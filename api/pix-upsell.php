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

// Saving an offer draft must not change the amount charged on published pages.
$published = json_decode($offer['published_config'] ?? '', true) ?: [];
$price = (float)($published['price'] ?? $offer['price']);
$offerTitle = $published['title'] ?? $offer['title'];

// 2. Resolve Customer
// Card aberto pelo token do webhook de vendas: os dados confiáveis do pedido da rifa vêm do servidor
// (nome, telefone, CPF e número do pedido), não da URL.
$cardToken = null;
if (!empty($input['token'])) {
    $stTok = $pdo->prepare('SELECT * FROM order_access_tokens WHERE token = ? AND workspace_id = ? AND expires_at > CURRENT_TIMESTAMP LIMIT 1');
    $stTok->execute([trim((string)$input['token']), $workspace->id]);
    $cardToken = $stTok->fetch(PDO::FETCH_ASSOC) ?: null;
}
// Mesmo card com um Pix ainda pendente desta oferta: devolve o mesmo Pix em vez de gerar outro
// (clicar de novo em "Ativar" não pode gerar uma segunda cobrança para o comprador).
if ($cardToken && !empty($cardToken['upsell_order_id'])) {
    $prevOrder = (new OrderRepository($pdo))->find((int)$cardToken['upsell_order_id']);
    $prevTx = $prevOrder ? (new TransactionRepository($pdo))->findByOrderId((int)$prevOrder['id']) : null;
    if ($prevOrder && $prevTx
        && (int)$prevOrder['workspace_id'] === $workspace->id
        && (int)$prevOrder['offer_id'] === (int)$offer['id']
        && $prevTx['status'] === 'pending'
        && !empty($prevTx['expires_at']) && strtotime($prevTx['expires_at']) > time() + 30
        && abs((float)$prevTx['amount'] - $price) < 0.005) {
        JsonResponse::send([
            'status'         => 'success',
            'reused'         => true,
            'order_id'       => (int)$prevOrder['id'],
            'transaction_id' => (int)$prevTx['id'],
            'pix'            => [
                'copy_paste' => $prevTx['pix_copy_paste'],
                'qr_base64'  => $prevTx['pix_qr_base64'],
                'amount'     => (float)$prevTx['amount'],
                'expires_at' => $prevTx['expires_at'],
            ],
            'test_mode'      => \App\Support\PixTestMode::active($pdo, $workspace->id)
                && \App\Support\PixTestMode::isTestTransaction($prevTx),
        ]);
    }
}

$customerName = trim($input['name'] ?? $input['customer']['name'] ?? '');
if (($customerName === '' || strcasecmp($customerName, 'Cliente') === 0) && !empty($cardToken['customer_name'])) $customerName = $cardToken['customer_name'];
if ($customerName === '') $customerName = 'Cliente';
$customerPhone = trim($input['phone'] ?? $input['telefone'] ?? $input['customer']['phone'] ?? '');
if ($customerPhone === '' && !empty($cardToken['customer_phone'])) $customerPhone = $cardToken['customer_phone'];
$customerCpf = trim($input['cpf'] ?? $input['customer']['cpf'] ?? '');
if ($customerCpf === '' && !empty($cardToken['customer_cpf'])) $customerCpf = $cardToken['customer_cpf'];
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
$externalOrderId = $input['order_id'] ?? $input['pedido_id'] ?? ($cardToken['external_order_id'] ?? null) ?? uniqid('ord_');

// Canal de origem da venda (rastreamento). Valores conhecidos:
// offer_link (link exclusivo /o/{slug}), card_link (link do card pós-venda),
// funnel (funil/pressel), raffle_webhook, direct (fallback).
$allowedSources = ['offer_link', 'card_link', 'funnel', 'raffle_webhook', 'direct'];
$orderSource = strtolower(trim((string)($input['source'] ?? '')));
if (!in_array($orderSource, $allowedSources, true)) {
    // Sem origem explícita: infere pelo que veio no payload.
    if (!empty($input['offer_slug'])) {
        $orderSource = 'offer_link';
    } elseif (!empty($input['token'])) {
        $orderSource = 'card_link';
    } else {
        $orderSource = 'direct';
    }
}
$orderSourceRef = trim((string)($input['source_ref'] ?? $input['offer_slug'] ?? $input['token'] ?? ''));
$orderSourceRef = $orderSourceRef !== '' ? mb_substr($orderSourceRef, 0, 180) : null;

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
    'source'            => $orderSource,
    'source_ref'        => $orderSourceRef,
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
    // Enquanto não houver gateway real integrado, toda cobrança sai do gateway de demonstração.
    'gateway'             => $gateway instanceof MockPixGateway ? 'mock' : ($workspace->gatewayProvider ?: 'mock'),
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
], ['offer_id' => (int)$offer['id']]);

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
    // Pix de teste ligado no workspace: a janela do Pix mostra "Simular pagamento aprovado".
    'test_mode'      => \App\Support\PixTestMode::active($pdo, $workspace->id)
        && \App\Support\PixTestMode::isTestTransaction($transaction),
    'split_preview'  => [
        'saas_amount'     => $saasAmount,
        'partner_amount'  => $partnerAmount,
        'merchant_amount' => $merchantAmount,
    ]
], 201);
