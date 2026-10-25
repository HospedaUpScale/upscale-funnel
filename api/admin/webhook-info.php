<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

// Workspace alvo (com checagem de escopo, igual aos demais endpoints admin)
$targetWsId = (int)($request->get('workspace_id') ?? ($request->all()['workspace_id'] ?? ($auth['workspace']->id ?? 0)));
if ($targetWsId <= 0) {
    $targetWsId = $auth['workspace']->id ?? 0;
}

if ($auth && $auth['role'] !== 'super_admin') {
    $ownId = $auth['workspace']->id;
    if ($ownId !== $targetWsId) {
        $allowed = array_map(fn($w) => $w->id, $wsRepo->findDescendants($ownId));
        if (!in_array($targetWsId, $allowed, true)) {
            JsonResponse::error('Forbidden for this workspace', 403);
        }
    }
}

$ws = $wsRepo->findById($targetWsId);
if (!$ws) {
    JsonResponse::error('Workspace não encontrado.', 404);
}

// Base pública do site (respeita proxy/HTTPS da Hostinger/Cloudflare)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'));
$scheme = $isHttps ? 'https' : 'http';
// Endereços para o cliente usam o domínio próprio do workspace; sem ele, o do sistema (com aviso no painel).
$ownDomain = \App\Support\PublicUrl::officialDomain($pdo, $targetWsId);
$host = $ownDomain ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
$base = ($ownDomain ? 'https' : $scheme) . '://' . $host;

// POST com action=rotate_token: gera um novo token de API e o devolve uma única vez.
if ($request->method() === 'POST') {
    $action = $request->get('action') ?? ($request->all()['action'] ?? '');
    // Endereço da plataforma da rifa que recebe o aviso de cotas pagas (upsell.quotas_paid)
    if ($action === 'save_postback') {
        $url = trim((string)($request->all()['postback_url'] ?? ''));
        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $url))) {
            JsonResponse::error('Informe um endereço completo começando com https://', 422);
        }
        try {
            $pdo->prepare('UPDATE workspaces SET postback_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$url ?: null, $targetWsId]);
        } catch (\PDOException $e) {
            JsonResponse::error('Faça o deploy para liberar este campo (migração 0030).', 503);
        }
        JsonResponse::send(['status' => 'success', 'postback_url' => $url ?: null, 'message' => $url ? 'Endereço de retorno da rifa salvo.' : 'Endereço de retorno removido.']);
    }
    // Pix de teste: liga por 24 horas (desliga sozinho) ou desliga na hora.
    if ($action === 'pix_test') {
        $enabled = filter_var($request->all()['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        try {
            if ($enabled) {
                $until = date('c', strtotime(\App\Support\PixTestMode::enable($pdo, $targetWsId)));
            } else {
                \App\Support\PixTestMode::disable($pdo, $targetWsId);
                $until = null;
            }
        } catch (\PDOException $e) {
            JsonResponse::error('Faça o deploy para liberar o Pix de teste (migração 0032).', 503);
        }
        JsonResponse::send([
            'status'         => 'success',
            'pix_test_until' => $until,
            'message'        => $enabled
                ? 'Pix de teste ligado por ' . \App\Support\PixTestMode::HOURS . ' horas. A janela do Pix da oferta mostra "Simular pagamento aprovado".'
                : 'Pix de teste desligado.',
        ]);
    }
    if ($action !== 'rotate_token') {
        JsonResponse::error('Ação inválida.', 400);
    }
    $raw = $wsRepo->rotateApiToken($targetWsId);
    JsonResponse::send([
        'status'     => 'success',
        'api_token'  => $raw,
        'message'    => 'Novo token de API gerado. Guarde-o: ele não será mostrado de novo. Atualize os webhooks das plataformas com este token.',
        'rotated_at' => date('c'),
    ]);
}

// GET: endpoints de webhook do workspace.
$postbackUrl = null;
try {
    $stPb = $pdo->prepare('SELECT postback_url FROM workspaces WHERE id = ?');
    $stPb->execute([$targetWsId]);
    $postbackUrl = $stPb->fetchColumn() ?: null;
} catch (\PDOException $e) {
}
JsonResponse::send([
    'status'       => 'success',
    'workspace_id' => $targetWsId,
    'postback_url' => $postbackUrl,
    'has_own_domain' => $ownDomain !== null,
    'postback_signed' => (string)($config['postback_secret'] ?? '') !== '',
    'pix_test_until' => \App\Support\PixTestMode::untilIso($pdo, $targetWsId),
    'base_url'     => $base,
    'endpoints'    => [
        [
            'key'         => 'sales',
            'name'        => 'Webhook de Vendas da Rifa',
            'description' => 'Cadastre na plataforma de rifas (Sorteamos, Play55, SorteiMe) para registrar cada venda e gerar o link do card.',
            'method'      => 'POST',
            'url'         => $base . '/api/v1/webhook.php',
            'auth'        => 'Authorization: Bearer <TOKEN_DE_API>',
            'auth_query'  => $base . '/api/v1/webhook.php?token=<TOKEN_DE_API>',
        ],
        [
            'key'         => 'payment',
            'name'        => 'Webhook de Pagamento Pix',
            'description' => 'Cadastre no gateway Pix para confirmar o pagamento do upsell e liberar as cotas.',
            'method'      => 'POST',
            'url'         => $base . '/api/webhook.php',
            'auth'        => 'Authorization: Bearer <TOKEN_DE_API>',
            'auth_query'  => $base . '/api/webhook.php?token=<TOKEN_DE_API>',
        ],
        [
            'key'         => 'order_context',
            'name'        => 'Injeção de Upsell (contexto do pedido)',
            'description' => 'Opcional: pré-carrega os dados do comprador no card do upsell.',
            'method'      => 'POST',
            'url'         => $base . '/api/order-context.php',
            'auth'        => 'Authorization: Bearer <TOKEN_DE_API>',
            'auth_query'  => $base . '/api/order-context.php?token=<TOKEN_DE_API>',
        ],
    ],
    // Canais de origem rastreados nas vendas (ver api/pix-upsell.php).
    'tracked_sources' => [
        ['key' => 'offer_link',    'label' => 'Link exclusivo do upsell (/o/slug)'],
        ['key' => 'card_link',     'label' => 'Link do card pós-venda (token)'],
        ['key' => 'funnel',        'label' => 'Funil / Pressel'],
        ['key' => 'raffle_webhook','label' => 'Webhook da rifa'],
        ['key' => 'direct',        'label' => 'Acesso direto'],
    ],
]);
