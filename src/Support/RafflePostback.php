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
    public static function payload(PDO $pdo, array $order, ?array $customer, ?array $offer): array
    {
        $wsId = (int)$order['workspace_id'];

        // Pedido da rifa: vem do card (token do webhook de vendas) ou do order_id enviado pela rifa.
        $raffleOrderId = null;
        $st = $pdo->prepare('SELECT external_order_id FROM order_access_tokens WHERE workspace_id = ? AND upsell_order_id = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$wsId, (int)$order['id']]);
        $raffleOrderId = $st->fetchColumn() ?: null;
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
                $campaign = ['id' => (int)$c['id'], 'title' => $c['title'], 'platform' => $c['external_platform'], 'external_campaign_id' => $c['external_campaign_id']];
            }
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

    /** Envia com até 3 tentativas. Devolve o resultado para registro. */
    public static function send(string $url, array $payload, string $secret): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type: application/json', 'User-Agent: upScale-Postback/1.0', 'X-Upscale-Event: ' . $payload['event']];
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
