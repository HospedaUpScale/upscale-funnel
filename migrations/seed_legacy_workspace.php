<?php

use App\Database\Connection;

require_once __DIR__ . '/../src/autoload.php';

$pdo = Connection::make();

// 1. Check if legacy workspace already seeded
$stmt = $pdo->prepare("SELECT id FROM workspaces WHERE slug = ? OR custom_domain = ?");
$stmt->execute(['sincero', 'oferta.vendedorsincero.pro']);
$existing = $stmt->fetch();

if ($existing) {
    echo "Legacy workspace already exists (ID: {$existing['id']}). Skipping seed.\n";
    return;
}

echo "Seeding initial partner and legacy workspace...\n";

// 2. Insert White-Label Partner Workspace (e.g. Play55)
$partnerApiToken = bin2hex(random_bytes(32));
$partnerAdminToken = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("
    INSERT INTO workspaces (name, slug, type, api_token_hash, admin_token_hash, status)
    VALUES (?, ?, 'partner_whitelabel', ?, ?, 'active')
");
$stmt->execute([
    'Play55 Hub Parceiro',
    'play55',
    hash('sha256', $partnerApiToken),
    hash('sha256', $partnerAdminToken)
]);

$partnerId = (int)$pdo->lastInsertId();

// 3. Insert Legacy Merchant Workspace (Vendedor Sincero)
$legacyApiToken = bin2hex(random_bytes(32));
$legacyAdminToken = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("
    INSERT INTO workspaces (parent_id, name, slug, custom_domain, type, api_token_hash, admin_token_hash, gateway_provider, gateway_credentials, status)
    VALUES (?, ?, ?, ?, 'merchant', ?, ?, 'mock', ?, 'active')
");

$mockCreds = json_encode([
    'pix_key' => '00000000000',
    'merchant_name' => 'Vendedor Sincero',
    'merchant_city' => 'Sao Paulo'
]);

$stmt->execute([
    $partnerId,
    'Vendedor Sincero',
    'sincero',
    'oferta.vendedorsincero.pro',
    hash('sha256', $legacyApiToken),
    hash('sha256', $legacyAdminToken),
    $mockCreds
]);

$merchantId = (int)$pdo->lastInsertId();

// 4. Insert Split Rules (10% SaaS, 5% Partner, 85% Merchant)
$stmt = $pdo->prepare("
    INSERT INTO partner_split_rules (partner_workspace_id, merchant_workspace_id, saas_rate_percentage, partner_rate_percentage, merchant_rate_percentage, absorb_gateway_fees)
    VALUES (?, ?, 10.00, 5.00, 85.00, 'merchant')
");
$stmt->execute([$partnerId, $merchantId]);

// 5. Insert Default Campaign
$stmt = $pdo->prepare("
    INSERT INTO campaigns (workspace_id, external_platform, external_campaign_id, title, ticket_price, status)
    VALUES (?, 'sorteamos', 'camp_bobzao', 'Kit Bobzão Bitruck + Ford F250', 90.00, 'active')
");
$stmt->execute([$merchantId]);
$campaignId = (int)$pdo->lastInsertId();

// 6. Insert Default Upsell Offer
$stmt = $pdo->prepare("
    INSERT INTO upsell_offers (
        workspace_id, campaign_id, type, title, subtitle, price, quota_count,
        legacy_checkout_url, checkout_mode, is_active
    ) VALUES (
        ?, ?, 'post_pix_scratch', 'Booster 110X', 'Kit Bobzão Bitruck + Ford F250', 90.00, 110,
        'https://sinceropremios.com/campanha/kit-vw19360-degrossi-ford-f250-azul?cupom=110XCHANCES&afiliado=L3WLFOKBDL',
        'pix_native', 1
    )
");
$stmt->execute([$merchantId, $campaignId]);

echo "Seeding completed successfully!\n";
echo "- Partner Workspace ID: {$partnerId} (Slug: play55)\n";
echo "- Partner Admin Token: {$partnerAdminToken}\n";
echo "- Partner API Token: {$partnerApiToken}\n";
echo "- Merchant Workspace ID: {$merchantId} (Slug: sincero, Domain: oferta.vendedorsincero.pro)\n";
echo "- Admin Token: {$legacyAdminToken}\n";
echo "- API Token: {$legacyApiToken}\n";
