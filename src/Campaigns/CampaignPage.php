<?php

namespace App\Campaigns;

use PDO;

final class CampaignPage
{
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
