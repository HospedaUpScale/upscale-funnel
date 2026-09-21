<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\JsonResponse;
use App\Repositories\OfferRepository;

header('Cache-Control: no-cache, no-store, must-revalidate');

function sanitizeName(?string $fullName): array
{
    $name = trim($fullName ?? '');
    if ($name === '') {
        return ['full' => '', 'first' => ''];
    }
    $parts = preg_split('/\s+/', $name);
    $first = $parts[0] ?? '';
    $firstClean = mb_convert_case(mb_strtolower($first, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    return [
        'full' => $name,
        'first' => $firstClean
    ];
}

// 1. Resolve Workspace
$targetWsId = null;
if (!empty($request->get('workspace_id'))) {
    $targetWsId = (int)$request->get('workspace_id');
} elseif (!empty($request->get('__workspace'))) {
    $targetWsId = (int)$request->get('__workspace');
} elseif (!empty($workspace)) {
    $targetWsId = $workspace->id;
} else {
    $targetWsId = 2;
}

// Carrega a oferta Booster ativa do workspace
$offerRepo = new OfferRepository($pdo);
$offer = $offerRepo->defaultActiveForWorkspace($targetWsId);
if (!$offer) {
    $offer = [
        'id' => 1,
        'workspace_id' => $targetWsId,
        'title' => 'Booster 110X',
        'subtitle' => 'Kit Bobzão Bitruck + Ford F250',
        'price' => 90.00,
        'quota_count' => 110,
        'theme_config' => null,
    ];
}

$themeConfig = [];
if (!empty($offer['theme_config'])) {
    $decoded = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
    if (is_array($decoded)) $themeConfig = $decoded;
}

// ─────────────────────────────────────────────────────────────────────────────
// POST: Registrar Compra da Rifa e Gerar Token de Acesso ao Card
// ─────────────────────────────────────────────────────────────────────────────
if ($request->method() === 'POST') {
    $input = $request->all();
    $wsId = (int)($input['workspace_id'] ?? $targetWsId);
    $extOrderId = trim((string)($input['order_id'] ?? $input['pedido_id'] ?? uniqid('rifa_')));
    $rawName = trim((string)($input['name'] ?? $input['customer_name'] ?? $input['nome'] ?? ''));
    $phone = trim((string)($input['phone'] ?? $input['customer_phone'] ?? $input['telefone'] ?? ''));
    $cpf = trim((string)($input['cpf'] ?? $input['customer_cpf'] ?? ''));
    $baseAmount = (float)($input['amount'] ?? $input['valor'] ?? $input['total'] ?? 0.00);
    $status = strtolower(trim((string)($input['status'] ?? 'paid')));
    $isPaid = in_array($status, ['paid', 'pago', 'aprovado', 'confirmed'], true) ? 1 : 0;

    $carroA = trim((string)($input['carro_a'] ?? $themeConfig['carro_a'] ?? 'RANGER'));
    $carroB = trim((string)($input['carro_b'] ?? $themeConfig['carro_b'] ?? 'BMW'));
    $extraBonus = (float)($input['extra_bonus'] ?? $themeConfig['extra_bonus'] ?? 50000.00);
    $minOrder = (float)($input['min_order'] ?? $themeConfig['min_order'] ?? 30.00);
    $bonusEntitled = ($baseAmount >= $minOrder && $isPaid) ? 1 : 0;

    $token = 'tk_' . bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO order_access_tokens 
            (token, workspace_id, external_order_id, customer_name, customer_phone, customer_cpf, base_amount, is_paid, carro_a, carro_b, extra_bonus, min_order, bonus_entitled, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $token, $wsId, $extOrderId, $rawName, $phone, $cpf, $baseAmount, $isPaid,
            $carroA, $carroB, $extraBonus, $minOrder, $bonusEntitled, $expiresAt
        ]);
    } catch (PDOException $e) {
        JsonResponse::error('Falha ao registrar token do pedido.', 500);
    }

    $nameData = sanitizeName($rawName);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $cardUrl = $scheme . '://' . $host . '/?token=' . urlencode($token);

    JsonResponse::success([
        'message' => 'Token do pedido gerado com sucesso.',
        'token' => $token,
        'order_id' => $extOrderId,
        'card_url' => $cardUrl,
        'iframe_snippet' => '<iframe id="upscale-frame" src="' . $cardUrl . '" style="width:100%;border:0;height:820px;" scrolling="no"></iframe>',
        'buyer' => [
            'name' => $nameData['full'],
            'first_name' => $nameData['first'],
            'phone' => $phone
        ],
        'bonus' => [
            'carro_a' => $carroA,
            'carro_b' => $carroB,
            'extra_amount' => $extraBonus,
            'min_order' => $minOrder,
            'eligible' => (bool)$bonusEntitled,
            'badge' => $isPaid ? 'COMPRA CONFIRMADA' : 'AGUARDANDO PAGAMENTO'
        ]
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// GET: Consulta de Contexto para o Card da Raspadinha / Upsell
// ─────────────────────────────────────────────────────────────────────────────
$token = trim((string)$request->get('token'));
$orderId = trim((string)($request->get('order_id') ?? $request->get('pedido_id') ?? $request->get('id')));
$action = trim((string)$request->get('action'));

$foundToken = null;

// 1. Busca por token emitido
if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE token = ? AND expires_at > CURRENT_TIMESTAMP LIMIT 1");
    $stmt->execute([$token]);
    $foundToken = $stmt->fetch();
}

// 2. Fallback: busca por order_id se não veio token
if (!$foundToken && $orderId !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE workspace_id = ? AND external_order_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$targetWsId, $orderId]);
    $foundToken = $stmt->fetch();
}

