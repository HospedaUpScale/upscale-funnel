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

// 1. Determinar target workspace e validar permissões
$targetWsId = (int)($request->get('workspace_id') ?? ($request->all()['workspace_id'] ?? ($auth['workspace']->id ?? 1)));

if ($targetWsId === 0) {
    // Se passou 0, pega o primeiro workspace elegível
    $targetWsId = $auth['workspace'] ? $auth['workspace']->id : 2;
}

if ($auth && $auth['role'] !== 'super_admin') {
    if ($auth['workspace']->id !== $targetWsId) {
        $descendants = $wsRepo->findDescendants($auth['workspace']->id);
        $allowedIds = array_map(fn($w) => $w->id, $descendants);
        if (!in_array($targetWsId, $allowedIds, true)) {
            JsonResponse::error('Acesso negado para esta oferta/subconta.', 403);
        }
    }
}

// 2. GET: Carregar dados completos da oferta ativa
if ($request->method() === 'GET') {
    $stmt = $pdo->prepare("
        SELECT * FROM upsell_offers 
        WHERE workspace_id = ? AND is_active = 1 
        ORDER BY id ASC LIMIT 1
    ");
    $stmt->execute([$targetWsId]);
    $offer = $stmt->fetch();

    if (!$offer) {
        // Cria oferta padrão caso ainda não exista para este workspace
        $stmtIns = $pdo->prepare("
            INSERT INTO upsell_offers (workspace_id, title, subtitle, price, quota_count, checkout_mode, is_active)
            VALUES (?, 'Booster 110X', 'Kit Bobzão Bitruck + Ford F250', 90.00, 110, 'pix_native', 1)
        ");
        $stmtIns->execute([$targetWsId]);
        $offerId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT * FROM upsell_offers WHERE id = ?");
        $stmt->execute([$offerId]);
        $offer = $stmt->fetch();
    }

    $themeConfig = [];
    if (!empty($offer['theme_config'])) {
        $decoded = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
        if (is_array($decoded)) {
            $themeConfig = $decoded;
        }
    }

    // Default theme config merging
    $defaultTheme = [
        'primary_color'       => '#0229C4',
        'accent_color'        => '#9FE870',
        'card_bg'             => '#05153F',
        'cta_bg'              => '#0229C4',
        'foil_start'          => '#021F96',
        'foil_end'            => '#3A5BE8',
        'eyebrow'             => 'OFERTA EXCLUSIVA DESBLOQUEADA',
        'greeting_pattern'    => 'Parabéns, {nome}! Você desbloqueou uma chance única.',
        'scratch_inst_top'    => 'RASPE COM O DEDO',
        'scratch_inst_sub'    => 'REVELE SUA OFERTA EXCLUSIVA',
        'cta_label'           => 'ATIVAR MEU BOOSTER 110X',
        'cta_subtext'         => 'Liberação imediata via Pix em 1 clique',
        'modal_win_title'     => 'PARABÉNS! VOCÊ DESBLOQUEOU',
        'anchor_price'        => 180.00,
        'timer_minutes'       => 3,
        'scratch_threshold'   => 22,
        'pitch_delay'         => 0,
        'video_src'           => '',
    ];

    $mergedTheme = array_merge($defaultTheme, $themeConfig);

    JsonResponse::send([
        'status' => 'success',
        'offer'  => [
            'id'                  => (int)$offer['id'],
            'workspace_id'        => (int)$offer['workspace_id'],
            'title'               => $offer['title'] ?? 'Booster 110X',
            'subtitle'            => $offer['subtitle'] ?? '',
            'price'               => (float)($offer['price'] ?? 90.00),
            'quota_count'         => (int)($offer['quota_count'] ?? 110),
            'vturb_account_id'    => $offer['vturb_account_id'] ?? '',
            'vturb_player_id'     => $offer['vturb_player_id'] ?? '',
            'legacy_checkout_url' => $offer['legacy_checkout_url'] ?? '',
            'checkout_mode'       => $offer['checkout_mode'] ?? 'pix_native',
            'theme'               => $mergedTheme,
        ]
    ]);
}

// 3. POST: Salvar alterações na oferta
if ($request->method() === 'POST') {
    $input = $request->all();

    $title = trim($input['title'] ?? 'Booster 110X');
    $subtitle = trim($input['subtitle'] ?? '');
    $price = (float)($input['price'] ?? 90.00);
    $quotaCount = (int)($input['quota_count'] ?? 110);
    $vturbAccountId = trim($input['vturb_account_id'] ?? '');
    $vturbPlayerId = trim($input['vturb_player_id'] ?? '');
    $legacyCheckoutUrl = trim($input['legacy_checkout_url'] ?? '');
    $checkoutMode = ($input['checkout_mode'] ?? 'pix_native') === 'external_redirect' ? 'external_redirect' : 'pix_native';

    // Monta o theme_config com todos os blocos editáveis
    $theme = [
        'primary_color'       => $input['theme']['primary_color'] ?? $input['primary_color'] ?? '#0229C4',
        'accent_color'        => $input['theme']['accent_color'] ?? $input['accent_color'] ?? '#9FE870',
        'card_bg'             => $input['theme']['card_bg'] ?? $input['card_bg'] ?? '#05153F',
        'cta_bg'              => $input['theme']['cta_bg'] ?? $input['cta_bg'] ?? '#0229C4',
        'foil_start'          => $input['theme']['foil_start'] ?? $input['foil_start'] ?? '#021F96',
        'foil_end'            => $input['theme']['foil_end'] ?? $input['foil_end'] ?? '#3A5BE8',
        'eyebrow'             => $input['theme']['eyebrow'] ?? $input['eyebrow'] ?? 'OFERTA EXCLUSIVA DESBLOQUEADA',
        'greeting_pattern'    => $input['theme']['greeting_pattern'] ?? $input['greeting_pattern'] ?? 'Parabéns, {nome}! Você desbloqueou uma chance única.',
        'scratch_inst_top'    => $input['theme']['scratch_inst_top'] ?? $input['scratch_inst_top'] ?? 'RASPE COM O DEDO',
        'scratch_inst_sub'    => $input['theme']['scratch_inst_sub'] ?? $input['scratch_inst_sub'] ?? 'REVELE SUA OFERTA EXCLUSIVA',
        'cta_label'           => $input['theme']['cta_label'] ?? $input['cta_label'] ?? ($title ? "ATIVAR MEU {$title}" : 'ATIVAR MEU BOOSTER 110X'),
        'cta_subtext'         => $input['theme']['cta_subtext'] ?? $input['cta_subtext'] ?? 'Liberação imediata via Pix em 1 clique',
        'modal_win_title'     => $input['theme']['modal_win_title'] ?? $input['modal_win_title'] ?? 'PARABÉNS! VOCÊ DESBLOQUEOU',
        'anchor_price'        => (float)($input['theme']['anchor_price'] ?? $input['anchor_price'] ?? ($price * 2)),
        'timer_minutes'       => (float)($input['theme']['timer_minutes'] ?? $input['timer_minutes'] ?? 3),
        'scratch_threshold'   => (int)($input['theme']['scratch_threshold'] ?? $input['scratch_threshold'] ?? 22),
        'pitch_delay'         => (int)($input['theme']['pitch_delay'] ?? $input['pitch_delay'] ?? 0),
        'video_src'           => trim($input['theme']['video_src'] ?? $input['video_src'] ?? ''),
    ];

    $themeJson = json_encode($theme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Verifica se já existe oferta ativa para o workspace
    $stmt = $pdo->prepare("SELECT id FROM upsell_offers WHERE workspace_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$targetWsId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $updateStmt = $pdo->prepare("
            UPDATE upsell_offers SET
                title = ?,
                subtitle = ?,
                price = ?,
                quota_count = ?,
                vturb_account_id = ?,
                vturb_player_id = ?,
                legacy_checkout_url = ?,
                checkout_mode = ?,
                theme_config = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([
            $title, $subtitle, $price, $quotaCount,
            $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
            $checkoutMode, $themeJson, $existing['id']
        ]);
        $offerId = (int)$existing['id'];
    } else {
        $insStmt = $pdo->prepare("
            INSERT INTO upsell_offers (
                workspace_id, title, subtitle, price, quota_count,
                vturb_account_id, vturb_player_id, legacy_checkout_url,
                checkout_mode, theme_config, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $insStmt->execute([
            $targetWsId, $title, $subtitle, $price, $quotaCount,
            $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
            $checkoutMode, $themeJson
        ]);
        $offerId = (int)$pdo->lastInsertId();
    }

    JsonResponse::send([
        'status'  => 'success',
        'message' => 'Configurações de blocos, textos, links e cores da oferta salvas com sucesso!',
        'offer_id'=> $offerId,
        'workspace_id' => $targetWsId,
    ]);
}

JsonResponse::error('Method not allowed', 405);
