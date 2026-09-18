<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Repositories\ConversionEventRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

// Allow in local dev or if valid admin
if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

$txId = (int)($request->get('transaction_id') ?? $request->get('tx_id') ?? 0);
if ($txId <= 0) {
    JsonResponse::error('transaction_id is required', 400);
}

$txRepo = new TransactionRepository($pdo);
$orderRepo = new OrderRepository($pdo);
$eventRepo = new ConversionEventRepository($pdo);

$tx = $txRepo->find($txId);
if (!$tx) {
    JsonResponse::error('Transaction not found', 404);
}

$order = $orderRepo->find((int)$tx['order_id']);

$txRepo->markPaid($txId);
if ($order) {
    $orderRepo->markPaid((int)$order['id']);
    $eventRepo->log((int)$order['workspace_id'], (int)$order['id'], 'pix_paid', [
        'transaction_id' => $txId,
        'amount'         => (float)$tx['amount'],
        'forced'         => true
    ]);
}

JsonResponse::success([
    'message'        => 'Transação Pix confirmada com sucesso!',
    'transaction_id' => $txId,
    'status'         => 'paid'
]);
