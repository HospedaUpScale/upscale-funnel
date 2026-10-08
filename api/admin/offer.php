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
    $targetWsId = ($auth && $auth['workspace']) ? $auth['workspace']->id : 1;
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

// Helper para normalizar slug
function normalizeOfferSlug(string $raw): string {
    $slug = mb_strtolower(trim($raw), 'UTF-8');
    // Remove acentos
    $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
    if ($translit !== false) {
        $slug = $translit;
    }
    $slug = preg_replace('~[^-\w]+~', '', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('~-+~', '-', $slug);
    return strtolower($slug);
}

// Determinar baseUrl do sistema
function getSystemBaseUrl(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    return $scheme . '://' . $host;
}

// Ação de checagem de slug (via GET ou POST)
$action = $request->get('action') ?? ($request->all()['action'] ?? '');

if ($action === 'check_slug') {
    $rawSlug = (string)($request->get('slug') ?? ($request->all()['slug'] ?? ''));
    $normalized = normalizeOfferSlug($rawSlug);
    $offerId = (int)($request->get('offer_id') ?? ($request->all()['offer_id'] ?? 0));

    if (empty($normalized) || strlen($normalized) < 3) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'normalized_slug' => $normalized,
            'reason' => 'O slug precisa ter no mínimo 3 caracteres alfanuméricos.'
        ], 422);
    }

    $reservedSlugs = ['api', 'hub', 'assets', 'css', 'js', 'documentacao', 'migrations', 'scripts', 'src', 'storage', 'config', 'vendor', 'f', 'o', 'index', 'login', 'admin'];
    if (in_array($normalized, $reservedSlugs, true)) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'normalized_slug' => $normalized,
            'reason' => 'Este slug é uma palavra reservada do sistema.'
        ], 422);
    }

    $stmtCheck = $pdo->prepare("SELECT id FROM upsell_offers WHERE slug = ? AND id != ? LIMIT 1");
    $stmtCheck->execute([$normalized, $offerId]);
    $existingCol = $stmtCheck->fetch();

    if ($existingCol) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'normalized_slug' => $normalized,
            'reason' => 'Este slug já está em uso por outra oferta.'
        ]);
    }

    JsonResponse::send([
        'status' => 'success',
        'available' => true,
        'normalized_slug' => $normalized,
        'public_url' => getSystemBaseUrl() . '/o/' . $normalized
    ]);
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
        $defaultSlug = 'kit-bobzao-booster-200x';
        $stmtIns = $pdo->prepare("
            INSERT INTO upsell_offers (
                workspace_id, title, subtitle, price, quota_count, checkout_mode, 
                slug, is_published, published_at, is_active
            ) VALUES (?, 'Booster 200X Ouro', 'Kit Bobzão Bitruck + Ford F250', 90.00, 200, 'pix_native', ?, 1, CURRENT_TIMESTAMP, 1)
        ");
        $stmtIns->execute([$targetWsId, $defaultSlug]);
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

    $draftConfig = [];
    if (!empty($offer['draft_config'])) {
        $decDraft = is_string($offer['draft_config']) ? json_decode($offer['draft_config'], true) : $offer['draft_config'];
        if (is_array($decDraft)) {
            $draftConfig = $decDraft;
        }
    }

    $publishedConfig = [];
    if (!empty($offer['published_config'])) {
        $decPub = is_string($offer['published_config']) ? json_decode($offer['published_config'], true) : $offer['published_config'];
        if (is_array($decPub)) {
            $publishedConfig = $decPub;
        }
    }

    $mergedTheme = OfferTheme::merge($themeConfig);
    $slug = !empty($offer['slug']) ? $offer['slug'] : normalizeOfferSlug($offer['title'] ?? 'booster-oferta');
    $isPublished = (bool)($offer['is_published'] ?? 0);
    $baseUrl = getSystemBaseUrl();

    JsonResponse::send([
        'status' => 'success',
        'offer'  => [
            'id'                  => (int)$offer['id'],
            'workspace_id'        => (int)$offer['workspace_id'],
            'title'               => $offer['title'] ?? 'Booster 200X Ouro',
            'subtitle'            => $offer['subtitle'] ?? 'Kit Bobzão Bitruck + Ford F250',
            'price'               => (float)($offer['price'] ?? 90.00),
            'quota_count'         => (int)($offer['quota_count'] ?? 200),
            'vturb_account_id'    => $offer['vturb_account_id'] ?? '',
            'vturb_player_id'     => $offer['vturb_player_id'] ?? '',
            'legacy_checkout_url' => $offer['legacy_checkout_url'] ?? '',
            'checkout_mode'       => $offer['checkout_mode'] ?? 'pix_native',
            'slug'                => $slug,
            'is_published'        => $isPublished,
            'published_at'        => $offer['published_at'] ?? null,
            'public_url'          => $baseUrl . '/o/' . $slug,
            'theme'               => $mergedTheme,
            'draft_config'        => $draftConfig,
            'published_config'    => $publishedConfig,
        ]
    ]);
}

