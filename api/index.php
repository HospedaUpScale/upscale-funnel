<?php
// ==============================================================================
// DX HUB / DX MÍDIA EMULADOR - API MULTI-TENANT DE UPSELL PÓS-VENDA
// Compatível com plataformas de Rifa (Sorteamos / Play55 / SorteiMe)
// ==============================================================================

require_once __DIR__ . '/bootstrap.php';

use App\Repositories\CampaignRepository;
use App\Repositories\OfferRepository;

// 1. Carrega dados do Workspace (Campanha e Oferta ativa do banco)
$campaignRepo = new CampaignRepository($pdo);
$offerRepo = new OfferRepository($pdo);

$campaign = $campaignRepo->defaultForWorkspace($workspace->id);
$offer = $offerRepo->defaultActiveForWorkspace($workspace->id);

$premio = $offer['title'] ?? 'Booster 110X';
$campanhaNome = $offer['subtitle'] ?? ($campaign['title'] ?? 'Kit Bobzão Bitruck + Ford F250');
$preco = (float)($offer['price'] ?? 90.00);
$cotas = (int)($offer['quota_count'] ?? 110);
$checkoutUrl = $offer['legacy_checkout_url'] ?? 'https://sinceropremios.com/';

// 2. Base URL do Card (auto-detectada por Workspace / Host)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'oferta.vendedorsincero.pro';
$currentScriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php');
$parentDir = dirname($currentScriptDir);
if ($parentDir === '/' || $parentDir === '\\' || $parentDir === '.') {
    $parentDir = '';
}
$autoCardBase = $scheme . '://' . $host . ($parentDir ? $parentDir . '/' : '/');
$cardBaseUrl = getenv('CARD_BASE_URL') ?: $autoCardBase;

// 3. Autenticação e Token da Plataforma de Rifa
$authHeader = $request->header('Authorization') ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
} else {
    $token = $request->header('X-Api-Token') 
        ?? $request->header('Token') 
        ?? $request->get('token') 
        ?? '';
}

$tokenValid = false;
if (!empty($token)) {
    $tokenHash = hash('sha256', $token);
    $tokenValid = hash_equals($workspace->apiTokenHash, $tokenHash);
}

// Enforce se configurado
if ($config['enforce_api_token'] && !$tokenValid) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Token de API inválido ou não autorizado para este workspace'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Captura e Higiene dos Dados do Comprador
$input = $request->all();
$nomeBruto = $input['customer']['name'] 
    ?? $input['buyer']['name'] 
    ?? $input['cliente']['nome']
    ?? $input['nome'] 
    ?? $input['name'] 
    ?? $input['customer_name'] 
    ?? '';

$telefone = $input['customer']['phone'] 
    ?? $input['buyer']['phone']
    ?? $input['cliente']['telefone']
    ?? $input['phone'] 
    ?? $input['telefone'] 
    ?? '';

$orderId = $input['order_id'] 
    ?? $input['pedido_id'] 
    ?? $input['id'] 
    ?? uniqid('ord_');

$primeiroNome = '';
if (!empty($nomeBruto)) {
    $partes = preg_split('/\s+/', trim($nomeBruto));
    $primeiroNome = ucfirst(mb_strtolower($partes[0], 'UTF-8'));
}

// 5. Monta a URL do Card para o Iframe
$cardUrl = rtrim($cardBaseUrl, '/') . '/index.html';
$queryParams = [];
if (!empty($primeiroNome)) {
    $queryParams['nome'] = $primeiroNome;
}
if (!empty($orderId)) {
    $queryParams['order_id'] = $orderId;
}
if (!empty($input['__workspace'])) {
    $queryParams['__workspace'] = $input['__workspace'];
}
if (!empty($offer['vturb_player_id'])) {
    $queryParams['vturb'] = $offer['vturb_player_id'];
}
if (!empty($offer['vturb_account_id'])) {
    $queryParams['vturb_account'] = $offer['vturb_account_id'];
}
if (!empty($queryParams)) {
    $cardUrl .= '?' . http_build_query($queryParams);
}

// 6. Registro de Auditoria / Log seguro por Workspace
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/' . $workspace->id . '.pedidos.log';

