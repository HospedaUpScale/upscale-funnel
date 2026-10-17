<?php

require_once __DIR__ . '/../bootstrap.php';

use App\AI\ClaudeCopywriter;
use App\AI\GenerationQuota;
use App\Auth\AdminAuth;
use App\Campaigns\CampaignPage;
use App\Http\JsonResponse;
use App\Support\Env;

header('Cache-Control: no-store');
$auth = (new AdminAuth($wsRepo, $config['super_admin_token'] ?? ''))->authenticate(AdminAuth::extractBearerToken());
// Billable endpoint: authentication is mandatory even in local development.
if (!$auth) JsonResponse::error('Entre no painel para usar o assistente Claude.', 401);
if (!in_array($request->method(), ['GET', 'POST'], true)) JsonResponse::error('Método não permitido.', 405);
$targetWsId = (int)$request->get('workspace_id', $auth['workspace']->id ?? 0);
if ($targetWsId <= 0 || !$wsRepo->findById($targetWsId)) JsonResponse::error('Selecione um workspace válido.', 422);
if ($auth['role'] !== 'super_admin' && $auth['workspace']->id !== $targetWsId) {
    $ids = array_map(fn($w) => $w->id, $wsRepo->findDescendants($auth['workspace']->id));
    if (!in_array($targetWsId, $ids, true)) JsonResponse::error('Acesso negado a este workspace.', 403);
}
$campaignId = (int)$request->get('campaign_id', 0);
if ($campaignId > 0 && !CampaignPage::campaign($pdo, $targetWsId, $campaignId)) {
    JsonResponse::error('Campanha não encontrada neste workspace.', 404);
}
$key = trim((string)Env::get('ANTHROPIC_API_KEY', ''));
$quota = new GenerationQuota($pdo,
    max(0, (int)Env::get('CLAUDE_TOTAL_REQUEST_LIMIT', 100)),
    max(0, (int)Env::get('CLAUDE_DAILY_REQUEST_LIMIT', 20)));
if ($request->method() === 'GET') {
    JsonResponse::success(['configured' => $key !== '', 'model' => ClaudeCopywriter::MODEL, 'quota' => $quota->status()]);
}
if ($key === '') JsonResponse::error('Configure ANTHROPIC_API_KEY nas variáveis de ambiente do EasyPanel e faça o deploy.', 503);
try {
    $input = ClaudeCopywriter::input($request->get('brief'), $request->get('context'));
} catch (\InvalidArgumentException $e) {
    JsonResponse::error($e->getMessage(), 422);
}
try {
    $remaining = $quota->reserve();
} catch (\RuntimeException $e) {
    if ($e->getCode() === 429) JsonResponse::error($e->getMessage(), 429, ['quota' => $quota->status()]);
    throw $e;
}
try {
    $result = (new ClaudeCopywriter($key))->generate($input);
    JsonResponse::success($result + ['quota' => $remaining]);
} catch (\RuntimeException $e) {
    // Only our sanitized errors: never return the upstream body, headers, prompt or key.
    JsonResponse::error($e->getMessage(), 502, ['quota' => $remaining]);
}
