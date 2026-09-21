<?php

// ==============================================================================
// upScale Media Network - API v1: Auditoria de Ganhadores & Bônus Extra
// ==============================================================================

require_once __DIR__ . '/../bootstrap.php';

use App\Http\JsonResponse;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$token = trim((string)$request->get('token'));
$orderId = trim((string)($request->get('order_id') ?? $request->get('pedido_id') ?? $request->get('id')));
$phone = trim((string)($request->get('phone') ?? $request->get('telefone')));
$targetWsId = (int)($request->get('workspace_id') ?? ($workspace->id ?? 2));

$found = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $found = $stmt->fetch();
} elseif ($orderId !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE workspace_id = ? AND external_order_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$targetWsId, $orderId]);
    $found = $stmt->fetch();
} elseif ($phone !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE workspace_id = ? AND customer_phone LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$targetWsId, '%' . $phone . '%']);
    $found = $stmt->fetch();
}

if (!$found) {
    JsonResponse::error('Nenhum registro de pedido ou raspadinha encontrado com os dados fornecidos.', 404);
}

$isEntitled = (bool)$found['bonus_entitled'];
$hasConverted = (bool)$found['upsell_converted'];
$extraBonus = (float)$found['extra_bonus'];

$veredicto = '';
if ($isEntitled && $hasConverted) {
    $veredicto = 'PARABÉNS! Ganhador 100% elegível: comprou a rifa, cumpriu o valor mínimo e ATIVOU a raspadinha. Bônus de R$ ' . number_format($extraBonus, 2, ',', '.') . ' LIBERADO para pagamento.';
} elseif ($isEntitled && !$hasConverted) {
    $veredicto = 'ATENÇÃO: O comprador atingiu a compra mínima de bilhetes, mas NÃO completou o pagamento do Booster/Raspadinha. Verifique o comprovante.';
} else {
    $veredicto = 'NÃO ELEGÍVEL: O valor da compra (R$ ' . number_format((float)$found['base_amount'], 2, ',', '.') . ') foi inferior ao mínimo exigido (R$ ' . number_format((float)$found['min_order'], 2, ',', '.') . ') para o bônus extra.';
}

JsonResponse::success([
    'order_id' => $found['external_order_id'],
    'customer_name' => $found['customer_name'],
    'customer_phone' => $found['customer_phone'],
    'base_amount_paid' => (float)$found['base_amount'],
    'min_order_required' => (float)$found['min_order'],
    'bonus_entitled' => $isEntitled,
    'upsell_converted' => $hasConverted,
    'upsell_order_id' => $found['upsell_order_id'],
    'extra_bonus_amount' => $extraBonus,
    'carro_a' => $found['carro_a'],
    'carro_b' => $found['carro_b'],
    'veredicto' => $veredicto,
    'timestamp' => date('c')
]);
