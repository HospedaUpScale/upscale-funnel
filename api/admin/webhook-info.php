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
$host = $ws->customDomain ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
$base = $scheme . '://' . $host;

// POST com action=rotate_token: gera um novo token de API e o devolve uma única vez.
if ($request->method() === 'POST') {
    $action = $request->get('action') ?? ($request->all()['action'] ?? '');
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
JsonResponse::send([
    'status'       => 'success',
    'workspace_id' => $targetWsId,
    'base_url'     => $base,
    'endpoints'    => [
        [
            'key'         => 'sales',
            'name'        => 'Webhook de Vendas da Rifa',
            'description' => 'Cadastre na plataforma de rifas (Sorteamos, Play55, SorteiMe) para registrar cada venda e gerar o link do card.',
            'method'      => 'POST',
            'url'         => $base . '/api/v1/webhook.php',
            'auth'        => 'Authorization: Bearer <TOKEN_DE_API>',
            'auth_query'  => $base . '/api/v1/webhook.php?api_token=<TOKEN_DE_API>',
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
            'auth'        => '—',
            'auth_query'  => '',
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
