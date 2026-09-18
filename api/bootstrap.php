<?php

use App\Database\Connection;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Repositories\WorkspaceRepository;
use App\Tenancy\TenantResolver;

require_once __DIR__ . '/../src/autoload.php';
$config = require __DIR__ . '/../config/config.php';

// 1. Database Connection
$pdo = Connection::make();

// 2. HTTP Request
$request = new Request();

// 3. Resolve Workspace
$wsRepo = new WorkspaceRepository($pdo);
$debugWs = $request->get('__workspace') 
    ?? $request->get('workspace') 
    ?? $request->header('X-Debug-Workspace');

$resolver = new TenantResolver(
    workspaceRepo: $wsRepo,
    baseDomain: $config['base_domain'] ?? 'vendedorsincero.pro',
    defaultSlug: $config['default_workspace_slug'] ?? 'sincero',
    appEnv: $config['app_env'] ?? 'local'
);

$workspace = $resolver->resolve(
    host: $_SERVER['HTTP_HOST'] ?? null,
    debugOverride: $debugWs
);

if (!$workspace) {
    JsonResponse::error('Workspace not found or inactive', 404);
}

// 4. Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Api-Token, Token, X-Admin-Token, X-Debug-Workspace, X-Workspace');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
