<?php

namespace App\Campaigns;

use PDO;

final class CampaignPage
{
    /**
     * Pressel = página de apresentação da campanha (Pressel Premium e Landing Enxuta).
     * Fica vinculada à campanha, mas não publica nem altera a oferta de upsell
     * e nunca é a página principal do link da campanha (/o/{slug}).
     */
    public const PRESSEL_SQL = "(template_slug = 'pressel-premium' OR template_slug LIKE 'landing-enxuta%')";

    public static function isPressel(?string $templateSlug): bool
    {
        $slug = (string)$templateSlug;
        return $slug === 'pressel-premium' || str_starts_with($slug, 'landing-enxuta');
    }

    public static function campaign(PDO $pdo, int $workspaceId, int $campaignId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$campaignId, $workspaceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function offer(PDO $pdo, array $campaign, string $role = 'upsell'): array
    {
        if (!in_array($role, ['upsell', 'downsell'], true)) throw new \InvalidArgumentException('Tipo de oferta inválido.');
        $stmt = $pdo->prepare('SELECT * FROM upsell_offers WHERE campaign_id = ? AND workspace_id = ? AND offer_role = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$campaign['id'], $campaign['workspace_id'], $role]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) return $row;
        // Cada campanha recebe sua própria oferta em rascunho, nunca a de outra campanha.
        $slug = ($role === 'downsell' ? 'downsell-' : 'campanha-') . $campaign['id'] . '-' . bin2hex(random_bytes(3));
        $pdo->prepare("INSERT INTO upsell_offers (workspace_id, campaign_id, title, subtitle, price, quota_count, slug, is_published, is_active, offer_role, theme_config) VALUES (?, ?, ?, ?, ?, 1, ?, 0, 1, ?, ?)")
            ->execute([$campaign['workspace_id'], $campaign['id'], ($role === 'downsell' ? 'Uma alternativa para você' : $campaign['title']), '', $campaign['ticket_price'], $slug, $role, $role === 'downsell' ? json_encode(['mechanic'=>'direct','offer_headline_start'=>'Uma nova condição','offer_headline_highlight'=>'para você aproveitar.','cta_label'=>'QUERO ESTA OFERTA']) : null]);
        $stmt->execute([$campaign['id'], $campaign['workspace_id'], $role]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Validate the campaign form before writing either the campaign or its offer. */
    public static function boosterInput(array $input): ?array
    {
        if (!array_key_exists('booster_name', $input) && !array_key_exists('booster_multiplier', $input) && !array_key_exists('booster_price', $input)) return null;
        $name = trim((string)($input['booster_name'] ?? ''));
        $multiplier = filter_var($input['booster_multiplier'] ?? null, FILTER_VALIDATE_INT);
        $price = $input['booster_price'] ?? null;
        if ($name === '' || mb_strlen($name) > 150) throw new \InvalidArgumentException('Informe o nome do Booster com até 150 caracteres.');
        if ($multiplier === false || $multiplier < 1 || $multiplier > 1000000) throw new \InvalidArgumentException('Informe quantas vezes: um número inteiro de 1 a 1.000.000.');
        if (!is_numeric($price) || !is_finite((float)$price) || (float)$price < 0.01 || (float)$price > 99999999.99) throw new \InvalidArgumentException('Informe um valor válido para o Booster, a partir de R$ 0,01.');
        // Keep the multiplier in a single place even if it was pasted with the name.
        $name = trim(preg_replace('/\b\d+\s*x\b/iu', '', $name));
        if ($name === '') throw new \InvalidArgumentException('Informe também o nome do Booster.');
        return ['title' => $name . ' ' . $multiplier . 'X', 'quota_count' => $multiplier, 'price' => round((float)$price, 2)];
    }

    public static function saveBoosterDraft(PDO $pdo, array $campaign, array $values, string $role = 'upsell'): void
    {
        $offer = self::offer($pdo, $campaign, $role);
        $draft = json_decode($offer['draft_config'] ?? '', true) ?: [];
        $theme = $draft['theme'] ?? (json_decode($offer['theme_config'] ?? '', true) ?: []);
        $theme['headline_highlight'] = $values['title'];
        $theme['wheel_highlight'] = $values['title'];
        $theme['offer_headline_start'] = $values['title'] . ' está';
        $theme['cta_label'] = 'ATIVAR ' . mb_strtoupper($values['title'], 'UTF-8');
        $segments = $theme['wheel_segments'] ?? \App\Support\OfferTheme::defaults()['wheel_segments'];
        foreach ($segments as &$segment) { if (!empty($segment['is_win'])) $segment['label'] = $values['quota_count'] . 'X'; }
        unset($segment);
        $theme['wheel_segments'] = $segments;
        $values['subtitle'] = trim($draft['subtitle'] ?? $offer['subtitle'] ?? '') ?: $campaign['title'];
        $draft = array_merge($draft, $values, ['theme' => $theme]);
        $pdo->prepare('UPDATE upsell_offers SET title = ?, subtitle = ?, price = ?, quota_count = ?, theme_config = ?, draft_config = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND workspace_id = ? AND campaign_id = ?')
            ->execute([$values['title'], $values['subtitle'], $values['price'], $values['quota_count'], json_encode($theme, JSON_UNESCAPED_UNICODE), json_encode($draft, JSON_UNESCAPED_UNICODE), $offer['id'], $campaign['workspace_id'], $campaign['id']]);
    }

    public static function publishOffer(PDO $pdo, array $campaign, ?string $mechanic = null, string $role = 'upsell'): array
    {
        $offer = self::offer($pdo, $campaign, $role);
        $draft = json_decode($offer['draft_config'] ?? '', true) ?: [];
        $theme = $draft['theme'] ?? (json_decode($offer['theme_config'] ?? '', true) ?: []);
        if ($mechanic !== null && in_array($mechanic, ['scratch', 'wheel', 'direct'], true)) $theme['mechanic'] = $mechanic;
        // Ofertas antigas podem ainda não ter um snapshot de rascunho.
        foreach (['title', 'subtitle', 'price', 'quota_count', 'vturb_account_id', 'vturb_player_id', 'legacy_checkout_url', 'checkout_mode', 'slug'] as $key) {
            if (!array_key_exists($key, $draft)) $draft[$key] = $offer[$key] ?? null;
        }
        $draft['theme'] = $theme;
        $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pdo->prepare('UPDATE upsell_offers SET theme_config = ?, draft_config = ?, published_config = ?, is_published = 1, is_active = 1, published_at = CURRENT_TIMESTAMP WHERE id = ? AND campaign_id = ? AND workspace_id = ?')
            ->execute([json_encode($theme), $json, $json, $offer['id'], $campaign['id'], $campaign['workspace_id']]);
        return $offer;
    }
}
