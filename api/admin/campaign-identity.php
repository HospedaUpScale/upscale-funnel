<?php

/**
 * Identidade visual da campanha.
 * GET  ?campaign_id=  → temas prontos + identidade atual da campanha
 * POST {campaign_id, preset, [mode, primary, accent]} → aplica em todos os modelos da campanha
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Support\CampaignIdentity;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

$input = $request->method() === 'POST' ? $request->all() : [];
$targetWsId = (int)($request->get('workspace_id') ?? ($input['workspace_id'] ?? ($auth['workspace']->id ?? 2)));
if ($targetWsId <= 0) {
    $targetWsId = ($auth && $auth['workspace']) ? $auth['workspace']->id : 2;
}
if ($auth && $auth['role'] !== 'super_admin' && $auth['workspace']->id !== $targetWsId) {
    $allowedIds = array_map(fn($w) => $w->id, $wsRepo->findDescendants($auth['workspace']->id));
    if (!in_array($targetWsId, $allowedIds, true)) {
        JsonResponse::error('Acesso negado para este workspace/campanha.', 403);
    }
}

$campaignId = (int)($request->get('campaign_id') ?? ($input['campaign_id'] ?? 0));
if ($campaignId <= 0) {
    JsonResponse::error('Informe a campanha.', 422);
}
$stmt = $pdo->prepare('SELECT id, title, brand_identity FROM campaigns WHERE id = ? AND workspace_id = ?');
$stmt->execute([$campaignId, $targetWsId]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$campaign) {
    JsonResponse::error('Campanha não encontrada.', 404);
}

if ($request->method() === 'GET') {
    JsonResponse::success([
        'campaign_id' => $campaignId,
        'identity'    => CampaignIdentity::stored($campaign['brand_identity'] ?? null),
        'presets'     => CampaignIdentity::presetList(),
    ]);
}

if ($request->method() === 'POST') {
    try {
        $identity = CampaignIdentity::fromInput($input);
    } catch (\InvalidArgumentException $e) {
        JsonResponse::error($e->getMessage(), 422);
    }

    try {
        $summary = CampaignIdentity::apply($pdo, $targetWsId, $campaignId, $identity);
    } catch (\Throwable $e) {
        JsonResponse::error('Não foi possível aplicar a identidade visual agora.', 500, ['code' => 'identity_apply_failed']);
    }

    $parts = [];
    if ($summary['offers']) $parts[] = $summary['offers'] . ($summary['offers'] > 1 ? ' ofertas' : ' oferta') . ' (raspadinha, roleta e oferta direta)';
    if ($summary['pressel']) $parts[] = $summary['pressel'] . ($summary['pressel'] > 1 ? ' pressels' : ' pressel');
    if ($summary['landing']) $parts[] = $summary['landing'] . ($summary['landing'] > 1 ? ' landings' : ' landing');

    JsonResponse::success([
        'message'  => 'Identidade "' . $identity['name'] . '" aplicada' . ($parts ? ' em ' . implode(', ', $parts) : '') . '.',
        'identity' => CampaignIdentity::stored(json_encode($identity)),
        'summary'  => $summary,
    ]);
}

JsonResponse::error('Method not allowed', 405);
