<?php

// ==============================================================================
// upScale Media Network - Gestão de Campanhas e Ações de Rifas
// ==============================================================================
// Suporta: Listagem de Ações, Publicação ("No Ar"), Finalização ("Encerrada"),
// e Criação/Edição com Isolamento Multi-Tenant.
// ==============================================================================

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Campaigns\CampaignPage;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

// 1. Determinar target workspace e validar permissões
$targetWsId = (int)($request->get('workspace_id') ?? ($request->all()['workspace_id'] ?? ($auth['workspace']->id ?? 2)));

if ($targetWsId === 0) {
    $targetWsId = ($auth && $auth['workspace']) ? $auth['workspace']->id : 2;
}

if ($auth && $auth['role'] !== 'super_admin') {
    if ($auth['workspace']->id !== $targetWsId) {
        $descendants = $wsRepo->findDescendants($auth['workspace']->id);
        $allowedIds = array_map(fn($w) => $w->id, $descendants);
        if (!in_array($targetWsId, $allowedIds, true)) {
            JsonResponse::error('Acesso negado para este workspace/campanha.', 403);
        }
    }
}

// 2. GET: Listar campanhas/ações do workspace com métricas agregadas
if ($request->method() === 'GET') {
    // Garante que exista pelo menos uma campanha ativa para o workspace
    $stmtCheck = $pdo->prepare("SELECT id FROM campaigns WHERE workspace_id = ? LIMIT 1");
    $stmtCheck->execute([$targetWsId]);
    $hasCamp = $stmtCheck->fetch();

    if (!$hasCamp) {
        $stmtIns = $pdo->prepare("
            INSERT INTO campaigns (workspace_id, external_platform, external_campaign_id, title, ticket_price, status, published_at)
            VALUES (?, 'sorteamos', 'camp_principal', 'Kit Bobzão Bitruck + Ford F250', 90.00, 'published', CURRENT_TIMESTAMP)
        ");
        $stmtIns->execute([$targetWsId]);
        $newCampId = (int)$pdo->lastInsertId();

        // Vincula a oferta ativa a esta campanha
        $pdo->prepare("UPDATE upsell_offers SET campaign_id = ? WHERE workspace_id = ? AND campaign_id IS NULL")
            ->execute([$newCampId, $targetWsId]);
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $baseUrl = $scheme . '://' . $host;

    // Busca campanhas com detalhes da oferta vinculada, funil/pressel e volume de vendas
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            (SELECT MIN(d.id) FROM upsell_offers d WHERE d.workspace_id = c.workspace_id AND d.campaign_id = c.id AND d.offer_role = 'downsell') as downsell_id,
            o.id as offer_id,
            o.title as offer_title,
            o.price as offer_price,
            o.quota_count as offer_quotas,
            o.is_active as offer_is_active,
            o.checkout_mode,
            o.slug as offer_slug,
            o.theme_config as offer_theme,
            o.draft_config as offer_draft,
            f.id as funnel_id,
            f.name as funnel_name,
            f.slug as funnel_slug,
            f.status as funnel_status,
            f.template_slug as funnel_template_slug,
            (SELECT COUNT(*) FROM orders ord WHERE ord.workspace_id = c.workspace_id AND ord.status = 'paid') as total_orders_paid,
            (SELECT COALESCE(SUM(ord.total_amount), 0) FROM orders ord WHERE ord.workspace_id = c.workspace_id AND ord.status = 'paid') as total_volume_paid
        FROM campaigns c
        LEFT JOIN upsell_offers o ON o.id = (SELECT MIN(oi.id) FROM upsell_offers oi WHERE oi.campaign_id = c.id AND oi.workspace_id = c.workspace_id AND oi.offer_role = 'upsell')
        LEFT JOIN funnels f ON f.id = c.primary_funnel_id AND f.workspace_id = c.workspace_id AND f.campaign_id = c.id
        WHERE c.workspace_id = ?
        ORDER BY 
            CASE 
                WHEN c.status IN ('active', 'published') THEN 1
                WHEN c.status = 'draft' THEN 2
                ELSE 3
            END,
            c.id DESC
    ");
    $stmt->execute([$targetWsId]);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function($c) use ($baseUrl) {
        $offerDraft = json_decode($c['offer_draft'] ?? '', true) ?: [];
        $offerTheme = $offerDraft['theme'] ?? (json_decode($c['offer_theme'] ?? '', true) ?: []);
        $statusNormalized = in_array($c['status'], ['active', 'published']) ? 'published' : ($c['status'] === 'ended' ? 'ended' : 'draft');
        return [
            'id'                   => (int)$c['id'],
            'workspace_id'         => (int)$c['workspace_id'],
            'title'                => $c['title'],
            'has_downsell'          => !empty($c['downsell_id']),
            'ticket_price'         => (float)$c['ticket_price'],
            'primary_funnel_id'     => $c['primary_funnel_id'] ? (int)$c['primary_funnel_id'] : null,
            'external_platform'    => $c['external_platform'],
            'external_campaign_id' => $c['external_campaign_id'],
            'status'               => $statusNormalized,
            'is_published'         => $statusNormalized === 'published',
            'is_ended'             => $statusNormalized === 'ended',
            'published_at'         => $c['published_at'],
            'ended_at'             => $c['ended_at'],
            'created_at'           => $c['created_at'],
            'offer'                => $c['offer_id'] ? [
                'id'            => (int)$c['offer_id'],
                'title'         => $offerDraft['title'] ?? $c['offer_title'],
                'price'         => (float)($offerDraft['price'] ?? $c['offer_price']),
                'quota_count'   => (int)($offerDraft['quota_count'] ?? $c['offer_quotas']),
                'is_active'     => (bool)$c['offer_is_active'],
                'checkout_mode' => $c['checkout_mode'],
                'public_url' => !empty($c['offer_slug']) ? $baseUrl . '/o/' . $c['offer_slug'] : null,
                'mechanic' => $offerTheme['mechanic'] ?? 'scratch'
            ] : null,
            'funnel'               => $c['funnel_id'] ? [
                'id'            => (int)$c['funnel_id'],
                'name'          => $c['funnel_name'],
                'slug'          => $c['funnel_slug'],
                'status'        => $c['funnel_status'],
                'template_slug' => $c['funnel_template_slug'] ?? 'default',
                'public_url'    => $baseUrl . '/f/' . $c['funnel_slug'],
            ] : null,
            'metrics'              => [
                'total_orders' => (int)$c['total_orders_paid'],
                'total_volume' => (float)$c['total_volume_paid'],
            ]
        ];
    }, $campaigns);

    JsonResponse::send([
        'status'       => 'success',
        'workspace_id' => $targetWsId,
        'total'        => count($formatted),
        'campaigns'    => $formatted
    ]);
}

// 3. POST: Operações de Publicar, Finalizar ou Salvar Ação
if ($request->method() === 'POST') {
    $input = $request->all();
    $action = $input['action'] ?? 'save';
    $campaignId = !empty($input['campaign_id']) ? (int)$input['campaign_id'] : null;

    // 3.1 PUBLICAR AÇÃO (COLOCAR NO AR)
    if ($action === 'publish') {
        if (!$campaignId) {
            // Se não especificou campanha, pega a primeira do workspace
            $stmtC = $pdo->prepare("SELECT id FROM campaigns WHERE workspace_id = ? ORDER BY id ASC LIMIT 1");
            $stmtC->execute([$targetWsId]);
            $cRow = $stmtC->fetch();
            $campaignId = $cRow ? (int)$cRow['id'] : null;
        }

        if (!$campaignId) {
            JsonResponse::error('Nenhuma ação encontrada para publicar.', 404);
        }

        // Coloca status em 'published', atualiza published_at e limpa ended_at
        $stmtUp = $pdo->prepare("
            UPDATE campaigns SET 
                status = 'published', 
                published_at = CURRENT_TIMESTAMP,
                ended_at = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND workspace_id = ?
        ");
        $stmtUp->execute([$campaignId, $targetWsId]);

        // Ativa a oferta vinculada de upsell para receber tráfego
        $campaign = CampaignPage::campaign($pdo, $targetWsId, $campaignId);
        if (!$campaign) JsonResponse::error('Campanha não encontrada.', 404);
        CampaignPage::publishOffer($pdo, $campaign);

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'published',
            'campaign_id'  => $campaignId,
            'workspace_id' => $targetWsId,
            'message'      => 'Ação publicada com sucesso! O Booster está no ar e pronto para monetizar.'
        ]);
    }

    // 3.2 FINALIZAR AÇÃO (ENCERRAR AÇÃO / SORTEIO REALIZADO)
    if ($action === 'finalize') {
        if (!$campaignId) {
            $stmtC = $pdo->prepare("SELECT id FROM campaigns WHERE workspace_id = ? ORDER BY id ASC LIMIT 1");
            $stmtC->execute([$targetWsId]);
            $cRow = $stmtC->fetch();
            $campaignId = $cRow ? (int)$cRow['id'] : null;
        }

        if (!$campaignId) {
            JsonResponse::error('Nenhuma ação encontrada para finalizar.', 404);
        }

        // Coloca status em 'ended', atualiza ended_at
        $stmtUp = $pdo->prepare("
            UPDATE campaigns SET 
                status = 'ended', 
                ended_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND workspace_id = ?
        ");
        $stmtUp->execute([$campaignId, $targetWsId]);

        // Desativa a oferta vinculada de upsell para não aceitar novas cobranças
        $pdo->prepare("UPDATE upsell_offers SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE campaign_id = ?")
            ->execute([$campaignId]);

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'finalized',
            'campaign_id'  => $campaignId,
            'workspace_id' => $targetWsId,
            'message'      => 'Ação finalizada com sucesso! O sorteio foi encerrado e as vendas congeladas.'
        ]);
    }

    // 3.3 CRIAR OU EDITAR AÇÃO
    if ($action === 'save' || $action === 'create') {
        try {
            $booster = CampaignPage::boosterInput($input);
            $downsell = !empty($input['downsell_enabled']) ? CampaignPage::boosterInput(['booster_name'=>$input['downsell_name'] ?? '', 'booster_multiplier'=>$input['downsell_multiplier'] ?? null, 'booster_price'=>$input['downsell_price'] ?? null]) : null;
        }
        catch (\InvalidArgumentException $e) { JsonResponse::error($e->getMessage(), 422); }
        if ($campaignId && !CampaignPage::campaign($pdo, $targetWsId, $campaignId)) JsonResponse::error('Campanha não encontrada neste workspace.', 404);
        $title = trim($input['title'] ?? 'Nova Ação de Rifa');
        $ticketPrice = (float)($input['ticket_price'] ?? 10.00);
        $extPlatform = trim($input['external_platform'] ?? 'sorteamos');
        $extCampId = trim($input['external_campaign_id'] ?? ('camp_' . uniqid()));
        $status = in_array($input['status'] ?? '', ['published', 'active', 'ended', 'draft']) ? $input['status'] : 'published';

        $pdo->beginTransaction();
        if ($campaignId) {
            $stmtUp = $pdo->prepare("
                UPDATE campaigns SET
                    title = ?,
                    ticket_price = ?,
                    external_platform = ?,
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND workspace_id = ?
            ");
            $stmtUp->execute([$title, $ticketPrice, $extPlatform, $status, $campaignId, $targetWsId]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO campaigns (workspace_id, title, ticket_price, external_platform, external_campaign_id, status, published_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmtIns->execute([$targetWsId, $title, $ticketPrice, $extPlatform, $extCampId, $status]);
            $campaignId = (int)$pdo->lastInsertId();
        }

        $savedCampaign = CampaignPage::campaign($pdo, $targetWsId, $campaignId);
        if (!$savedCampaign) JsonResponse::error('Campanha não encontrada.', 404);
        CampaignPage::offer($pdo, $savedCampaign);
        if ($booster !== null) CampaignPage::saveBoosterDraft($pdo, $savedCampaign, $booster);
        if ($downsell !== null) CampaignPage::saveBoosterDraft($pdo, $savedCampaign, $downsell, 'downsell');
        $pdo->commit();

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'saved',
            'campaign_id'  => $campaignId,
            'workspace_id' => $targetWsId,
            'message'      => 'Dados da ação atualizados com sucesso!'
        ]);
    }

    // 3.4 DUPLICAR AÇÃO (Cria cópia independente da campanha e de sua pressel)
    if ($action === 'duplicate') {
        if (!$campaignId) {
            JsonResponse::error('ID da campanha não especificado para duplicação.', 422);
        }

        $stmtOrig = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND workspace_id = ?");
        $stmtOrig->execute([$campaignId, $targetWsId]);
        $orig = $stmtOrig->fetch(PDO::FETCH_ASSOC);
        if (!$orig) {
            JsonResponse::error('Campanha original não encontrada.', 404);
        }

        $newTitle = $orig['title'] . ' (Cópia)';
        $newExtId = 'camp_' . uniqid();

        $stmtIns = $pdo->prepare("
            INSERT INTO campaigns (workspace_id, title, ticket_price, external_platform, external_campaign_id, status, published_at)
            VALUES (?, ?, ?, ?, ?, 'draft', NULL)
        ");
        $stmtIns->execute([$targetWsId, $newTitle, $orig['ticket_price'], $orig['external_platform'], $newExtId]);
        $newCampId = (int)$pdo->lastInsertId();

        // Se houver funil/pressel vinculado, duplica também de forma independente
        $stmtFunnel = $pdo->prepare("SELECT * FROM funnels WHERE campaign_id = ? AND workspace_id = ? LIMIT 1");
        $stmtFunnel->execute([$campaignId, $targetWsId]);
        $origFunnel = $stmtFunnel->fetch(PDO::FETCH_ASSOC);

        $newFunnelId = null;
        if ($origFunnel) {
            $baseSlug = $origFunnel['slug'] . '-copia';
            $candidateSlug = $baseSlug;
            $suffix = 2;
            $slugCheck = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? LIMIT 1");
            do {
                $slugCheck->execute([$targetWsId, $candidateSlug]);
                $hasConflict = (bool)$slugCheck->fetchColumn();
                if ($hasConflict) {
                    $candidateSlug = $baseSlug . '-' . $suffix;
                    $suffix++;
                }
            } while ($hasConflict && $suffix < 100);

            $stmtDupFunnel = $pdo->prepare("
                INSERT INTO funnels (
                    workspace_id, campaign_id, custom_domain_id, name, slug, template_slug,
                    draft_content, published_content, html_content,
                    blocks_config, settings_config,
                    status, is_home, published_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'draft', 0, NULL)
            ");
            $stmtDupFunnel->execute([
                $targetWsId,
                $newCampId,
                $origFunnel['custom_domain_id'],
                $origFunnel['name'] . ' (Cópia)',
                $candidateSlug,
                $origFunnel['template_slug'] ?? 'default',
                $origFunnel['draft_content'],
                $origFunnel['draft_content'],
                $origFunnel['blocks_config'],
                $origFunnel['settings_config'],
            ]);
            $newFunnelId = (int)$pdo->lastInsertId();
        }

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'duplicated',
            'campaign_id'  => $newCampId,
            'funnel_id'    => $newFunnelId,
            'workspace_id' => $targetWsId,
            'message'      => 'Campanha duplicada com sucesso com configuração 100% independente!'
        ]);
    }

    JsonResponse::error('Ação desconhecida solicitada.', 400);
}

JsonResponse::error('Method not allowed', 405);
