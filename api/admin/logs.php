<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

// Target workspace
$targetWsId = (int)($request->get('workspace_id') ?? $workspace->id);
if ($auth && $auth['role'] !== 'super_admin') {
    // If not super admin, check if target is self or a descendant
    if ($auth['workspace']->id !== $targetWsId) {
        $descendants = $wsRepo->findDescendants($auth['workspace']->id);
        $allowedIds = array_map(fn($w) => $w->id, $descendants);
        if (!in_array($targetWsId, $allowedIds, true)) {
            JsonResponse::error('Forbidden for this workspace', 403);
        }
    }
}

$logFile = dirname(__DIR__) . '/logs/' . $targetWsId . '.pedidos.log';
$logs = [];

if (file_exists($logFile)) {
    $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
    foreach (array_reverse($lines) as $line) {
        $decoded = json_decode($line, true);
        if ($decoded) $logs[] = $decoded;
    }
}

JsonResponse::send([
    'status' => 'success',
    'workspace_id' => $targetWsId,
    'total'  => count($logs),
    'logs'   => $logs
]);
