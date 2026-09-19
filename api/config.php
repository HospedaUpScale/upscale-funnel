<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\JsonResponse;
use App\Support\OfferTheme;

// 1. Identificar workspace / oferta
$targetWsId = null;
if (!empty($request->get('workspace_id'))) {
    $targetWsId = (int)$request->get('workspace_id');
} elseif (!empty($request->get('__workspace'))) {
    $targetWsId = (int)$request->get('__workspace');
} elseif (!empty($workspace)) {
    $targetWsId = $workspace->id;
} else {
    $targetWsId = 2; // Default legacy workspace
}

$offerId = !empty($request->get('offer_id')) ? (int)$request->get('offer_id') : null;

if ($offerId) {
    $stmt = $pdo->prepare("SELECT * FROM upsell_offers WHERE id = ?");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM upsell_offers 
        WHERE workspace_id = ?
        ORDER BY is_active DESC, id ASC LIMIT 1
    ");
    $stmt->execute([$targetWsId]);
    $offer = $stmt->fetch();
}

if (!$offer) {
    // Fallback default
    $offer = [
        'id'                  => 1,
        'workspace_id'        => $targetWsId,
        'campaign_id'         => 1,
        'title'               => 'Booster 110X',
        'subtitle'            => 'Kit Bobzão Bitruck + Ford F250',
        'price'               => 90.00,
        'quota_count'         => 110,
        'vturb_account_id'    => '',
        'vturb_player_id'     => '',
        'legacy_checkout_url' => 'https://sinceropremios.com/',
        'checkout_mode'       => 'pix_native',
        'is_active'           => 1,
        'theme_config'        => null
    ];
}

// 2. Resolver status da Ação / Campanha
$campaign = null;
if (!empty($offer['campaign_id'])) {
    $stmtC = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmtC->execute([(int)$offer['campaign_id']]);
    $campaign = $stmtC->fetch();
} else {
    $stmtC = $pdo->prepare("SELECT * FROM campaigns WHERE workspace_id = ? ORDER BY id DESC LIMIT 1");
    $stmtC->execute([$targetWsId]);
    $campaign = $stmtC->fetch();
}

$actionStatus = 'published';
if ($campaign) {
    $actionStatus = in_array($campaign['status'], ['published', 'active']) ? 'published' : ($campaign['status'] === 'ended' ? 'ended' : 'draft');
}
if (empty($offer['is_active'])) {
    $actionStatus = 'ended';
}

$themeConfig = [];
if (!empty($offer['theme_config'])) {
    $decoded = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
    if (is_array($decoded)) {
        $themeConfig = $decoded;
    }
}

$mergedTheme = OfferTheme::merge($themeConfig);

header('Cache-Control: no-cache, no-store, must-revalidate');
JsonResponse::send([
    'status'        => 'success',
    'action_status' => $actionStatus,
    'is_published'  => $actionStatus === 'published',
    'is_ended'      => $actionStatus === 'ended',
    'campaign'      => $campaign ? [
        'id'           => (int)$campaign['id'],
        'title'        => $campaign['title'],
        'status'       => $actionStatus,
        'published_at' => $campaign['published_at'],
        'ended_at'     => $campaign['ended_at']
    ] : null,
    'offer'  => [
        'id'                  => (int)$offer['id'],
        'workspace_id'        => (int)$offer['workspace_id'],
        'title'               => $offer['title'] ?? 'Booster 110X',
        'subtitle'            => $offer['subtitle'] ?? ($campaign['title'] ?? ''),
        'price'               => (float)($offer['price'] ?? 90.00),
        'quota_count'         => (int)($offer['quota_count'] ?? 110),
        'vturb_account_id'    => $offer['vturb_account_id'] ?? '',
        'vturb_player_id'     => $offer['vturb_player_id'] ?? '',
        'legacy_checkout_url' => $offer['legacy_checkout_url'] ?? '',
        'checkout_mode'       => $offer['checkout_mode'] ?? 'pix_native',
        'is_active'           => (bool)$offer['is_active'],
        'theme'               => $mergedTheme,
    ]
]);
