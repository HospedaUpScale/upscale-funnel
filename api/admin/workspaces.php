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

// 1. GET: List accessible workspaces
if ($request->method() === 'GET') {
    $workspaces = [];
    if (!$auth || $auth['role'] === 'super_admin') {
        $all = $wsRepo->all();
        foreach ($all as $w) {
            $parentName = null;
            if ($w->parentId) {
                $p = $wsRepo->findById($w->parentId);
                $parentName = $p ? $p->name : null;
            }
            $workspaces[] = [
                'id'            => $w->id,
                'parent_id'     => $w->parentId,
                'parent_name'   => $parentName,
                'type'          => $w->type,
                'name'          => $w->name,
                'slug'          => $w->slug,
                'custom_domain' => $w->customDomain,
                'status'        => $w->status,
                'is_subaccount' => !empty($w->parentId),
            ];
        }
    } elseif ($auth['role'] === 'partner_admin') {
        $me = $auth['workspace'];
        $workspaces[] = [
            'id'            => $me->id,
            'parent_id'     => null,
            'parent_name'   => null,
            'type'          => $me->type,
            'name'          => $me->name,
            'slug'          => $me->slug,
            'custom_domain' => $me->customDomain,
            'status'        => $me->status,
            'is_subaccount' => false,
        ];
        $descendants = $wsRepo->findDescendants($me->id);
        foreach ($descendants as $w) {
            $workspaces[] = [
                'id'            => $w->id,
                'parent_id'     => $w->parentId,
                'parent_name'   => $me->name,
                'type'          => $w->type,
                'name'          => $w->name,
                'slug'          => $w->slug,
                'custom_domain' => $w->customDomain,
                'status'        => $w->status,
                'is_subaccount' => true,
            ];
        }
    } else { // merchant_admin
        $me = $auth['workspace'];
        $parentName = null;
        if ($me->parentId) {
            $p = $wsRepo->findById($me->parentId);
            $parentName = $p ? $p->name : null;
        }
        $workspaces[] = [
            'id'            => $me->id,
            'parent_id'     => $me->parentId,
            'parent_name'   => $parentName,
            'type'          => $me->type,
            'name'          => $me->name,
            'slug'          => $me->slug,
            'custom_domain' => $me->customDomain,
            'status'        => $me->status,
            'is_subaccount' => !empty($me->parentId),
        ];
    }

    JsonResponse::send([
        'status'     => 'success',
        'workspaces' => $workspaces
    ]);
}

