<?php

require_once __DIR__ . '/../src/autoload.php';

echo "=== TESTANDO API DE GESTAO DE CAMPANHAS E ACOES ===\n\n";

// 1. Teste de GET
$ch = curl_init('http://127.0.0.1:8000/api/admin/campaigns.php?workspace_id=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer sincero-admin-2026']);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "1. GET /api/admin/campaigns.php: HTTP {$code}\n";
$data = json_decode($res, true);
echo "Total de Ações: " . ($data['total'] ?? 0) . "\n";
if (!empty($data['campaigns'][0])) {
    $c = $data['campaigns'][0];
    echo "Ação 1: '{$c['title']}' | Status: {$c['status']}\n\n";
}

// 2. Teste de Finalizar Ação
$ch = curl_init('http://127.0.0.1:8000/api/admin/campaigns.php?workspace_id=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'action'       => 'finalize',
    'workspace_id' => 2
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer sincero-admin-2026'
]);
$resFin = curl_exec($ch);
curl_close($ch);

echo "2. POST action=finalize:\n";
echo "Resposta: {$resFin}\n\n";

// 3. Teste de Publicar Ação
$ch = curl_init('http://127.0.0.1:8000/api/admin/campaigns.php?workspace_id=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'action'       => 'publish',
    'workspace_id' => 2
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer sincero-admin-2026'
]);
$resPub = curl_exec($ch);
curl_close($ch);

echo "3. POST action=publish:\n";
echo "Resposta: {$resPub}\n\n";
