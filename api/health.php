<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Database\Connection;

$startTime = microtime(true);
$status = 'ok';
$dbInfo = ['status' => 'disconnected'];

try {
    $dbStart = microtime(true);
    $pdo = Connection::make();
    $pdo->query('SELECT 1')->fetch();
    $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
    $dbInfo = [
        'status' => 'connected',
        'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        'latency_ms' => $dbLatency,
    ];
} catch (\Throwable $e) {
    $status = 'error';
    $dbInfo = [
        'status' => 'error',
        'message' => 'Database connection failed',
    ];
}

$storageDir = __DIR__ . '/../storage';
// Público: só o que serve para monitorar (sem tamanho de disco nem versão do PHP).
$diskInfo = [
    'writable' => is_writable($storageDir),
];

$requiredExtensions = ['pdo', 'curl', 'json', 'mbstring'];
$extStatus = [];
foreach ($requiredExtensions as $ext) {
    $extStatus[$ext] = extension_loaded($ext);
}

$httpCode = $status === 'ok' ? 200 : 503;
if (!headers_sent()) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

$response = [
    'status' => $status,
    'app' => 'upScale Platform',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'uptime_ms' => round((microtime(true) - $startTime) * 1000, 2),
    'database' => $dbInfo,
    'disk' => $diskInfo,
    'php' => [
        'extensions' => $extStatus,
    ],
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
