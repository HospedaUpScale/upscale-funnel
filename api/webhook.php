<?php

// ==============================================================================
// upScale Media Network - Webhook Receiver Universal de Pagamento Pix
// ==============================================================================
// Suporta: Infopago, Mercado Pago, Asaas, Efí (Gerencianet), EzzePay, SuitPay,
// e Webhooks Customizados de Plataformas de Rifas (Sorteamos, Play55, SorteiMe).
// ==============================================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Webhook-Token, X-Signature');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/autoload.php';
$config = require __DIR__ . '/../config/config.php';

use App\Database\Connection;
use App\Repositories\ConversionEventRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OfferRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;

$pdo = Connection::make();
$txRepo = new TransactionRepository($pdo);
$orderRepo = new OrderRepository($pdo);
$eventRepo = new ConversionEventRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$offerRepo = new OfferRepository($pdo);

// 1. Diagnóstico / Healthcheck se for GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'status';
    if ($action === 'logs') {
        $logFile = __DIR__ . '/logs/webhooks.log';
        $logs = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $recent = array_slice(array_reverse($logs), 0, 50);
        $parsed = array_map(fn($line) => json_decode($line, true) ?: ['raw' => $line], $recent);
        echo json_encode(['status' => 'success', 'total_logs' => count($logs), 'recent_webhooks' => $parsed], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status'             => 'online',
        'service'            => 'upScale Media Network Pix Webhook Gateway',
        'endpoint'           => '/api/webhook.php',
        'methods_accepted'   => ['POST'],
        'gateways_supported' => ['Infopago', 'Mercado Pago', 'Asaas', 'Efí (Gerencianet)', 'EzzePay', 'SuitPay', 'Sorteamos / DX Custom'],
        'timestamp'          => date('c'),
        'instructions'       => 'Envie um HTTP POST com payload JSON contendo o status de pagamento e o identificador (txid, order_id ou id).'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Leitura do Payload Bruto
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    // Tenta ler form-data se não for JSON puro
    $payload = !empty($_POST) ? $_POST : [];
}

$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// 3. Auditoria em arquivo de logs
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip'        => $clientIp,
    'method'    => $_SERVER['REQUEST_METHOD'],
    'query'     => $_GET,
    'headers'   => [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'signature'  => $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_SIGNATURE'] ?? '',
        'token'      => $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''
    ],
    'payload'   => $payload
];

