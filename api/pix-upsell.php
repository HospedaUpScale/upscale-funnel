<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\JsonResponse;
use App\Pix\MockPixGateway;
use App\Repositories\ConversionEventRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OfferRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PartnerSplitRuleRepository;
use App\Repositories\TransactionRepository;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$input = $request->all();

// 1. Resolve Offer
$offerRepo = new OfferRepository($pdo);
$offerId = !empty($input['offer_id']) ? (int)$input['offer_id'] : null;

$offer = $offerId 
    ? $offerRepo->find($offerId, $workspace->id)
    : $offerRepo->defaultActiveForWorkspace($workspace->id);

if (!$offer || empty($offer['is_active'])) {
    JsonResponse::error('Nenhuma oferta ativa encontrada para este workspace', 404);
}

$price = (float)$offer['price'];
$offerTitle = $offer['title'];

// 2. Resolve Customer
$customerName = trim($input['name'] ?? $input['customer']['name'] ?? 'Cliente');
$customerPhone = trim($input['phone'] ?? $input['telefone'] ?? $input['customer']['phone'] ?? '');
$customerCpf = trim($input['cpf'] ?? $input['customer']['cpf'] ?? '');
$customerEmail = trim($input['email'] ?? $input['customer']['email'] ?? '');

$customerRepo = new CustomerRepository($pdo);
$customer = $customerRepo->upsert(
    workspaceId: $workspace->id,
    name: $customerName,
    phone: $customerPhone ?: null,
    cpf: $customerCpf ?: null,
    email: $customerEmail ?: null
);

// 3. Create Order
$orderRepo = new OrderRepository($pdo);
$externalOrderId = $input['order_id'] ?? $input['pedido_id'] ?? uniqid('ord_');

$order = $orderRepo->create([
    'workspace_id'      => $workspace->id,
    'customer_id'       => $customer['id'],
    'offer_id'          => $offer['id'],
    'flow_type'         => 'post_pix_upsell',
    'status'            => 'pending',
    'external_order_id' => $externalOrderId,
    'base_amount'       => 0.00,
    'upsell_amount'     => $price,
    'total_amount'      => $price,
    'utm_source'        => $input['utm_source'] ?? null,
    'utm_medium'        => $input['utm_medium'] ?? null,
    'utm_campaign'      => $input['utm_campaign'] ?? null,
]);

// 4. Calculate Split
$splitRuleRepo = new PartnerSplitRuleRepository($pdo);
$splitRule = $splitRuleRepo->resolveFor($workspace);

$saasPct = (float)$splitRule['saas_rate_percentage'];
$partnerPct = (float)$splitRule['partner_rate_percentage'];
$merchantPct = (float)$splitRule['merchant_rate_percentage'];

$saasAmount = round(($price * $saasPct) / 100, 2);
$partnerAmount = round(($price * $partnerPct) / 100, 2);
$merchantAmount = round($price - $saasAmount - $partnerAmount, 2);

$splitDetails = [
    'saas_amount'     => $saasAmount,
    'partner_amount'  => $partnerAmount,
    'merchant_amount' => $merchantAmount,
    'saas_percent'    => $saasPct,
    'partner_percent' => $partnerPct,
    'merchant_percent'=> $merchantPct,
];

// 5. Create Pix Charge
$gateway = new MockPixGateway($config['pix'] ?? []);
$charge = $gateway->createCharge(
    workspace: $workspace,
    orderId: $order['id'],
    amount: $price,
    description: $offerTitle,
    customerCpf: $customer['cpf'] ?? null,
    customerName: $customer['name'] ?? null
);

// 6. Record Transaction
$txRepo = new TransactionRepository($pdo);
$transaction = $txRepo->create([
    'order_id'            => $order['id'],
    'type'                => 'upsell_order',
    'status'              => 'pending',
    'gateway'             => $workspace->gatewayProvider ?: 'mock',
    'gateway_tx_id'       => $charge->gatewayTxId,
    'pix_copy_paste'      => $charge->copyPaste,
    'pix_qr_base64'       => $charge->qrBase64,
    'amount'              => $price,
    'split_details'       => $splitDetails,
    'mock_auto_confirm_at'=> null,
    'expires_at'          => $charge->expiresAt,
]);

// 7. Log Conversion Event
$eventRepo = new ConversionEventRepository($pdo);
$eventRepo->log($workspace->id, $order['id'], 'pix_generated', [
    'transaction_id' => $transaction['id'],
    'amount'         => $price,
    'offer_id'       => $offer['id']
]);

// 8. Atualizar token de acesso pós-compra se fornecido (Auditoria de Ganhadores & Bônus Extra)
if (!empty($input['token'])) {
    $pdo->prepare("
        UPDATE order_access_tokens 
        SET upsell_converted = 1, upsell_order_id = ? 
        WHERE token = ?
    ")->execute([(int)$order['id'], trim($input['token'])]);
} elseif (!empty($externalOrderId)) {
    $pdo->prepare("
        UPDATE order_access_tokens 
        SET upsell_converted = 1, upsell_order_id = ? 
        WHERE workspace_id = ? AND external_order_id = ?
    ")->execute([(int)$order['id'], $workspace->id, $externalOrderId]);
}

// 9. Return Response
JsonResponse::send([
    'status'         => 'success',
    'order_id'       => $order['id'],
    'transaction_id' => $transaction['id'],
    'pix'            => [
        'copy_paste' => $charge->copyPaste,
        'qr_base64'  => $charge->qrBase64,
        'amount'     => $price,
        'expires_at' => $charge->expiresAt,
    ],
    'split_preview'  => [
        'saas_amount'     => $saasAmount,
        'partner_amount'  => $partnerAmount,
        'merchant_amount' => $merchantAmount,
    ]
], 201);
