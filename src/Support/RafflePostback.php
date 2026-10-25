<?php

namespace App\Support;

use PDO;

/**
 * Aviso para a plataforma da rifa de que as cotas extras foram pagas (evento upsell.quotas_paid).
 *
 * Destino: postback_url do workspace (Configurações → Webhooks) ou, para ofertas antigas,
 * delivery_webhook_url da oferta. Assinatura: HMAC-SHA256 do corpo JSON com POSTBACK_SECRET,
 * enviado no cabeçalho X-Upscale-Signature: sha256=<hex>. Até 3 tentativas; sucesso = HTTP 2xx.
 */
class RafflePostback
{
    /** Texto fixo assinado na verificação da integração, para a rifa conferir se o segredo é o mesmo. */
    public const PROBE = 'upscale:postback-probe';

    public static function payload(PDO $pdo, array $order, ?array $customer, ?array $offer): array
    {
        $wsId = (int)$order['workspace_id'];

        // Pedido da rifa: vem do card (token do webhook de vendas) ou do order_id enviado pela rifa.
        $raffleOrderId = null;
        $raffleCampaignRef = null;
        try {
            $st = $pdo->prepare('SELECT external_order_id, raffle_campaign_ref FROM order_access_tokens WHERE workspace_id = ? AND upsell_order_id = ? ORDER BY id DESC LIMIT 1');
            $st->execute([$wsId, (int)$order['id']]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            $st = $pdo->prepare('SELECT external_order_id FROM order_access_tokens WHERE workspace_id = ? AND upsell_order_id = ? ORDER BY id DESC LIMIT 1');
            $st->execute([$wsId, (int)$order['id']]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        $raffleOrderId = $row['external_order_id'] ?? null;
        $raffleCampaignRef = $row['raffle_campaign_ref'] ?? null;
        if (!$raffleOrderId && !empty($order['external_order_id']) && !preg_match('/^ord_[0-9a-f]+$/i', (string)$order['external_order_id'])) {
            $raffleOrderId = (string)$order['external_order_id'];
        }

        $published = $offer ? (json_decode($offer['published_config'] ?? '', true) ?: []) : [];
        $theme = $offer ? (is_string($offer['theme_config'] ?? null) ? (json_decode($offer['theme_config'], true) ?: []) : ($offer['theme_config'] ?? [])) : [];
        $quotas = (int)($published['quota_count'] ?? $offer['quota_count'] ?? $theme['quotas_extra'] ?? 0);
        if ($quotas <= 0) $quotas = (int)($theme['quotas_extra'] ?? 110);

        $campaign = null;
        if (!empty($offer['campaign_id'])) {
            $sc = $pdo->prepare('SELECT id, title, external_platform, external_campaign_id FROM campaigns WHERE id = ? AND workspace_id = ?');
            $sc->execute([(int)$offer['campaign_id'], $wsId]);
            if ($c = $sc->fetch(PDO::FETCH_ASSOC)) {
                // external_campaign_id = o ID que a rifa usou na venda (quando houver), para a rifa conferir.
                $campaign = ['id' => (int)$c['id'], 'title' => $c['title'], 'platform' => $c['external_platform'], 'external_campaign_id' => $raffleCampaignRef ?? $c['external_campaign_id']];
            }
        }

        if (!$campaign && $raffleCampaignRef) {
            $campaign = ['id' => null, 'title' => null, 'platform' => null, 'external_campaign_id' => $raffleCampaignRef];
        }

        return [
            'event'             => 'upsell.quotas_paid',
            'order_id'          => (int)$order['id'],
            'raffle_order_id'   => $raffleOrderId,
            'external_order_id' => $raffleOrderId ?? $order['external_order_id'],
            'campaign'          => $campaign,
            'offer'             => $offer ? ['id' => (int)$offer['id'], 'title' => $published['title'] ?? $offer['title'], 'role' => $offer['offer_role'] ?? 'upsell'] : null,
            'customer'          => [
                'name'  => $customer['name'] ?? null,
                'phone' => $customer['phone'] ?? null,
                'cpf'   => $customer['cpf'] ?? null,
                'email' => $customer['email'] ?? null,
            ],
            'quotas_awarded'    => $quotas,
            'amount_paid'       => (float)($order['upsell_amount'] ?? $order['total_amount'] ?? 0),
            'paid_at'           => gmdate('c'),
        ];
    }

    /**
     * Para onde vai o aviso deste pedido e com qual chave.
     * 1º retorno automático: endereço e chave que a rifa mandou na própria venda (callback_url/callback_key).
     * 2º retorno do workspace (painel) ou da oferta antiga, assinado com POSTBACK_SECRET.
     * @return array{url: ?string, key: ?string}
     */
    public static function target(PDO $pdo, array $order, ?array $offer): array
    {
        try {
            $st = $pdo->prepare('SELECT callback_url, callback_key FROM order_access_tokens WHERE workspace_id = ? AND upsell_order_id = ? AND callback_url IS NOT NULL ORDER BY id DESC LIMIT 1');
            $st->execute([(int)$order['workspace_id'], (int)$order['id']]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            $row = null; // banco ainda sem a migração 0033
        }
        if ($row && !empty($row['callback_url']) && !empty($row['callback_key'])) {
            return ['url' => (string)$row['callback_url'], 'key' => (string)$row['callback_key']];
        }
        return ['url' => self::destination($pdo, (int)$order['workspace_id'], $offer), 'key' => null];
    }

    /** Endereço de retorno aceito na venda: https público (http/localhost só com APP_ENV=local). */
    public static function isAllowedCallback(string $url, bool $local = false): bool
    {
        if (strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $p = parse_url($url);
        $scheme = strtolower($p['scheme'] ?? '');
        $host = strtolower($p['host'] ?? '');
        if ($host === '' || isset($p['user']) || isset($p['pass'])) return false;
        if ($local) return in_array($scheme, ['http', 'https'], true);
        if ($scheme !== 'https' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.internal')) return false;
        $ip = trim($host, '[]');
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return true;
    }

    /** O host do retorno automático resolve só para IPs públicos (evita apontar para a rede interna). */
    public static function resolvesPublic(string $url): bool
    {
        $host = trim((string)parse_url($url, PHP_URL_HOST), '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) return false;
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
        }
        return true;
    }

    public static function destination(PDO $pdo, int $workspaceId, ?array $offer): ?string
    {
        $theme = $offer ? (is_string($offer['theme_config'] ?? null) ? (json_decode($offer['theme_config'], true) ?: []) : ($offer['theme_config'] ?? [])) : [];
        $url = $theme['delivery_webhook_url'] ?? $theme['postback_url'] ?? null;
        if (!$url) {
            try {
                $st = $pdo->prepare('SELECT postback_url FROM workspaces WHERE id = ?');
                $st->execute([$workspaceId]);
                $url = $st->fetchColumn() ?: null;
            } catch (\PDOException $e) {
                $url = null; // banco ainda sem a migração 0030
            }
        }
        return ($url && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)) ? $url : null;
    }

    /**
     * Envia com até 3 tentativas. Devolve o resultado para registro.
     * Com $bearer (chave do retorno automático): Authorization: Bearer <chave> e assinatura HMAC com a mesma chave.
     */
    public static function send(string $url, array $payload, string $secret, ?string $bearer = null): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type: application/json', 'User-Agent: upScale-Postback/1.0', 'X-Upscale-Event: ' . $payload['event']];
        if ($bearer !== null && $bearer !== '') {
            $headers[] = 'Authorization: Bearer ' . $bearer;
            $secret = $bearer;
        }
        if ($secret !== '') {
            $headers[] = 'X-Upscale-Signature: sha256=' . hash_hmac('sha256', $body, $secret);
        }
        $last = ['delivered' => false, 'attempts' => 0, 'status_code' => 0, 'response' => ''];
        for ($i = 1; $i <= 3; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            $last = ['delivered' => $code >= 200 && $code < 300, 'attempts' => $i, 'status_code' => $code, 'response' => mb_substr((string)($resp ?: $err), 0, 300)];
            if ($last['delivered'] || ($code >= 400 && $code < 500 && $code !== 408 && $code !== 429)) break;
            if ($i < 3) usleep($i * 1000000);
        }
        return $last;
    }
}
