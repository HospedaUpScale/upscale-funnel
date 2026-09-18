<?php

use App\Database\Connection;

require_once __DIR__ . '/../src/autoload.php';

$pdo = Connection::make();

// 1. Check if legacy workspace already seeded
$stmt = $pdo->prepare("SELECT id FROM workspaces WHERE slug = ? OR custom_domain = ?");
$stmt->execute(['sincero', 'oferta.vendedorsincero.pro']);
$existing = $stmt->fetch();

if ($existing) {
    echo "Workspace already exists (ID: {$existing['id']}). Skipping seed.\n";
    return;
}

echo "Seeding initial workspace...\n";

// 2. Insert Merchant Workspace
$legacyApiToken = bin2hex(random_bytes(32));
$legacyAdminToken = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("
    INSERT INTO workspaces (parent_id, name, slug, custom_domain, type, api_token_hash, admin_token_hash, gateway_provider, gateway_credentials, status)
    VALUES (NULL, ?, ?, ?, 'merchant', ?, ?, 'pending', NULL, 'active')
");

$stmt->execute([
    'Vendedor Sincero',
    'sincero',
    'oferta.vendedorsincero.pro',
    hash('sha256', $legacyApiToken),
    hash('sha256', $legacyAdminToken)
]);

$merchantId = (int)$pdo->lastInsertId();

// 3. Insert Default Campaign
$stmt = $pdo->prepare("
    INSERT INTO campaigns (workspace_id, external_platform, external_campaign_id, title, ticket_price, status)
    VALUES (?, 'propria', 'camp_padrao', 'Campanha Principal', 90.00, 'active')
");
$stmt->execute([$merchantId]);
$campaignId = (int)$pdo->lastInsertId();

// 4. Insert Default Upsell Offer
$stmt = $pdo->prepare("
    INSERT INTO upsell_offers (
        workspace_id, campaign_id, type, title, subtitle, price, quota_count,
        legacy_checkout_url, checkout_mode, is_active
    ) VALUES (
        ?, ?, 'post_pix_scratch', 'Booster de Cotas', 'Campanha Principal', 90.00, 110,
        '',
        'pix_native', 1
    )
");
$stmt->execute([$merchantId, $campaignId]);

echo "Seeding completed successfully!\n";
echo "- Workspace ID: {$merchantId} (Slug: sincero, Domain: oferta.vendedorsincero.pro)\n";
echo "- Admin Token: {$legacyAdminToken}\n";
echo "- API Token: {$legacyApiToken}\n";