// 3. POST: Salvar rascunho, publicar ou despublicar
if ($request->method() === 'POST') {
    $input = $request->all();

    // Buscar oferta existente
    $stmt = $pdo->prepare("SELECT * FROM upsell_offers WHERE workspace_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
    $stmt->execute([$targetWsId]);
    $existing = $stmt->fetch();

    $offerId = $existing ? (int)$existing['id'] : 0;

    // Despublicar oferta
    if ($action === 'unpublish') {
        if ($existing) {
            $unpubStmt = $pdo->prepare("UPDATE upsell_offers SET is_published = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $unpubStmt->execute([$existing['id']]);
        }
        JsonResponse::send([
            'status'  => 'success',
            'message' => 'Oferta despublicada com sucesso. Ela não está mais acessível publicamente.',
            'is_published' => false
        ]);
    }

    $title = trim($input['title'] ?? ($existing['title'] ?? 'Booster 200X Ouro'));
    $subtitle = trim($input['subtitle'] ?? ($existing['subtitle'] ?? 'Kit Bobzão Bitruck + Ford F250'));
    $price = (float)($input['price'] ?? ($existing['price'] ?? 90.00));
    $quotaCount = (int)($input['quota_count'] ?? ($existing['quota_count'] ?? 200));
    $vturbAccountId = trim($input['vturb_account_id'] ?? ($existing['vturb_account_id'] ?? ''));
    $vturbPlayerId = trim($input['vturb_player_id'] ?? ($existing['vturb_player_id'] ?? ''));
    $legacyCheckoutUrl = trim($input['legacy_checkout_url'] ?? ($existing['legacy_checkout_url'] ?? ''));
    $checkoutMode = ($input['checkout_mode'] ?? ($existing['checkout_mode'] ?? 'pix_native')) === 'external_redirect' ? 'external_redirect' : 'pix_native';

    // Tratar slug
    $rawSlug = trim($input['slug'] ?? ($existing['slug'] ?? ''));
    if (empty($rawSlug)) {
        $rawSlug = $title;
    }
    $slug = normalizeOfferSlug($rawSlug);
    if (empty($slug)) {
        $slug = 'booster-' . ($existing ? $existing['id'] : time());
    }

    // Validar unicidade de slug se for publicar
    if ($action === 'publish') {
        $stmtSlugCheck = $pdo->prepare("SELECT id FROM upsell_offers WHERE slug = ? AND id != ? LIMIT 1");
        $stmtSlugCheck->execute([$slug, $offerId]);
        if ($stmtSlugCheck->fetch()) {
            JsonResponse::error("O endereço /o/{$slug} já está em uso por outra oferta. Escolha outro slug.", 422);
        }
    }

    $storedTheme = [];
    if ($existing && !empty($existing['theme_config'])) {
        $decodedStored = is_string($existing['theme_config']) ? json_decode($existing['theme_config'], true) : $existing['theme_config'];
        if (is_array($decodedStored)) {
            $storedTheme = $decodedStored;
        }
    }

    $theme = OfferTheme::fromInput($input, $storedTheme, $title, $price);
    $themeJson = json_encode($theme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Estrutura completa de dados da oferta para congelamento em JSON
    $fullConfig = [
        'title'               => $title,
        'subtitle'            => $subtitle,
        'price'               => $price,
        'quota_count'         => $quotaCount,
        'vturb_account_id'    => $vturbAccountId,
        'vturb_player_id'     => $vturbPlayerId,
        'legacy_checkout_url' => $legacyCheckoutUrl,
        'checkout_mode'       => $checkoutMode,
        'slug'                => $slug,
        'theme'               => $theme,
        'updated_at'          => date('Y-m-d H:i:s'),
    ];
    $draftJson = json_encode($fullConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($action === 'publish') {
        // Copia draft para published_config, marca is_published = 1 e atualiza published_at
        $publishedJson = $draftJson;
        if ($existing) {
            $stmtUp = $pdo->prepare("
                UPDATE upsell_offers SET
                    title = ?, subtitle = ?, price = ?, quota_count = ?,
                    vturb_account_id = ?, vturb_player_id = ?, legacy_checkout_url = ?,
                    checkout_mode = ?, theme_config = ?, draft_config = ?,
                    published_config = ?, slug = ?, is_published = 1,
                    published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmtUp->execute([
                $title, $subtitle, $price, $quotaCount,
                $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
                $checkoutMode, $themeJson, $draftJson,
                $publishedJson, $slug, $existing['id']
            ]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO upsell_offers (
                    workspace_id, title, subtitle, price, quota_count,
                    vturb_account_id, vturb_player_id, legacy_checkout_url,
                    checkout_mode, theme_config, draft_config, published_config,
                    slug, is_published, published_at, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, 1)
            ");
            $stmtIns->execute([
                $targetWsId, $title, $subtitle, $price, $quotaCount,
                $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
                $checkoutMode, $themeJson, $draftJson, $publishedJson, $slug
            ]);
            $offerId = (int)$pdo->lastInsertId();
        }

        $publicUrl = getSystemBaseUrl() . '/o/' . $slug;

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'publish',
            'message'      => 'Oferta publicada com sucesso no link público!',
            'offer_id'     => $offerId,
            'slug'         => $slug,
            'is_published' => true,
            'public_url'   => $publicUrl,
        ]);
    } else {
        // Salvar Rascunho (save_draft ou POST padrão)
        if ($existing) {
            $stmtUp = $pdo->prepare("
                UPDATE upsell_offers SET
                    title = ?, subtitle = ?, price = ?, quota_count = ?,
                    vturb_account_id = ?, vturb_player_id = ?, legacy_checkout_url = ?,
                    checkout_mode = ?, theme_config = ?, draft_config = ?,
                    slug = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmtUp->execute([
                $title, $subtitle, $price, $quotaCount,
                $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
                $checkoutMode, $themeJson, $draftJson, $slug, $existing['id']
            ]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO upsell_offers (
                    workspace_id, title, subtitle, price, quota_count,
                    vturb_account_id, vturb_player_id, legacy_checkout_url,
                    checkout_mode, theme_config, draft_config,
                    slug, is_published, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
            ");
            $stmtIns->execute([
                $targetWsId, $title, $subtitle, $price, $quotaCount,
                $vturbAccountId, $vturbPlayerId, $legacyCheckoutUrl,
                $checkoutMode, $themeJson, $draftJson, $slug
            ]);
            $offerId = (int)$pdo->lastInsertId();
        }

        $publicUrl = getSystemBaseUrl() . '/o/' . $slug;

        JsonResponse::send([
            'status'       => 'success',
            'action'       => 'save_draft',
            'message'      => 'Rascunho da oferta salvo com sucesso!',
            'offer_id'     => $offerId,
            'slug'         => $slug,
            'is_published' => (bool)($existing['is_published'] ?? false),
            'public_url'   => $publicUrl,
        ]);
    }
}

JsonResponse::error('Method not allowed', 405);

