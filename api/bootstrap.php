<?php

use App\Database\Connection;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Repositories\WorkspaceRepository;
use App\Tenancy\TenantResolver;

require_once __DIR__ . '/../src/autoload.php';

// Nunca deixa warnings/notices vazarem para a resposta: a API sempre fala JSON válido.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

function api_log_error(string $line): void
{
    $dir = __DIR__ . '/logs';
    if (is_dir($dir) && is_writable($dir)) {
        @error_log('[' . date('c') . '] ' . $line . "\n", 3, $dir . '/api-errors.log');
    } else {
        error_log($line);
    }
}

set_exception_handler(function (\Throwable $e): void {
    api_log_error(get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    $raw = $e->getMessage();
    $message = 'Erro interno no servidor. Tente novamente em instantes.';

    if (stripos($raw, 'readonly database') !== false
        || stripos($raw, 'unable to open database file') !== false
        || stripos($raw, 'disk I/O error') !== false) {
        $message = 'O servidor não conseguiu gravar no banco de dados (permissão da pasta storage/). Ajuste as permissões e tente novamente.';
    } elseif (stripos($raw, 'no such table') !== false || stripos($raw, "doesn't exist") !== false) {
        $message = 'Banco de dados desatualizado. Execute as migrations (php scripts/migrate.php).';
    } elseif (stripos($raw, 'UNIQUE constraint') !== false || stripos($raw, 'Duplicate entry') !== false) {
        $message = 'Já existe um registro com esses dados (valor duplicado).';
    } elseif (stripos($raw, 'Connection refused') !== false || stripos($raw, 'Access denied for user') !== false || stripos($raw, 'Unknown database') !== false) {
        $message = 'Não foi possível conectar ao banco de dados. Verifique as credenciais DB_* do servidor.';
    }

    $payload = ['status' => 'error', 'message' => $message];
    if (in_array(strtolower((string) getenv('APP_ENV')), ['', 'local', 'dev', 'development'], true)) {
        $payload['detail'] = $raw;
    }

    JsonResponse::send($payload, 500);
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        api_log_error('FATAL: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['status' => 'error', 'message' => 'Erro fatal no servidor. Consulte api/logs/api-errors.log.'], JSON_UNESCAPED_UNICODE);
    }
});

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
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(200);
    exit;
}
