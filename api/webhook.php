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
use App\Repositories\WorkspaceRepository;
use App\Security\WebhookAuth;

$pdo = Connection::make();
$webhookAuth = new WebhookAuth(new WorkspaceRepository($pdo), (string) ($config['webhook_secret'] ?? ''));
$txRepo = new TransactionRepository($pdo);
$orderRepo = new OrderRepository($pdo);
$eventRepo = new ConversionEventRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$offerRepo = new OfferRepository($pdo);

// 1. Healthcheck se for GET (os logs de webhook não são mais expostos publicamente)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status'             => 'online',
        'service'            => 'upScale Media Network Pix Webhook Gateway',
        'endpoint'           => '/api/webhook.php',
        'methods_accepted'   => ['POST'],
        'gateways_supported' => ['Infopago', 'Mercado Pago', 'Asaas', 'Efí (Gerencianet)', 'EzzePay', 'SuitPay', 'Sorteamos / DX Custom'],
        'timestamp'          => date('c'),
        'instructions'       => 'Envie um HTTP POST autenticado (Authorization: Bearer <token>, X-Webhook-Token ou ?token=) com payload JSON contendo o status de pagamento e o identificador (txid, order_id ou id).'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido. Utilize POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1.1 Autenticação obrigatória: sem token, nada é processado.
$webhookToken = WebhookAuth::extractToken();
if ($webhookToken === '') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token de webhook ausente.'], JSON_UNESCAPED_UNICODE);
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

// 3. Auditoria em arquivo de logs (sem tokens e com CPF mascarado)
function webhook_redact(mixed $data): mixed
{
    if (!is_array($data)) {
        return $data;
    }
    foreach ($data as $key => $value) {
        $k = strtolower((string) $key);
        if (in_array($k, ['token', 'api_token', 'access_token', 'secret', 'password'], true)) {
            $data[$key] = '[removido]';
        } elseif (in_array($k, ['cpf', 'document', 'documento', 'cpf_cnpj', 'tax_id'], true) && is_scalar($value)) {
            $digits = preg_replace('/\D/', '', (string) $value);
            $data[$key] = $digits === '' ? '' : str_repeat('*', max(0, strlen($digits) - 2)) . substr($digits, -2);
        } else {
            $data[$key] = webhook_redact($value);
        }
    }
    return $data;
}

$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip'        => $clientIp,
    'method'    => $_SERVER['REQUEST_METHOD'],
    'query'     => webhook_redact($_GET),
    'headers'   => [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'signature'  => $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_SIGNATURE'] ?? '',
        'has_token'  => true,
    ],
    'payload'   => webhook_redact($payload)
];

$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0777, true);
}
@file_put_contents($logsDir . '/webhooks.log', json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

// 3.1 Token precisa ser o segredo global ou o token de API de algum workspace ativo.
if (!$webhookAuth->isGlobalSecret($webhookToken) && !$webhookAuth->workspaceForToken($webhookToken)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token de webhook inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

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

// O status precisa vir explícito: ausência de status NUNCA é tratada como pagamento.
$statusString = strtolower(trim((string)(
    $payload['status']
    ?? $payload['payment']['status']
    ?? $payload['situacao']
    ?? $payload['event']
    ?? $payload['action']
    ?? ''
)));

// Termos que confirmam pagamento — comparação exata, para que "unpaid" ou "nao_pago"
// não sejam confundidos com "paid"/"pago". "payment.updated" não confirma pagamento
// (pode ser estorno ou cancelamento), por isso não está na lista.
$paidKeywords = [
    'paid', 'approved', 'pago', 'concluido', 'completed',
    'payment_received', 'settled',
    'confirmed', 'sucesso', 'pix_received', 'pix.received'
];

$isPaid = in_array($statusString, $paidKeywords, true);

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

// Se não localizou nem por txid nem por order_id — ou o token não pertence ao workspace
// do pedido — devolve 404 (mesma resposta, para não revelar pedidos de outros clientes).
if (!$transaction || !$order
    || !$webhookAuth->authorizesWorkspace($webhookToken, (int) $order['workspace_id'])) {
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
    ];

    // Assinatura HMAC com segredo configurado no servidor (POSTBACK_SECRET).
    // Sem segredo configurado, o postback segue sem assinatura em vez de usar uma chave pública.
    $postbackSecret = (string) ($config['postback_secret'] ?? '');
    if ($postbackSecret !== '') {
        $deliveryPayload['signature'] = hash_hmac(
            'sha256',
            (string)$order['id'] . '|' . $order['external_order_id'],
            $postbackSecret
        );
    }

    // Se houver URL de entrega configurada na oferta ou workspace
    $deliveryUrl = null;
    if ($offer && !empty($offer['theme_config'])) {
        $cfg = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
        $deliveryUrl = $cfg['delivery_webhook_url'] ?? $cfg['postback_url'] ?? null;
    }

    $postbackResult = 'no_url_configured';
    if (!empty($deliveryUrl) && filter_var($deliveryUrl, FILTER_VALIDATE_URL)) {
        // Envia requisição HTTP POST para a plataforma da rifa creditar as cotas
        $postbackHeaders = [
            'Content-Type: application/json',
            'X-Source: upScale-Media-Network',
        ];
        if (!empty($deliveryPayload['signature'])) {
            $postbackHeaders[] = 'X-Signature: ' . $deliveryPayload['signature'];
        }

        $ch = curl_init($deliveryUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_POSTFIELDS     => json_encode($deliveryPayload),
            CURLOPT_HTTPHEADER     => $postbackHeaders,
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
