<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\JsonResponse;
use App\Repositories\ConversionEventRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;

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

// Scope check: ensure this transaction belongs to this workspace
$order = $orderRepo->find((int)$tx['order_id']);
if (!$order || (int)$order['workspace_id'] !== $workspace->id) {
    JsonResponse::error('Transaction not found in this workspace', 404);
}

$currentStatus = $tx['status'];
$now = time();

// 1. If already paid
if ($currentStatus === 'paid') {
    JsonResponse::send([
        'status'             => 'success',
        'transaction_status' => 'paid',
        'paid'               => true,
        'paid_at'            => $tx['paid_at'] ?? date('Y-m-d H:i:s'),
        'quotas'             => 110,
    ]);
}

// 2. Check if expired
$expiresTs = !empty($tx['expires_at']) ? strtotime($tx['expires_at']) : 0;
if ($expiresTs > 0 && $now >= $expiresTs) {
    $txRepo->markExpired($txId);
    JsonResponse::send([
        'status'             => 'success',
        'transaction_status' => 'expired',
        'paid'               => false,
        'message'            => 'Chave Pix expirada. Gere uma nova cobrança.'
    ]);
}

// 3. Check Mock Auto-confirm
$autoConfirmTs = !empty($tx['mock_auto_confirm_at']) ? strtotime($tx['mock_auto_confirm_at']) : 0;
if ($tx['gateway'] === 'mock' && $autoConfirmTs > 0 && $now >= $autoConfirmTs) {
    // Auto-confirm mock payment
    $txRepo->markPaid($txId);
    $orderRepo->markPaid((int)$order['id']);
    $eventRepo->log($workspace->id, (int)$order['id'], 'pix_paid', [
        'transaction_id' => $txId,
        'amount'         => (float)$tx['amount'],
        'auto_confirmed' => true
    ]);

    JsonResponse::send([
        'status'             => 'success',
        'transaction_status' => 'paid',
        'paid'               => true,
        'paid_at'            => date('Y-m-d H:i:s'),
        'quotas'             => 110,
    ]);
}

// 4. Still pending
$secondsRemaining = max(0, $expiresTs - $now);
JsonResponse::send([
    'status'             => 'success',
    'transaction_status' => 'pending',
    'paid'               => false,
    'expires_at'         => $tx['expires_at'],
    'seconds_remaining'  => $secondsRemaining
]);
