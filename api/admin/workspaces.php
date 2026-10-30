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
    $partners = [];

    // Plataformas usadas pelas campanhas de cada workspace (o workspace em si não guarda plataforma)
    $platformsByWs = [];
    try {
        $stmt = $pdo->query('SELECT workspace_id, external_platform FROM campaigns GROUP BY workspace_id, external_platform');
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $platformsByWs[(int)$row['workspace_id']][] = (string)$row['external_platform'];
        }
    } catch (\Throwable $e) {
        $platformsByWs = [];
    }

    // Carregar parceiros white-label para seleção rápida
    $allWorkspaces = $wsRepo->all();
    foreach ($allWorkspaces as $w) {
        if ($w->type === 'partner_whitelabel' && $w->status === 'active') {
            $partners[] = [
                'id'   => $w->id,
                'name' => $w->name,
                'slug' => $w->slug,
            ];
        }
    }

    if (!$auth || $auth['role'] === 'super_admin') {
        foreach ($allWorkspaces as $w) {
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
                'created_at'    => $w->createdAt,
                'platforms'     => $platformsByWs[$w->id] ?? [],
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
            'created_at'    => $me->createdAt,
            'platforms'     => $platformsByWs[$me->id] ?? [],
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
                'created_at'    => $w->createdAt,
                'platforms'     => $platformsByWs[$w->id] ?? [],
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
            'created_at'    => $me->createdAt,
            'platforms'     => $platformsByWs[$me->id] ?? [],
        ];
    }

    JsonResponse::send([
        'status'     => 'success',
        'workspaces' => $workspaces,
        'partners'   => $partners,
    ]);
}

