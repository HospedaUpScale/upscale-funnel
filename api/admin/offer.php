<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Support\OfferTheme;

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
    $targetWsId = ($auth && $auth['workspace']) ? $auth['workspace']->id : 2;
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

    $mergedTheme = OfferTheme::merge($themeConfig);

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

    // Oferta ativa atual (mantém valores salvos que o payload não enviou)
    $stmt = $pdo->prepare("SELECT id, theme_config FROM upsell_offers WHERE workspace_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
    $stmt->execute([$targetWsId]);
    $existing = $stmt->fetch();

    $storedTheme = [];
    if ($existing && !empty($existing['theme_config'])) {
        $decodedStored = is_string($existing['theme_config']) ? json_decode($existing['theme_config'], true) : $existing['theme_config'];
        if (is_array($decodedStored)) {
            $storedTheme = $decodedStored;
        }
    }

    $theme = OfferTheme::fromInput($input, $storedTheme, $title, $price);
    $themeJson = json_encode($theme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