$logData = [
    'timestamp'    => date('Y-m-d H:i:s'),
    'ip'           => $_SERVER['REMOTE_ADDR'] ?? '',
    'method'       => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'uri'          => $_SERVER['REQUEST_URI'] ?? '',
    'token'        => $token ? substr($token, 0, 6) . '...' : 'sem-token',
    'token_valid'  => $tokenValid,
    'workspace_id' => $workspace->id,
    'workspace'    => $workspace->slug,
    'comprador'    => $primeiroNome ?: 'nao-informado',
    'telefone'     => $telefone ?: '-',
    'order_id'     => $orderId
];
@file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);

// 7. Determina se a resposta deve ser JSON (API) ou HTML (Navegador)
$uri = $_SERVER['REQUEST_URI'] ?? '';
$accept = $request->header('Accept') ?? '';
$isApiRoute = strpos($uri, 'upsell') !== false 
    || strpos($uri, 'health') !== false 
    || strpos($uri, 'status') !== false
    || strpos($uri, 'offers') !== false
    || $_SERVER['REQUEST_METHOD'] === 'POST'
    || strpos($accept, 'application/json') !== false
    || $request->get('format') === 'json';

// ==============================================================================
// RESPOSTA EM FORMATO JSON (QUANDO A PLATAFORMA DA RIFA CHAMA A API)
// ==============================================================================
if ($isApiRoute) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Engine: upScale Media Network v2.0');

    // A. Handshake / Verificação de Conexão do Painel ("Salvar Configurações")
    $rawBody = file_get_contents('php://input');
    if (strpos($uri, 'health') !== false || strpos($uri, 'status') !== false || (empty($rawBody) && empty($_POST))) {
        echo json_encode([
            'status'       => 'success',
            'connected'    => true,
            'message'      => 'Conexão com a upScale Media Network estabelecida com sucesso!',
            'engine'       => 'upScale Media Engine v2.0',
            'version'      => '3.0.0',
            'workspace'    => [
                'name' => $workspace->name,
                'slug' => $workspace->slug
            ],
            'capabilities' => [
                'upsell' => [
                    'iframe'       => true,
                    'custom'       => true,
                    'simple'       => true,
                    'redirect'     => true
                ]
            ],
            'active_offer' => [
                'title' => $premio . ' - ' . $campanhaNome,
                'price' => $preco
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // B. Resposta de Oferta de Upsell Pós-Compra (Quando o comprador conclui o pedido)
    echo json_encode([
        'status'     => 'success',
        'has_offer'  => true,
        'type'       => 'iframe',
        'url'        => $cardUrl,
        'iframe_url' => $cardUrl,
        'height'     => 640,
        'title'      => $premio . ' · R$' . number_format($preco, 0, ',', '.'),
        
        // Formato para modo "Personalizado" (HTML embutido)
        'html'       => sprintf(
            '<div id="dx-wrap" style="width:100%%;max-width:640px;margin:0 auto;"><iframe id="dx-frame" src="%s" style="width:100%%;height:640px;border:none;background:transparent;" scrolling="no" allow="autoplay; encrypted-media"></iframe></div>',
            htmlspecialchars($cardUrl)
        ),
        
        // Formato para modo "Redirect"
        'redirect_url' => $cardUrl,
        
        // Metadados completos da oferta
        'offer'      => [
            'id'           => 'offer_' . ($offer['id'] ?? 'default'),
            'title'        => $premio,
            'subtitle'     => $campanhaNome,
            'price'        => $preco,
            'quota_count'  => $cotas,
            'cta_label'    => 'ATIVAR MEU ' . strtoupper($premio),
            'checkout_url' => $checkoutUrl,
            'iframe_url'   => $cardUrl
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==============================================================================
// RESPOSTA EM FORMATO HTML (QUANDO O IFRAME CARREGA A PRÓPRIA API)
// ==============================================================================
header('Content-Type: text/html; charset=utf-8');

$templatePath = dirname(__DIR__) . '/index.html';
if (file_exists($templatePath)) {
    $html = file_get_contents($templatePath);
    if (!empty($primeiroNome)) {
        $html = str_replace('data-nome=""', 'data-nome="' . htmlspecialchars($primeiroNome) . '"', $html);
    }
    echo $html;
} else {
    header('Location: ' . $cardUrl);
}
exit;