// 2. POST: Create a new workspace
if ($request->method() === 'POST') {
    if ($auth && $auth['role'] === 'merchant_admin') {
        JsonResponse::error('Subcontas de clientes não possuem permissão para criar workspaces.', 403);
    }

    $input = $request->all();
    $name = trim($input['name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]/i', '', $input['slug'] ?? '')));

    if (empty($name) || empty($slug)) {
        JsonResponse::error('name and slug are required', 400);
    }

    // Check if slug exists
    if ($wsRepo->findBySlug($slug)) {
        JsonResponse::error('Slug already in use', 409);
    }

    // Login opcional do cliente (e-mail + senha), validado antes de criar qualquer coisa
    $adminEmail = strtolower(trim((string) ($input['admin_email'] ?? '')));
    $adminPassword = (string) ($input['admin_password'] ?? '');
    $userRepo = new \App\Repositories\UserRepository($pdo);
    if ($adminEmail !== '') {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            JsonResponse::error('Informe um e-mail válido para o login do cliente.', 400);
        }
        if ($adminPassword !== '' && strlen($adminPassword) < 8) {
            JsonResponse::error('A senha do cliente precisa ter pelo menos 8 caracteres.', 400);
        }
        if ($userRepo->findByEmail($adminEmail) && $auth && $auth['role'] === 'partner_admin') {
            JsonResponse::error('Este e-mail já está em uso em outro workspace. Use outro e-mail.', 409);
        }
    }

    // Generate random secure tokens
    $rawApiToken = bin2hex(random_bytes(20));
    $rawAdminToken = bin2hex(random_bytes(20));

    // Enforce parent_id for partner_admin
    $parentId = null;
    $type = 'merchant';

    if ($auth && $auth['role'] === 'partner_admin') {
        $parentId = $auth['workspace']->id;
        $type = 'merchant';
    } elseif (!$auth || $auth['role'] === 'super_admin') {
        $parentId = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
        $type = $input['type'] ?? ($parentId ? 'merchant' : 'partner_whitelabel');
    }

    $pdo->beginTransaction();
    try {
    $created = $wsRepo->create([
        'parent_id'        => $parentId,
        'type'             => $type,
        'name'             => $name,
        'slug'             => $slug,
        'custom_domain'    => !empty($input['custom_domain']) ? trim($input['custom_domain']) : null,
        'document'         => $input['document'] ?? null,
        'api_token_hash'   => hash('sha256', $rawApiToken),
        'admin_token_hash' => hash('sha256', $rawAdminToken),
        'gateway_provider' => $input['gateway_provider'] ?? 'mock',
        'gateway_credentials' => $input['gateway_credentials'] ?? null,
        'split_recipient_id' => $input['split_recipient_id'] ?? null,
        'status'           => 'active',
    ]);

    // Create default campaign & upsell offer for new workspace
    $campaignStmt = $pdo->prepare("
        INSERT INTO campaigns (workspace_id, external_platform, external_campaign_id, title, ticket_price, status)
        VALUES (?, 'sorteamos', 'camp_default', ?, ?, 'active')
    ");
    $campaignTitle = $input['campaign_title'] ?? ($name . ' Campanha Oficial');
    $offerPrice = (float)($input['price'] ?? 90.00);
    $campaignStmt->execute([$created->id, $campaignTitle, $offerPrice]);
    $campId = (int)$pdo->lastInsertId();

    $offerStmt = $pdo->prepare("
        INSERT INTO upsell_offers (workspace_id, campaign_id, type, title, subtitle, price, quota_count, checkout_mode, is_active)
        VALUES (?, ?, 'post_pix_scratch', ?, ?, ?, ?, 'pix_native', 1)
    ");
    $offerTitle = $input['offer_title'] ?? 'Booster 110X';
    $offerQuotas = (int)($input['quota_count'] ?? 110);
    $offerStmt->execute([$created->id, $campId, $offerTitle, $campaignTitle, $offerPrice, $offerQuotas]);

    // Set partner split rule if parent is provided
    if ($parentId) {
        $splitStmt = $pdo->prepare("
            INSERT INTO partner_split_rules (partner_workspace_id, merchant_workspace_id, saas_rate_percentage, partner_rate_percentage, merchant_rate_percentage, absorb_gateway_fees)
            VALUES (?, ?, 10.00, 5.00, 85.00, 'merchant')
        ");
        $splitStmt->execute([$parentId, $created->id]);
    }

    $access = null;
    if ($adminEmail !== '') {
        $accessRole = $created->type === 'partner_whitelabel' ? 'partner_admin' : 'merchant_admin';
        $r = $userRepo->upsertForWorkspace($created->id, $accessRole, $adminEmail, $adminPassword, $created->name);
        $access = [
            'email'    => $r['email'],
            'password' => $r['password'] ?? ($adminPassword !== '' ? $adminPassword : null),
        ];
    }

    $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    JsonResponse::send([
        'status'    => 'success',
        'message'   => 'Workspace criado com sucesso!',
        'workspace' => [
            'id'            => $created->id,
            'name'          => $created->name,
            'slug'          => $created->slug,
            'custom_domain' => $created->customDomain,
            'api_token'     => $rawApiToken,     // SHOWN ONLY ONCE!
            'admin_token'   => $rawAdminToken,   // SHOWN ONLY ONCE!
        ],
        'access' => $access,
    ], 201);
}

JsonResponse::error('Method not allowed', 405);