// Se for auditoria de ganhador (check_winner)
if ($action === 'check_winner') {
    if (!$foundToken) {
        JsonResponse::error('Pedido ou token não encontrado para auditoria.', 404);
    }
    JsonResponse::success([
        'order_id' => $foundToken['external_order_id'],
        'customer_name' => $foundToken['customer_name'],
        'customer_phone' => $foundToken['customer_phone'],
        'base_amount' => (float)$foundToken['base_amount'],
        'is_paid' => (bool)$foundToken['is_paid'],
        'carro_escolhido_a' => $foundToken['carro_a'],
        'carro_escolhido_b' => $foundToken['carro_b'],
        'extra_bonus' => (float)$foundToken['extra_bonus'],
        'min_order' => (float)$foundToken['min_order'],
        'bonus_entitled' => (bool)$foundToken['bonus_entitled'],
        'upsell_converted' => (bool)$foundToken['upsell_converted'],
        'status_ganhador' => $foundToken['bonus_entitled']
            ? 'ELEGÍVEL AO BÔNUS EXTRA DE R$ ' . number_format((float)$foundToken['extra_bonus'], 2, ',', '.')
            : 'NÃO ELEGÍVEL AO BÔNUS (NÃO ATINGIU O VALOR MÍNIMO)'
    ]);
}

// 3. Montar Resposta de Contexto do Card
if ($foundToken) {
    $nameData = sanitizeName($foundToken['customer_name']);
    $isPaid = (bool)$foundToken['is_paid'];
    $isEligible = (bool)$foundToken['bonus_entitled'];

    JsonResponse::success([
        'authenticated' => true,
        'source' => 'api_token',
        'confirmed' => $isPaid,
        'badge' => $isPaid ? 'COMPRA CONFIRMADA' : 'PEDIDO PENDENTE',
        'buyer' => [
            'name' => $nameData['full'],
            'first_name' => $nameData['first'],
            'phone' => $foundToken['customer_phone'] ?? '',
        ],
        'order' => [
            'id' => $foundToken['external_order_id'],
            'amount' => (float)$foundToken['base_amount'],
            'paid' => $isPaid,
            'created_at' => $foundToken['created_at'],
        ],
        'booster' => [
            'offer_id' => (int)$offer['id'],
            'title' => $offer['title'],
            'price' => (float)$offer['price'],
            'quotas' => (int)($offer['quota_count'] ?? 110),
            'carro_a' => $foundToken['carro_a'] ?: 'RANGER',
            'carro_b' => $foundToken['carro_b'] ?: 'BMW',
            'extra_bonus' => (float)$foundToken['extra_bonus'],
            'min_order' => (float)$foundToken['min_order'],
            'bonus_entitled' => $isEligible,
            'timer_minutes' => (float)($themeConfig['timer_minutes'] ?? 3),
        ]
    ]);
}

// 4. Fallback: Lê dados diretamente da URL (Caso a rifa passe na query string sem token)
$urlName = trim((string)($request->get('nome') ?? $request->get('name') ?? $request->get('cliente') ?? ''));
$urlPhone = trim((string)($request->get('telefone') ?? $request->get('phone') ?? ''));
$urlAmount = (float)($request->get('valor') ?? $request->get('total') ?? $request->get('amount') ?? 0.00);
$urlMinimo = (float)($themeConfig['min_order'] ?? 30.00);
$urlEligible = ($urlAmount <= 0 || $urlAmount >= $urlMinimo);

$nameData = sanitizeName($urlName);

JsonResponse::success([
    'authenticated' => false,
    'source' => $urlName !== '' ? 'url_fallback' : 'default_fallback',
    'confirmed' => $urlName !== '', // Se veio nome na URL da tela de obrigado, trata como confirmado
    'badge' => $urlName !== '' ? 'COMPRA CONFIRMADA' : 'OFERTA EXCLUSIVA',
    'buyer' => [
        'name' => $nameData['full'],
        'first_name' => $nameData['first'],
        'phone' => $urlPhone,
    ],
    'order' => [
        'id' => $orderId ?: 'direto',
        'amount' => $urlAmount,
        'paid' => true,
    ],
    'booster' => [
        'offer_id' => (int)$offer['id'],
        'title' => $offer['title'],
        'price' => (float)$offer['price'],
        'quotas' => (int)($offer['quota_count'] ?? 110),
        'carro_a' => $themeConfig['carro_a'] ?? 'RANGER',
        'carro_b' => $themeConfig['carro_b'] ?? 'BMW',
        'extra_bonus' => (float)($themeConfig['extra_bonus'] ?? 50000.00),
        'min_order' => $urlMinimo,
        'bonus_entitled' => $urlEligible,
        'timer_minutes' => (float)($themeConfig['timer_minutes'] ?? 3),
    ]
]);
