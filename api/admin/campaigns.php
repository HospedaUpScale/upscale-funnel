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

    // Busca campanhas com detalhes da oferta vinculada e volume de vendas
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            o.id as offer_id,
            o.title as offer_title,
            o.price as offer_price,
            o.quota_count as offer_quotas,
            o.is_active as offer_is_active,
            o.checkout_mode,
            (SELECT COUNT(*) FROM orders ord WHERE ord.workspace_id = c.workspace_id AND ord.status = 'paid') as total_orders_paid,
            (SELECT COALESCE(SUM(ord.total_amount), 0) FROM orders ord WHERE ord.workspace_id = c.workspace_id AND ord.status = 'paid') as total_volume_paid
        FROM campaigns c
        LEFT JOIN upsell_offers o ON o.campaign_id = c.id
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

    $formatted = array_map(function($c) {
        $statusNormalized = in_array($c['status'], ['active', 'published']) ? 'published' : ($c['status'] === 'ended' ? 'ended' : 'draft');
        return [
            'id'                   => (int)$c['id'],
            'workspace_id'         => (int)$c['workspace_id'],
            'title'                => $c['title'],
            'ticket_price'         => (float)$c['ticket_price'],
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
                'title'         => $c['offer_title'],
                'price'         => (float)$c['offer_price'],
                'quota_count'   => (int)$c['offer_quotas'],
                'is_active'     => (bool)$c['offer_is_active'],
                'checkout_mode' => $c['checkout_mode']
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
        $pdo->prepare("UPDATE upsell_offers SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE campaign_id = ? OR (workspace_id = ? AND is_active = 0)")
            ->execute([$campaignId, $targetWsId]);

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
        $title = trim($input['title'] ?? 'Nova Ação de Rifa');
        $ticketPrice = (float)($input['ticket_price'] ?? 10.00);
        $extPlatform = trim($input['external_platform'] ?? 'sorteamos');
        $extCampId = trim($input['external_campaign_id'] ?? ('camp_' . uniqid()));
        $status = in_array($input['status'] ?? '', ['published', 'active', 'ended', 'draft']) ? $input['status'] : 'published';

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

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'saved',
            'campaign_id'  => $campaignId,
            'workspace_id' => $targetWsId,
            'message'      => 'Dados da ação atualizados com sucesso!'
        ]);
    }

    JsonResponse::error('Ação desconhecida solicitada.', 400);
}

JsonResponse::error('Method not allowed', 405);
