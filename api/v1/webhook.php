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

// GET autenticado = verificação da integração (não cria venda): confirma o token, mostra o
// workspace e o domínio onde os cards serão entregues, e se a campanha informada existe.
if ($request->method() === 'GET') {
    $wsCheck = (new \App\Security\WebhookAuth($wsRepo))->workspaceForToken(\App\Security\WebhookAuth::extractToken());
    if (!$wsCheck) {
        JsonResponse::error('Token de API ausente ou inválido.', 401);
    }
    $ownDomain = \App\Support\PublicUrl::officialDomain($pdo, $wsCheck->id);
    $ref = trim((string)($request->get('rifa_id') ?? $request->get('campaign_id') ?? ''));
    $campaignInfo = null;
    if ($ref !== '') {
        $stc = $pdo->prepare("SELECT c.id, c.title, (SELECT COUNT(*) FROM upsell_offers o WHERE o.campaign_id = c.id AND o.offer_role = 'upsell' AND o.is_published = 1 AND o.is_active = 1) AS ofertas
            FROM campaigns c WHERE c.workspace_id = ? AND (c.external_campaign_id = ? OR c.id = ?) ORDER BY (c.external_campaign_id = ?) DESC, c.id DESC LIMIT 1");
        $stc->execute([$wsCheck->id, $ref, ctype_digit($ref) ? (int)$ref : 0, $ref]);
        $c = $stc->fetch(PDO::FETCH_ASSOC);
        $campaignInfo = $c ? ['id' => (int)$c['id'], 'title' => $c['title'], 'published_offer' => (int)$c['ofertas'] > 0] : false;
    }
    $postback = null;
    try {
        $stp = $pdo->prepare('SELECT postback_url FROM workspaces WHERE id = ?');
        $stp->execute([$wsCheck->id]);
        $postback = $stp->fetchColumn() ?: null;
    } catch (\PDOException $e) {
    }
    $postbackSecret = (string)($config['postback_secret'] ?? '');
    JsonResponse::send([
        'status'          => 'success',
        'message'         => 'Token válido.',
        'workspace'       => $wsCheck->name,
        'card_domain'     => $ownDomain,
        'campaign'        => $campaignInfo,
        'postback_configured' => $postback !== null,
        'postback_url'    => $postback,
        // A rifa pode mandar callback_url + callback_key na venda (retorno automático, sem segredo no servidor).
        'automatic_callback' => true,
        'postback_signed' => $postbackSecret !== '',
        // Prova do segredo sem revelá-lo: a rifa calcula o mesmo HMAC com o segredo dela e compara.
        'postback_probe'  => $postbackSecret !== '' ? hash_hmac('sha256', \App\Support\RafflePostback::PROBE, $postbackSecret) : null,
    ]);
}

if ($request->method() !== 'POST') {
    JsonResponse::error('Método não permitido. Utilize POST.', 405);
}

// 1. Autenticação obrigatória: o token de API define o workspace da venda.
// Aceita Authorization: Bearer, X-Upscale-Token / X-Api-Token / X-Webhook-Token, ?token= ou ?api_token=.
$token = \App\Security\WebhookAuth::extractToken();

$tokenWorkspace = $token !== ''
    ? (new \App\Security\WebhookAuth($wsRepo))->workspaceForToken($token)
    : null;

if (!$tokenWorkspace) {
    JsonResponse::error('Token de API ausente ou inválido. Envie o token do workspace em Authorization: Bearer <token>.', 401);
}

$targetWsId = $tokenWorkspace->id;

// 2. Extração Flexível do Payload de Venda da Rifa
$input = $request->all();

$extOrderIdRaw = trim((string)(
    $input['order_id']
    ?? $input['pedido_id']
    ?? $input['id']
    ?? $input['data']['id']
    ?? $input['order']['id']
    ?? ''
));
$extOrderId = $extOrderIdRaw !== '' ? $extOrderIdRaw : uniqid('rifa_');

// Campanha da venda (opcional): ID da rifa na plataforma ou ID da campanha no upScale.
$campaignRef = trim((string)(
    $input['campaign_id']
    ?? $input['rifa_id']
    ?? $input['raffle_id']
    ?? $input['sorteio_id']
    ?? $input['external_campaign_id']
    ?? $input['campaign']['id']
    ?? $input['raffle']['id']
    ?? $input['rifa']['id']
    ?? ''
));

// Retorno automático (opcional): onde a rifa quer receber o aviso de cotas pagas e a chave que ela confere
// (vai em Authorization: Bearer <chave>). Com isso não é preciso cadastrar retorno nem POSTBACK_SECRET.
$callbackUrl = trim((string)($input['callback_url'] ?? $input['return_url'] ?? $input['url_retorno'] ?? ''));
$callbackKey = trim((string)($input['callback_key'] ?? $input['return_key'] ?? $input['chave_retorno'] ?? ''));
if ($callbackUrl !== '') {
    $localEnv = ($config['app_env'] ?? 'production') === 'local';
    if (!\App\Support\RafflePostback::isAllowedCallback($callbackUrl, $localEnv)) {
        JsonResponse::error('callback_url inválido: use um endereço https público.', 422);
    }
    if (strlen($callbackKey) < 16 || strlen($callbackKey) > 200 || preg_match('/[\r\n\s]/', $callbackKey)) {
        JsonResponse::error('Envie callback_key (16 a 200 caracteres, sem espaços) junto com callback_url.', 422);
    }
}

// Link de volta (opcional): página do pedido na rifa. A LP da oferta mostra "voltar para a rifa" com ele.
$backUrl = trim((string)($input['back_url'] ?? $input['voltar_url'] ?? $input['order_url'] ?? $input['pedido_url'] ?? ''));
if ($backUrl !== '' && !\App\Support\RafflePostback::isAllowedCallback($backUrl, ($config['app_env'] ?? 'production') === 'local')) {
    JsonResponse::error('back_url inválido: use o endereço https público da página do pedido na rifa.', 422);
}

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

// 4. Oferta da campanha informada (ou a oferta padrão do workspace)
$offerRepo = new OfferRepository($pdo);
$campaignId = null;
$offer = null;
if ($campaignRef !== '') {
    $stc = $pdo->prepare("SELECT id FROM campaigns WHERE workspace_id = ? AND (external_campaign_id = ? OR id = ?) ORDER BY (external_campaign_id = ?) DESC, id DESC LIMIT 1");
    $stc->execute([$targetWsId, $campaignRef, ctype_digit($campaignRef) ? (int)$campaignRef : 0, $campaignRef]);
    $campaignId = (int)($stc->fetchColumn() ?: 0) ?: null;
    if ($campaignId) {
        $sto = $pdo->prepare("SELECT * FROM upsell_offers WHERE workspace_id = ? AND campaign_id = ? AND offer_role = 'upsell' AND is_active = 1 ORDER BY is_published DESC, id ASC LIMIT 1");
        $sto->execute([$targetWsId, $campaignId]);
        $offer = $sto->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
if (!$offer) {
    $offer = $offerRepo->defaultActiveForWorkspace($targetWsId);
}
$theme = [];
if (!empty($offer['theme_config'])) {
    $theme = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
}

$carroA = $input['carro_a'] ?? $theme['carro_a'] ?? 'RANGER';
$carroB = $input['carro_b'] ?? $theme['carro_b'] ?? 'BMW';
$extraBonus = (float)($input['extra_bonus'] ?? $theme['extra_bonus'] ?? 50000.00);
$minOrder = (float)($input['min_order'] ?? $theme['min_order'] ?? 30.00);
$bonusEntitled = ($amount >= $minOrder && $isPaid) ? 1 : 0;

// 5. Token de acesso ao card (24 horas). A mesma venda reenviada reaproveita o token
//    (plataformas costumam repetir o webhook), e só atualiza o status de pagamento.
$duplicate = false;
$cardAccessToken = null;
if ($extOrderIdRaw !== '') {
    $stx = $pdo->prepare("SELECT token FROM order_access_tokens WHERE workspace_id = ? AND external_order_id = ? AND expires_at > CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1");
    $stx->execute([$targetWsId, $extOrderIdRaw]);
    $cardAccessToken = $stx->fetchColumn() ?: null;
}
if ($cardAccessToken) {
    $duplicate = true;
    $pdo->prepare("UPDATE order_access_tokens SET is_paid = ?, bonus_entitled = ? WHERE token = ?")
        ->execute([$isPaid ? 1 : 0, $bonusEntitled, $cardAccessToken]);
} else {
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
}
// ID da campanha como a rifa enviou: volta igual no aviso de cotas pagas (a rifa confere).
if ($campaignRef !== '') {
    try {
        $pdo->prepare("UPDATE order_access_tokens SET raffle_campaign_ref = ? WHERE token = ?")->execute([mb_substr($campaignRef, 0, 120), $cardAccessToken]);
    } catch (\PDOException $e) {
        // banco ainda sem a migração 0031
    }
}
if ($backUrl !== '') {
    try {
        $pdo->prepare("UPDATE order_access_tokens SET back_url = ? WHERE token = ?")->execute([$backUrl, $cardAccessToken]);
    } catch (\PDOException $e) {
        JsonResponse::error('Faça o deploy para liberar o link de volta (migração 0035).', 503);
    }
}
if ($callbackUrl !== '') {
    try {
        $pdo->prepare("UPDATE order_access_tokens SET callback_url = ?, callback_key = ? WHERE token = ?")->execute([$callbackUrl, $callbackKey, $cardAccessToken]);
    } catch (\PDOException $e) {
        JsonResponse::error('Faça o deploy para liberar o retorno automático (migração 0033).', 503);
    }
}

// 6. Link do card no domínio oficial do workspace, já na oferta da campanha quando publicada
$publicBase = \App\Support\PublicUrl::base($pdo, $targetWsId);

$parts = preg_split('/\s+/', $fullName);
$firstName = ucfirst(mb_strtolower($parts[0] ?? '', 'UTF-8'));

$cardUrl = (!empty($offer['slug']) && !empty($offer['is_published']))
    ? $publicBase . '/o/' . rawurlencode($offer['slug']) . '?token=' . urlencode($cardAccessToken)
    : $publicBase . '/?token=' . urlencode($cardAccessToken) . '&workspace_id=' . $targetWsId;

$iframeSnippet = '<iframe id="upscale-frame" src="' . $cardUrl . '" style="width:100%;border:0;height:820px;display:block;overflow:hidden" scrolling="no" allow="clipboard-write"></iframe>' . "\n" .
'<script>' . "\n" .
'window.addEventListener("message", function(e) {' . "\n" .
'  var f = document.getElementById("upscale-frame");' . "\n" .
'  if (f && e.source === f.contentWindow && e.data && e.data.tipo === "dx:altura") {' . "\n" .
'    f.style.height = e.data.altura + "px";' . "\n" .
'  }' . "\n" .
'});' . "\n" .
'</script>';

// Botão para a página de compra aprovada (abre a LP da oferta na mesma aba).
$buttonHtml = '<a href="' . htmlspecialchars($cardUrl, ENT_QUOTES, 'UTF-8') . '" style="display:block;max-width:420px;margin:16px auto;padding:16px 20px;border-radius:12px;background:#16a34a;color:#fff;font:700 17px/1.2 system-ui,-apple-system,sans-serif;text-align:center;text-decoration:none">'
    . htmlspecialchars($firstName !== '' ? $firstName . ', você ganhou uma oferta especial →' : 'Você ganhou uma oferta especial →', ENT_QUOTES, 'UTF-8') . '</a>';

// Log de Auditoria (fora do webroot; sem o token completo do card nem o nome inteiro do comprador)
@file_put_contents(
    \App\Support\LogPath::file('upscale-sales-webhooks.log'),
    \App\Support\LogPath::oneLine(date('c') . " | WS: {$targetWsId} | PED: {$extOrderId} | CLI: {$firstName} | VALOR: R$ {$amount} | TOKEN: " . substr($cardAccessToken, 0, 8) . '…') . "\n",
    FILE_APPEND | LOCK_EX
);

// 7. Retorno Oficial para a Plataforma de Rifa
JsonResponse::send([
    'status'         => 'success',
    'message'        => $duplicate ? 'Venda já registrada antes: o mesmo card foi reaproveitado.' : 'Venda registrada com sucesso na upScale Media Network.',
    'duplicate'      => $duplicate,
    'warning'        => \App\Support\PublicUrl::officialDomain($pdo, $targetWsId) === null
        ? 'Workspace sem domínio próprio ativo: o card está saindo no endereço do sistema. Conecte o domínio do cliente no painel.'
        : null,
    'campaign_id'    => $campaignId,
    'callback'       => $callbackUrl !== '' ? 'automatic' : 'workspace',
    'token'          => $cardAccessToken,
    'order_id'       => $extOrderId,
    'card_url'       => $cardUrl,
    // Integração por botão (recomendada): a página de compra aprovada leva o comprador para a LP da oferta.
    'button_url'     => $cardUrl,
    'button_html'    => $buttonHtml,
    'back_url'       => $backUrl !== '' ? $backUrl : null,
    // Integração antiga por iframe (mantida para quem já usa).
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