$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0777, true);
}
@file_put_contents($logsDir . '/webhooks.log', json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

// 4. Extração Inteligente de Identificadores e Status
$txid = $payload['txid'] 
    ?? $payload['gateway_tx_id'] 
    ?? $payload['payment']['id'] 
    ?? $payload['data']['id'] 
    ?? $payload['id'] 
    ?? $_GET['txid'] 
    ?? null;

$externalOrderId = $payload['order_id'] 
    ?? $payload['pedido_id'] 
    ?? $payload['external_reference'] 
    ?? $payload['external_id'] 
    ?? $payload['payment']['externalReference'] 
    ?? $_GET['order_id'] 
    ?? null;

$statusString = strtolower((string)(
    $payload['status'] 
    ?? $payload['event'] 
    ?? $payload['action'] 
    ?? $payload['payment']['status'] 
    ?? $payload['situacao'] 
    ?? 'paid' // default se o endpoint for acionado por trigger de sucesso
));

// Lista de termos que confirmam pagamento
$paidKeywords = [
    'paid', 'approved', 'pago', 'concluido', 'completed', 
    'payment_received', 'payment.updated', 'settled', 
    'confirmed', 'sucesso', 'pix_received', 'pix.received'
];

$isPaid = false;
foreach ($paidKeywords as $keyword) {
    if (strpos($statusString, $keyword) !== false) {
        $isPaid = true;
        break;
    }
}

// 5. Localização da Transação e do Pedido no Banco
$transaction = null;
$order = null;

if (!empty($txid)) {
    $transaction = $txRepo->findByGatewayTxId((string)$txid);
    if (!$transaction && is_numeric($txid)) {
        $transaction = $txRepo->find((int)$txid);
    }
}

if (!$transaction && !empty($externalOrderId)) {
    $order = $orderRepo->findByExternalOrderId((string)$externalOrderId);
    if (!$order && is_numeric($externalOrderId)) {
        $order = $orderRepo->find((int)$externalOrderId);
    }
    if ($order) {
        $transaction = $txRepo->findByOrderId((int)$order['id']);
    }
}

// Se encontrou transação mas não o pedido, carrega o pedido
if ($transaction && !$order) {
    $order = $orderRepo->find((int)$transaction['order_id']);
}

// Se não localizou nem por txid nem por order_id, devolve 404 / informativa
if (!$transaction || !$order) {
    http_response_code(404);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Nenhuma transação ou pedido correspondente localizado no sistema.',
        'searched_identifiers' => [
            'txid'              => $txid,
            'external_order_id' => $externalOrderId
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 6. Processamento da Liquidação se Confirmado
$workspaceId = (int)$order['workspace_id'];

if ($isPaid) {
    // 6.1 Se já estiver pago, retorna idempotente com sucesso
    if ($transaction['status'] === 'paid') {
        echo json_encode([
            'status'         => 'success',
            'message'        => 'Pagamento já processado anteriormente (idempotente).',
            'order_id'       => (int)$order['id'],
            'transaction_id' => (int)$transaction['id'],
            'settled_at'     => $transaction['paid_at']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 6.2 Marca como pago no Banco de Dados
    $txRepo->markPaid((int)$transaction['id']);
    $orderRepo->markPaid((int)$order['id']);

    // 6.3 Registra evento de conversão
    $eventRepo->log($workspaceId, (int)$order['id'], 'pix_paid', [
        'transaction_id' => (int)$transaction['id'],
        'gateway'        => $transaction['gateway'] ?? 'webhook',
        'amount'         => (float)$transaction['amount'],
        'source'         => 'webhook',
        'webhook_ip'     => $clientIp
    ]);

    // 6.4 Disparo de Webhook de Entrega de Cotas (Postback para a Plataforma da Rifa)
    $customer = !empty($order['customer_id']) ? $customerRepo->find((int)$order['customer_id']) : null;
    $offer = !empty($order['offer_id']) ? $offerRepo->find((int)$order['offer_id'], $workspaceId) : null;
    
    $quotasToAdd = 110;
    if ($offer && !empty($offer['theme_config'])) {
        $cfg = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
        if (!empty($cfg['quotas_extra'])) {
            $quotasToAdd = (int)$cfg['quotas_extra'];
        }
    }

    $deliveryPayload = [
        'event'            => 'upsell.quotas_paid',
        'order_id'         => (int)$order['id'],
        'external_order_id'=> $order['external_order_id'],
        'customer'         => [
            'name'  => $customer['name'] ?? 'Cliente',
            'phone' => $customer['phone'] ?? '',
            'cpf'   => $customer['cpf'] ?? ''
        ],
        'quotas_awarded'   => $quotasToAdd,
        'amount_paid'      => (float)$order['total_amount'],
        'paid_at'          => date('Y-m-d H:i:s'),
        'signature'        => hash_hmac('sha256', (string)$order['id'] . '|' . $order['external_order_id'], 'upscale_secret_delivery_key')
    ];

    // Se houver URL de entrega configurada na oferta ou workspace
    $deliveryUrl = null;
    if ($offer && !empty($offer['theme_config'])) {
        $cfg = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
        $deliveryUrl = $cfg['delivery_webhook_url'] ?? $cfg['postback_url'] ?? null;
    }

    $postbackResult = 'no_url_configured';
    if (!empty($deliveryUrl) && filter_var($deliveryUrl, FILTER_VALIDATE_URL)) {
        // Envia requisição HTTP POST para a plataforma da rifa creditar as cotas
        $ch = curl_init($deliveryUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_POSTFIELDS     => json_encode($deliveryPayload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Source: upScale-Media-Network',
                'X-Signature: ' . $deliveryPayload['signature']
            ]
        ]);
        $postbackResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $postbackResult = ['status_code' => $httpCode, 'response' => substr((string)$postbackResponse, 0, 300)];
    }

    echo json_encode([
        'status'             => 'success',
        'message'            => 'Pagamento Pix liquidado com sucesso!',
        'order_id'           => (int)$order['id'],
        'transaction_id'     => (int)$transaction['id'],
        'amount'             => (float)$transaction['amount'],
        'quotas_awarded'     => $quotasToAdd,
        'customer'           => [
            'name'  => $customer['name'] ?? null,
            'phone' => $customer['phone'] ?? null
        ],
        'postback_delivery'  => $postbackResult
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 7. Se status não for paid (ex: pending, expired, failed)
echo json_encode([
    'status'         => 'ignored',
    'message'        => "Status '{$statusString}' recebido. Nenhuma ação necessária.",
    'order_id'       => (int)$order['id'],
    'transaction_id' => (int)$transaction['id']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
