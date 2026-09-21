<?php

// ==============================================================================
// upScale Media Network - API v1: Webhook Oficial de Vendas da Rifa
// ==============================================================================
// Chamado pela plataforma de rifa (Sorteamos, Play55, SorteiMe, WooCommerce, etc.)
// assim que o comprador paga um pedido de cotas.
// ==============================================================================

require_once __DIR__ . '/../bootstrap.php';

use App\Http\JsonResponse;
use App\Repositories\CustomerRepository;
use App\Repositories\OfferRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($request->method() !== 'POST') {
    JsonResponse::error('Método não permitido. Utilize POST.', 405);
}

// 1. Autenticação via Bearer Token ou Header Customizado
$authHeader = $request->header('Authorization') ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
} else {
    $token = $request->header('X-Upscale-Token')
        ?? $request->header('X-Api-Token')
        ?? $request->get('api_token')
        ?? '';
}

// Se fornecido token, valida no workspace
if (!empty($token)) {
    $tokenHash = hash('sha256', $token);
    $wsStmt = $pdo->prepare("SELECT id FROM workspaces WHERE api_token_hash = ? AND is_active = 1 LIMIT 1");
    $wsStmt->execute([$tokenHash]);
    $matchedWsId = (int)$wsStmt->fetchColumn();
    if ($matchedWsId > 0) {
        $targetWsId = $matchedWsId;
    }
}

$targetWsId = $targetWsId ?? ($workspace->id ?? 2);

// 2. Extração Flexível do Payload de Venda da Rifa
$input = $request->all();

$extOrderId = trim((string)(
    $input['order_id'] 
    ?? $input['pedido_id'] 
    ?? $input['id'] 
    ?? $input['data']['id'] 
    ?? $input['order']['id'] 
    ?? uniqid('rifa_')
));

$customerRaw = $input['customer'] ?? $input['buyer'] ?? $input['cliente'] ?? [];
if (!is_array($customerRaw)) $customerRaw = [];

$fullName = trim((string)(
    $customerRaw['name'] 
    ?? $customerRaw['nome'] 
    ?? $input['customer_name'] 
    ?? $input['name'] 
    ?? $input['nome'] 
    ?? 'Cliente'
));

$phone = trim((string)(
    $customerRaw['phone'] 
    ?? $customerRaw['telefone'] 
    ?? $customerRaw['celular'] 
    ?? $input['customer_phone'] 
    ?? $input['phone'] 
    ?? $input['telefone'] 
    ?? ''
));

$cpf = trim((string)(
    $customerRaw['cpf'] 
    ?? $input['customer_cpf'] 
    ?? $input['cpf'] 
    ?? ''
));

$email = trim((string)(
    $customerRaw['email'] 
    ?? $input['customer_email'] 
    ?? $input['email'] 
    ?? ''
));

$amount = (float)(
    $input['amount'] 
    ?? $input['total'] 
    ?? $input['valor'] 
    ?? $input['order']['amount'] 
    ?? 0.00
);

$quotasCount = (int)(
    $input['quotas'] 
    ?? $input['cotas'] 
    ?? $input['tickets'] 
    ?? $input['quantity'] 
    ?? 1
);

$status = strtolower(trim((string)($input['status'] ?? $input['payment_status'] ?? 'paid')));
$isPaid = in_array($status, ['paid', 'pago', 'aprovado', 'confirmed', 'completed', 'liquidado'], true);

// 3. Upsert do Cliente no Banco
$customerRepo = new CustomerRepository($pdo);
$customer = $customerRepo->upsert(
    workspaceId: $targetWsId,
    name: $fullName,
    phone: $phone ?: null,
    cpf: $cpf ?: null,
    email: $email ?: null
);

// 4. Carregar Oferta Booster do Workspace
$offerRepo = new OfferRepository($pdo);
$offer = $offerRepo->defaultActiveForWorkspace($targetWsId);
$theme = [];
if (!empty($offer['theme_config'])) {
    $theme = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
}

$carroA = $input['carro_a'] ?? $theme['carro_a'] ?? 'RANGER';
$carroB = $input['carro_b'] ?? $theme['carro_b'] ?? 'BMW';
$extraBonus = (float)($input['extra_bonus'] ?? $theme['extra_bonus'] ?? 50000.00);
$minOrder = (float)($input['min_order'] ?? $theme['min_order'] ?? 30.00);
$bonusEntitled = ($amount >= $minOrder && $isPaid) ? 1 : 0;

// 5. Gerar Token de Acesso Seguro (24 horas)
$cardAccessToken = 'tk_' . bin2hex(random_bytes(16));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $pdo->prepare("
    INSERT INTO order_access_tokens 
    (token, workspace_id, external_order_id, customer_name, customer_phone, customer_cpf, base_amount, is_paid, carro_a, carro_b, extra_bonus, min_order, bonus_entitled, expires_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $cardAccessToken, $targetWsId, $extOrderId, $fullName, $phone, $cpf, $amount, $isPaid ? 1 : 0,
    $carroA, $carroB, $extraBonus, $minOrder, $bonusEntitled, $expiresAt
]);

// 6. Construir URLs Públicas do Card da Raspadinha
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';

$parts = preg_split('/\s+/', $fullName);
$firstName = ucfirst(mb_strtolower($parts[0] ?? '', 'UTF-8'));

$cardUrl = $scheme . '://' . $host . '/?token=' . urlencode($cardAccessToken) . '&workspace_id=' . $targetWsId;

$iframeSnippet = '<iframe id="upscale-frame" src="' . $cardUrl . '" style="width:100%;border:0;height:820px;" scrolling="no"></iframe>' . "\n" .
'<script>' . "\n" .
'window.addEventListener("message", function(e) {' . "\n" .
'  if (e.data && e.data.tipo === "dx:altura") {' . "\n" .
'    document.getElementById("upscale-frame").style.height = e.data.altura + "px";' . "\n" .
'  }' . "\n" .
'});' . "\n" .
'</script>';

// Log de Auditoria
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
@file_put_contents(
    $logDir . '/upscale-sales-webhooks.log',
    date('c') . " | WS: {$targetWsId} | PED: {$extOrderId} | CLI: {$fullName} | VALOR: R$ {$amount} | TOKEN: {$cardAccessToken}\n",
    FILE_APPEND | LOCK_EX
);

// 7. Retorno Oficial para a Plataforma de Rifa
JsonResponse::send([
    'status'         => 'success',
    'message'        => 'Venda registrada com sucesso na upScale Media Network.',
    'token'          => $cardAccessToken,
    'order_id'       => $extOrderId,
    'card_url'       => $cardUrl,
    'iframe_url'     => $cardUrl,
    'iframe_snippet' => $iframeSnippet,
    'buyer'          => [
        'name'       => $fullName,
        'first_name' => $firstName,
        'phone'      => $phone,
        'cpf'        => $cpf,
    ],
    'bonus'          => [
        'carro_a'      => $carroA,
        'carro_b'      => $carroB,
        'extra_amount' => $extraBonus,
        'min_order'    => $minOrder,
        'eligible'     => (bool)$bonusEntitled,
        'badge'        => $isPaid ? 'COMPRA CONFIRMADA' : 'AGUARDANDO PAGAMENTO'
    ],
    'offer'          => [
        'id'          => $offer ? (int)$offer['id'] : 1,
        'title'       => $offer['title'] ?? 'Booster 110X',
        'price'       => (float)($offer['price'] ?? 90.00),
        'quota_count' => (int)($offer['quota_count'] ?? 110),
    ]
], 200);
