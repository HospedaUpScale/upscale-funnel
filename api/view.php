<?php

require_once __DIR__ . '/bootstrap.php';

use App\Repositories\ConversionEventRepository;

$sid       = $request->get('sid', 'padrao');
$pid       = $request->get('pid', 'booster110x');
$evento    = $request->get('evento', 'view'); // view, scratch, click, scratch_win
$interagiu = $request->get('interagiu', '0');
$ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$data      = date('Y-m-d H:i:s');

// 1. Grava no banco de dados relacional
try {
    $eventRepo = new ConversionEventRepository($pdo);
    $eventRepo->log($workspace->id, null, $evento, [
        'sid'       => $sid,
        'pid'       => $pid,
        'interagiu' => $interagiu,
        'ip'        => $ip
    ]);
} catch (\Throwable $e) {
    // Fail-safe: não trava resposta caso BD esteja em manutenção
}

// 2. Grava log local de auditoria
$linha = sprintf("[%s] EVENTO: %s | WS: %s | SID: %s | PID: %s | INTERAGIU: %s | IP: %s\n", $data, $evento, $workspace->slug, $sid, $pid, $interagiu, $ip);
@file_put_contents(\App\Support\LogPath::file($workspace->id . '.metricas.log'), \App\Support\LogPath::oneLine(rtrim($linha, "\n")) . "\n", FILE_APPEND | LOCK_EX);

echo json_encode([
    'status'     => 'ok',
    'workspace'  => $workspace->slug,
    'timestamp'  => time(),
    'registrado' => true
]);
