<?php

// ==============================================================================
// upScale Media Network - API v1: Endpoint de Consulta de Oferta
// ==============================================================================

require_once __DIR__ . '/../bootstrap.php';

use App\Http\JsonResponse;
use App\Repositories\CampaignRepository;
use App\Repositories\OfferRepository;
use App\Support\OfferTheme;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$targetWsId = (int)($request->get('workspace_id') ?? ($workspace->id ?? 2));

$offerRepo = new OfferRepository($pdo);
$campaignRepo = new CampaignRepository($pdo);

$offer = $offerRepo->defaultActiveForWorkspace($targetWsId);
$campaign = $campaignRepo->defaultForWorkspace($targetWsId);

if (!$offer) {
    JsonResponse::error('Nenhuma oferta ativa encontrada para este workspace.', 404);
}

$themeConfig = [];
if (!empty($offer['theme_config'])) {
    $decoded = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
    if (is_array($decoded)) $themeConfig = $decoded;
}
$mergedTheme = OfferTheme::merge($themeConfig);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
$baseUrl = $scheme . '://' . $host;

JsonResponse::success([
    'platform' => 'upScale Media Network',
    'version' => '1.0.0',
    'workspace' => [
        'id' => $targetWsId,
        'name' => $workspace->name ?? 'Workspace',
        'slug' => $workspace->slug ?? 'default'
    ],
    'offer' => [
        'id' => (int)$offer['id'],
        'title' => $offer['title'],
        'subtitle' => $offer['subtitle'],
        'price' => (float)$offer['price'],
        'quota_count' => (int)$offer['quota_count'],
        'checkout_mode' => $offer['checkout_mode'] ?? 'pix_native',
        'cta_label' => $mergedTheme['cta_label'] ?? 'ATIVAR MEU BOOSTER 110X',
        'timer_minutes' => (float)($mergedTheme['timer_minutes'] ?? 3),
        'scratch_threshold' => (float)($mergedTheme['scratch_threshold'] ?? 22),
    ],
    'bonus' => [
        'carro_a' => $themeConfig['carro_a'] ?? 'RANGER',
        'carro_b' => $themeConfig['carro_b'] ?? 'BMW',
        'extra_bonus' => (float)($themeConfig['extra_bonus'] ?? 50000.00),
        'min_order' => (float)($themeConfig['min_order'] ?? 30.00),
    ],
    'endpoints' => [
        'webhook_sales' => $baseUrl . '/api/v1/webhook',
        'card_embed' => $baseUrl . '/?workspace_id=' . $targetWsId,
        'pix_create' => $baseUrl . '/api/pix-upsell.php',
        'check_winner' => $baseUrl . '/api/v1/winner',
    ]
]);
