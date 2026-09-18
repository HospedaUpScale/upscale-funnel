<?php

require_once __DIR__ . '/../src/autoload.php';
use App\Database\Connection;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;

$pdo = Connection::make();
$txRepo = new TransactionRepository($pdo);
$orderRepo = new OrderRepository($pdo);

echo "=== TESTANDO WEBHOOK DE PAGAMENTO PIX ===\n\n";

// 1. Teste de GET (Healthcheck)
$ch = curl_init('http://127.0.0.1:8000/api/webhook.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "1. Healthcheck GET: HTTP {$code}\n";
echo "Resposta: " . substr($res, 0, 150) . "...\n\n";

// 2. Criar uma cobrança pendente para testar o Webhook
$ch = curl_init('http://127.0.0.1:8000/api/pix-upsell.php?workspace=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name'     => 'Carlos Teste Webhook',
    'phone'    => '5511988887777',
    'cpf'      => '12345678901',
    'order_id' => 'ord_webhook_test_' . time()
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resUpsell = curl_exec($ch);
curl_close($ch);

$upsellData = json_decode($resUpsell, true);
$orderId = $upsellData['order_id'] ?? null;
$txId = $upsellData['transaction_id'] ?? null;

echo "2. Cobrança criada para teste:\n";
echo "Order ID: {$orderId} | Transaction ID: {$txId}\n\n";

if (!$orderId || !$txId) {
    echo "ERRO ao criar cobrança.\n";
    exit(1);
}

// 3. Simular Webhook disparado pelo Gateway (ex: Infopago / Asaas / Mercado Pago)
$webhookPayload = [
    'event'       => 'PAYMENT_RECEIVED',
    'status'      => 'PAID',
    'id'          => (string)$txId,
    'order_id'    => $orderId,
    'amount'      => 10.00,
    'paid_at'     => date('Y-m-d H:i:s')
];

$ch = curl_init('http://127.0.0.1:8000/api/webhook.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resWebhook = curl_exec($ch);
$codeWebhook = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "3. Disparo de Webhook (POST /api/webhook.php): HTTP {$codeWebhook}\n";
echo "Resposta do Webhook: {$resWebhook}\n\n";

// 4. Teste de Idempotência (disparar o mesmo webhook novamente)
$ch = curl_init('http://127.0.0.1:8000/api/webhook.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resIdempotent = curl_exec($ch);
curl_close($ch);

echo "4. Teste de Idempotência (Webhook repetido):\n";
echo "Resposta: {$resIdempotent}\n\n";

// 5. Verificar no Banco se a transação e o pedido foram marcados como 'paid'
$tx = $txRepo->find($txId);
$order = $orderRepo->find($orderId);

echo "5. Validação no Banco de Dados:\n";
echo "Status da Transação: " . ($tx['status'] ?? 'N/A') . " (Esperado: paid)\n";
echo "Status do Pedido: " . ($order['status'] ?? 'N/A') . " (Esperado: paid)\n";
echo "Data de Pagamento: " . ($tx['paid_at'] ?? 'N/A') . "\n\n";

if ($tx['status'] === 'paid' && $order['status'] === 'paid') {
    echo ">>> SUCESSO TOTAL NO WEBHOOK! <<<\n";
} else {
    echo ">>> FALHA NA LIQUIDAÇÃO DO WEBHOOK <<<\n";
}