// 2. POST: Create a new workspace
if ($request->method() === 'POST') {
    if ($auth && $auth['role'] === 'merchant_admin') {
        JsonResponse::error('Subcontas de clientes não possuem permissão para criar workspaces.', 403);
    }

    $input = $request->all();
    // Parceiro só cria clientes dentro da própria árvore: parceiros novos, "Sorteamos" e escolha de
    // outro pai são exclusivos do master (antes um parceiro criava contas sob outro parceiro e recebia os tokens).
    if ($auth && $auth['role'] === 'partner_admin') {
        $input['account_type'] = 'merchant';
        $input['partner_option'] = 'existing';
        unset($input['type'], $input['partner_id'], $input['parent_id'], $input['new_partner_name'], $input['new_partner_slug']);
    }
    $name = trim($input['name'] ?? '');
    $accountType = trim($input['account_type'] ?? $input['type'] ?? 'merchant');

    if (empty($name)) {
        JsonResponse::error('O Nome do cliente/workspace é obrigatório.', 400);
    }

    $rawSlug = trim($input['slug'] ?? '');
    if ($rawSlug === '') {
        $rawSlug = $name;
    }
    $rawSlug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $rawSlug) ?: $rawSlug;
    $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]+/i', '-', str_replace(' ', '-', $rawSlug)), '-'));
    if ($slug === '') {
        $slug = 'cliente-' . substr(md5(uniqid('', true)), 0, 6);
    }

    // Se o slug já existir, gera sufixo numérico
    if ($wsRepo->findBySlug($slug)) {
        $candidate = $slug;
        $suffix = 2;
        while ($wsRepo->findBySlug($candidate . '-' . $suffix) && $suffix < 100) {
            $suffix++;
        }
        $slug = $candidate . '-' . $suffix;
    }

    // Login opcional do cliente/parceiro (e-mail + senha), validado antes de criar qualquer coisa
    $adminEmail = strtolower(trim((string) ($input['initial_user_email'] ?? $input['admin_email'] ?? '')));
    $adminPassword = (string) ($input['initial_user_password'] ?? $input['admin_password'] ?? '');
    $userRepo = new \App\Repositories\UserRepository($pdo);
    if ($adminEmail !== '') {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            JsonResponse::error('Informe um e-mail válido para o login de acesso.', 400);
        }
        if ($adminPassword !== '' && strlen($adminPassword) < 8) {
            JsonResponse::error('A senha de acesso precisa ter pelo menos 8 caracteres.', 400);
        }
        if ($userRepo->findByEmail($adminEmail) && $auth && $auth['role'] === 'partner_admin') {
            JsonResponse::error('Este e-mail já está em uso em outro workspace. Use outro e-mail.', 409);
        }
    }

    // Generate random secure tokens
    $rawApiToken = bin2hex(random_bytes(20));
    $rawAdminToken = bin2hex(random_bytes(20));

    $parentId = null;
    $type = $accountType === 'partner_whitelabel' ? 'partner_whitelabel' : 'merchant';
    $partnerInfo = null;
    $externalPlatform = trim((string)($input['external_platform'] ?? 'sorteamos'));

    $pdo->beginTransaction();
    try {
        if ($type === 'partner_whitelabel') {
            // Criação direta de Empresa Parceira (White-Label)
            $parentId = null;
        } else {
            // Criação de Cliente / Rifa vinculado a um Parceiro
            $partnerOption = $input['partner_option'] ?? 'existing'; // 'existing', 'new', 'sorteamos'
            $newPartnerName = trim($input['new_partner_name'] ?? '');

            if ($partnerOption === 'new' && $newPartnerName !== '') {
                // Usuário optou por criar um Novo Parceiro (ex: Sorteamos ou Outra Empresa)
                $newPartnerSlug = strtolower(trim(preg_replace('/[^a-z0-9\-]/i', '', $input['new_partner_slug'] ?? '')));
                if ($newPartnerSlug === '') {
                    $newPartnerSlug = strtolower(trim(preg_replace('/[^a-z0-9\-]/i', '', str_replace(' ', '-', $newPartnerName))));
                }
                if ($newPartnerSlug === '') {
                    $newPartnerSlug = 'parceiro-' . bin2hex(random_bytes(3));
                }

                // Verifica se parceiro já existe por slug
                $existingPartner = $wsRepo->findBySlug($newPartnerSlug);
                if ($existingPartner) {
                    $parentId = $existingPartner->id;
                    $partnerInfo = [
                        'id'      => $existingPartner->id,
                        'name'    => $existingPartner->name,
                        'slug'    => $existingPartner->slug,
                        'is_new'  => false,
                    ];
                } else {
                    $partnerRawApiToken = bin2hex(random_bytes(20));
                    $partnerRawAdminToken = bin2hex(random_bytes(20));
                    $createdPartner = $wsRepo->create([
                        'parent_id'        => null,
                        'type'             => 'partner_whitelabel',
                        'name'             => $newPartnerName,
                        'slug'             => $newPartnerSlug,
                        'custom_domain'    => null,
                        'document'         => null,
                        'api_token_hash'   => hash('sha256', $partnerRawApiToken),
                        'admin_token_hash' => hash('sha256', $partnerRawAdminToken),
                        'gateway_provider' => 'mock',
                        'status'           => 'active',
                    ]);
                    $parentId = $createdPartner->id;
                    $partnerInfo = [
                        'id'          => $createdPartner->id,
                        'name'        => $createdPartner->name,
                        'slug'        => $createdPartner->slug,
                        'is_new'      => true,
                        'api_token'   => $partnerRawApiToken,
                        'admin_token' => $partnerRawAdminToken,
                    ];
                }

                if (stripos($newPartnerName, 'sorteamos') !== false || stripos($newPartnerSlug, 'sorteamos') !== false) {
                    $externalPlatform = 'sorteamos';
                } else {
                    $externalPlatform = $newPartnerSlug;
                }
            } elseif ($partnerOption === 'sorteamos') {
                // Opção direta "Sorteamos"
                $partnerWs = $wsRepo->findBySlug('sorteamos');
                if (!$partnerWs) {
                    $partnerRawApiToken = bin2hex(random_bytes(20));
                    $partnerRawAdminToken = bin2hex(random_bytes(20));
                    $partnerWs = $wsRepo->create([
                        'parent_id'        => null,
                        'type'             => 'partner_whitelabel',
                        'name'             => 'Sorteamos Plataforma Parceira',
                        'slug'             => 'sorteamos',
                        'custom_domain'    => null,
                        'document'         => null,
                        'api_token_hash'   => hash('sha256', $partnerRawApiToken),
                        'admin_token_hash' => hash('sha256', $partnerRawAdminToken),
                        'gateway_provider' => 'mock',
                        'status'           => 'active',
                    ]);
                    $partnerInfo = [
                        'id'     => $partnerWs->id,
                        'name'   => $partnerWs->name,
                        'slug'   => $partnerWs->slug,
                        'is_new' => true,
                    ];
                } else {
                    $partnerInfo = [
                        'id'     => $partnerWs->id,
                        'name'   => $partnerWs->name,
                        'slug'   => $partnerWs->slug,
                        'is_new' => false,
                    ];
                }
                $parentId = $partnerWs->id;
                $externalPlatform = 'sorteamos';
            } else {
                // Parceiro existente selecionado ou herança de autenticação
                if ($auth && $auth['role'] === 'partner_admin') {
                    $parentId = $auth['workspace']->id;
                    $partnerInfo = [
                        'id'     => $auth['workspace']->id,
                        'name'   => $auth['workspace']->name,
                        'slug'   => $auth['workspace']->slug,
                        'is_new' => false,
                    ];
                } elseif (!empty($input['partner_id'])) {
                    $parentId = (int)$input['partner_id'];
                    $selectedPartner = $wsRepo->findById($parentId);
                    if ($selectedPartner) {
                        $partnerInfo = [
                            'id'     => $selectedPartner->id,
                            'name'   => $selectedPartner->name,
                            'slug'   => $selectedPartner->slug,
                            'is_new' => false,
                        ];
                        if (stripos($selectedPartner->slug, 'sorteamos') !== false) {
                            $externalPlatform = 'sorteamos';
                        }
                    }
                } elseif (!empty($input['parent_id'])) {
                    $parentId = (int)$input['parent_id'];
                } else {
                    // Fallback para o primeiro parceiro disponível
                    $firstPartner = $pdo->query("SELECT id, name, slug FROM workspaces WHERE type = 'partner_whitelabel' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    if ($firstPartner) {
                        $parentId = (int)$firstPartner['id'];
                        $partnerInfo = $firstPartner;
                    }
                }
            }
        }

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

        // Domínio informado na criação entra na lista de domínios como domínio oficial do workspace
        if (!empty($created->customDomain)) {
            $officialDomain = strtolower(trim((string)$created->customDomain));
            $officialDomain = preg_replace('#^https?://#', '', $officialDomain);
            $officialDomain = rtrim(explode('/', $officialDomain)[0], '.');
            if (str_starts_with($officialDomain, 'www.')) $officialDomain = substr($officialDomain, 4);
            if ($officialDomain !== '' && filter_var($officialDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                try {
                    $exists = $pdo->prepare("SELECT id FROM custom_domains WHERE domain = ? LIMIT 1");
                    $exists->execute([$officialDomain]);
                    if (!$exists->fetchColumn()) {
                        $pdo->prepare("INSERT INTO custom_domains (workspace_id, domain, verification_token, is_primary) VALUES (?, ?, ?, 1)")
                            ->execute([$created->id, $officialDomain, bin2hex(random_bytes(16))]);
                    }
                } catch (\Throwable $e) {
                    // o workspace já foi criado; o domínio pode ser conectado depois em Domínios
                }
            }
        }

        if ($type === 'merchant') {
            // Create default campaign & upsell offer for new client workspace
            $campaignStmt = $pdo->prepare("
                INSERT INTO campaigns (workspace_id, external_platform, external_campaign_id, title, ticket_price, status)
                VALUES (?, ?, 'camp_default', ?, ?, 'active')
            ");
            $campaignTitle = $input['campaign_title'] ?? ($name . ' Campanha Oficial');
            $offerPrice = (float)($input['price'] ?? 90.00);
            $campaignStmt->execute([$created->id, $externalPlatform, $campaignTitle, $offerPrice]);
            $campId = (int)$pdo->lastInsertId();

            $offerStmt = $pdo->prepare("
                INSERT INTO upsell_offers (workspace_id, campaign_id, type, title, subtitle, price, quota_count, checkout_mode, is_active)
                VALUES (?, ?, 'post_pix_scratch', ?, ?, ?, ?, 'pix_native', 1)
            ");
            $offerTitle = $input['offer_title'] ?? 'Booster 110X';
            $offerQuotas = (int)($input['quota_count'] ?? 110);
            $offerStmt->execute([$created->id, $campId, $offerTitle, $campaignTitle, $offerPrice, $offerQuotas]);

            // Set partner split rule if parent is provided (sem comissão SaaS, 100% merchant)
            if ($parentId) {
                $splitStmt = $pdo->prepare("
                    INSERT INTO partner_split_rules (partner_workspace_id, merchant_workspace_id, saas_rate_percentage, partner_rate_percentage, merchant_rate_percentage, absorb_gateway_fees)
                    VALUES (?, ?, 0.00, 0.00, 100.00, 'merchant')
                ");
                $splitStmt->execute([$parentId, $created->id]);
            }
        }

        $access = null;
        if ($adminEmail !== '') {
            $accessRole = $created->type === 'partner_whitelabel' ? 'partner_admin' : 'merchant_admin';
            $r = $userRepo->upsertForWorkspace($created->id, $accessRole, $adminEmail, $adminPassword, $created->name);
            $access = [
                'email'    => $r['email'],
                'password' => $r['password'] ?? ($adminPassword !== '' ? $adminPassword : null),
                'role'     => $accessRole,
            ];
        }

        // Vincular usuário master ao novo workspace com papel super_admin
        try {
            $masterUserRow = $pdo->query("SELECT id FROM users WHERE email = 'isaiasmktd@gmail.com' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($masterUserRow) {
                $checkStmt = $pdo->prepare("SELECT id FROM workspace_memberships WHERE workspace_id = ? AND user_id = ?");
                $checkStmt->execute([$created->id, (int)$masterUserRow['id']]);
                if (!$checkStmt->fetch()) {
                    $pdo->prepare("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, 'super_admin')")
                        ->execute([$created->id, (int)$masterUserRow['id']]);
                }
            }
        } catch (\Throwable $ignored) {}

        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    JsonResponse::send([
        'status'    => 'success',
        'message'   => $type === 'partner_whitelabel' ? 'Empresa Parceira criada com sucesso!' : 'Subconta de cliente criada com sucesso!',
        'workspace' => [
            'id'            => $created->id,
            'type'          => $created->type,
            'parent_id'     => $created->parentId,
            'name'          => $created->name,
            'slug'          => $created->slug,
            'custom_domain' => $created->customDomain,
            'api_token'     => $rawApiToken,     // SHOWN ONLY ONCE!
            'admin_token'   => $rawAdminToken,   // SHOWN ONLY ONCE!
        ],
        'partner'   => $partnerInfo,
        'access'    => $access,
    ], 201);
}

JsonResponse::error('Method not allowed', 405);
