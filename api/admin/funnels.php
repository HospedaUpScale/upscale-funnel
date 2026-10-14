<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Templates\TemplateRegistry;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

$targetWsId = (int)($request->get('workspace_id') ?? ($auth['workspace']->id ?? 2));
if ($targetWsId <= 0) $targetWsId = $auth['workspace']->id ?? 2;

if ($auth && $auth['role'] !== 'super_admin') {
    $allowedIds = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $descendant) $allowedIds[] = $descendant->id;
    if (!in_array($targetWsId, $allowedIds, true)) JsonResponse::error('Acesso negado para este workspace.', 403);
}

function funnelSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-');
}

function buildFunnelUrls(string $slug, ?string $domainName): array
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';

    if (!empty($domainName)) {
        return [
            'public_url' => 'https://' . $domainName . '/f/' . $slug,
            'direct_url' => 'https://' . $domainName . '/' . $slug,
        ];
    }

    return [
        'public_url' => $scheme . '://' . $host . '/f/' . $slug,
        'direct_url' => $scheme . '://' . $host . '/f/' . $slug,
    ];
}

// Ações rápidas GET (como listagem de ofertas, templates ou checagem de slug)
$action = $request->get('action') ?? ($request->all()['action'] ?? '');

// 0. Retornar catálogo de templates disponíveis
if ($action === 'get_templates') {
    JsonResponse::success(['templates' => TemplateRegistry::getAll()]);
}

if ($action === 'get_template_defaults') {
    $tplSlug = (string)($request->get('template_slug') ?? ($request->all()['template_slug'] ?? 'pressel-premium'));
    $template = TemplateRegistry::get($tplSlug);
    $config = TemplateRegistry::getDefaultConfig($tplSlug);
    JsonResponse::success([
        'template' => $template,
        'config' => $config,
    ]);
}

// 1. Retornar ofertas disponíveis para vincular no builder
if ($action === 'get_offers') {
    $stmt = $pdo->prepare("SELECT id, title, subtitle, price, quota_count, checkout_mode, slug, is_published, legacy_checkout_url FROM upsell_offers WHERE workspace_id = ? AND is_active = 1 ORDER BY id DESC");
    $stmt->execute([$targetWsId]);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $baseUrl = $scheme . '://' . $host;

    foreach ($offers as &$off) {
        $off['id'] = (int)$off['id'];
        $off['price'] = (float)$off['price'];
        $off['quota_count'] = (int)$off['quota_count'];
        $off['is_published'] = (bool)$off['is_published'];
        $off['public_url'] = !empty($off['slug']) ? $baseUrl . '/o/' . $off['slug'] : '';
    }

    JsonResponse::success(['offers' => $offers]);
}

// 2. Verificar disponibilidade de slug
if ($action === 'check_slug') {
    $rawSlug = (string)($request->get('slug') ?? ($request->all()['slug'] ?? ''));
    $slug = funnelSlug($rawSlug);
    $funnelId = (int)($request->get('funnel_id') ?? ($request->all()['funnel_id'] ?? 0));

    if (empty($slug) || strlen($slug) < 2) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'slug' => $slug,
            'reason' => 'O slug deve conter no mínimo 2 caracteres alfanuméricos.'
        ], 422);
    }

    $reserved = ['api', 'hub', 'assets', 'css', 'js', 'documentacao', 'migrations', 'scripts', 'src', 'storage', 'config', 'vendor', 'f', 'o', 'index', 'login', 'admin'];
    if (in_array($slug, $reserved, true)) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'slug' => $slug,
            'reason' => 'Este slug é uma palavra reservada do sistema.'
        ], 422);
    }

    $stmtSlug = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$targetWsId, $slug, $funnelId]);
    $exists = $stmtSlug->fetch();

    if ($exists) {
        JsonResponse::send([
            'status' => 'error',
            'available' => false,
            'slug' => $slug,
            'reason' => 'Este slug já está em uso por outra página deste cliente.'
        ]);
    }

    $urls = buildFunnelUrls($slug, null);
    JsonResponse::success([
        'available' => true,
        'slug' => $slug,
        'public_url' => $urls['public_url']
    ]);
}

if ($request->method() === 'GET') {
    $campFilter = (int)($request->get('campaign_id') ?? 0);
    if ($campFilter > 0) {
        $stmt = $pdo->prepare("SELECT f.*, d.domain FROM funnels f LEFT JOIN custom_domains d ON d.id = f.custom_domain_id WHERE f.workspace_id = ? AND f.campaign_id = ? ORDER BY f.id DESC");
        $stmt->execute([$targetWsId, $campFilter]);
    } else {
        $stmt = $pdo->prepare("SELECT f.*, d.domain FROM funnels f LEFT JOIN custom_domains d ON d.id = f.custom_domain_id WHERE f.workspace_id = ? ORDER BY f.id DESC");
        $stmt->execute([$targetWsId]);
    }
    $funnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($funnels as &$funnel) {
        $funnel['id'] = (int)$funnel['id'];
        $funnel['workspace_id'] = (int)$funnel['workspace_id'];
        $funnel['campaign_id'] = $funnel['campaign_id'] !== null ? (int)$funnel['campaign_id'] : null;
        $funnel['custom_domain_id'] = $funnel['custom_domain_id'] !== null ? (int)$funnel['custom_domain_id'] : null;
        $funnel['template_slug'] = $funnel['template_slug'] ?? 'default';
        $funnel['is_home'] = false; // A raspadinha tem prioridade absoluta na raiz
        $funnel['is_published'] = ($funnel['status'] === 'published');
        $funnel['draft_content'] = $funnel['draft_content'] ?? $funnel['html_content'];
        $funnel['published_content'] = $funnel['published_content'] ?? null;
        $urls = buildFunnelUrls($funnel['slug'], $funnel['domain'] ?? null);
        $funnel['public_url'] = $urls['public_url'];
        $funnel['direct_url'] = $urls['direct_url'];
    }
    JsonResponse::success(['funnels' => $funnels]);
}

if ($request->method() === 'POST') {
    $input = $request->all();
    $id = (int)($input['id'] ?? ($input['funnel_id'] ?? 0));
    $postAction = $input['action'] ?? 'save';

    // Despublicar
    if ($postAction === 'unpublish') {
        if ($id <= 0) JsonResponse::error('ID do funil não especificado.', 422);
        $stmtUnpub = $pdo->prepare("UPDATE funnels SET status = 'draft', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND workspace_id = ?");
        $stmtUnpub->execute([$id, $targetWsId]);
        JsonResponse::success(['message' => 'Página despublicada com sucesso. Ela não está mais acessível publicamente.', 'status' => 'draft']);
    }

    // Duplicar Funil como cópia independente
    if ($postAction === 'duplicate') {
        if ($id <= 0) JsonResponse::error('ID da página não especificado.', 422);
        $stmtOrig = $pdo->prepare("SELECT * FROM funnels WHERE id = ? AND workspace_id = ?");
        $stmtOrig->execute([$id, $targetWsId]);
        $orig = $stmtOrig->fetch(PDO::FETCH_ASSOC);
        if (!$orig) JsonResponse::error('Página original não encontrada.', 404);

        $dupName = $orig['name'] . ' (Cópia)';
        $baseSlug = funnelSlug($orig['slug'] . '-copia');
        $candidateSlug = $baseSlug;
        $suffix = 2;
        $slugStmt = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? LIMIT 1");
        do {
            $slugStmt->execute([$targetWsId, $candidateSlug]);
            $hasConflict = (bool)$slugStmt->fetchColumn();
            if ($hasConflict) {
                $candidateSlug = $baseSlug . '-' . $suffix;
                $suffix++;
            }
        } while ($hasConflict && $suffix < 100);

        $dupCampaignId = !empty($input['campaign_id']) ? (int)$input['campaign_id'] : ($orig['campaign_id'] !== null ? (int)$orig['campaign_id'] : null);

        $stmtDup = $pdo->prepare("
            INSERT INTO funnels (
                workspace_id, campaign_id, custom_domain_id, name, slug, template_slug,
                draft_content, published_content, html_content,
                blocks_config, settings_config,
                status, is_home, published_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'draft', 0, NULL)
        ");
        $stmtDup->execute([
            $targetWsId,
            $dupCampaignId,
            $orig['custom_domain_id'],
            $dupName,
            $candidateSlug,
            $orig['template_slug'] ?? 'default',
            $orig['draft_content'],
            $orig['draft_content'],
            $orig['blocks_config'],
            $orig['settings_config'],
        ]);
        $newFunnelId = (int)$pdo->lastInsertId();
        $urls = buildFunnelUrls($candidateSlug, null);

        JsonResponse::success([
            'message' => 'Página duplicada com sucesso como rascunho independente.',
            'funnel_id' => $newFunnelId,
            'name' => $dupName,
            'slug' => $candidateSlug,
            'status' => 'draft',
            'template_slug' => $orig['template_slug'] ?? 'default',
            'public_url' => $urls['public_url'],
            'direct_url' => $urls['direct_url'],
        ]);
    }

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') $name = 'Página ' . date('d/m H:i');
    $rawSlug = trim((string)($input['slug'] ?? ''));
    if ($rawSlug === '') $rawSlug = $name;
    $slug = funnelSlug($rawSlug);
    if ($slug === '') $slug = 'pagina-' . substr(md5(uniqid('', true)), 0, 6);

    $templateSlug = trim((string)($input['template_slug'] ?? 'default'));
    $campaignId = !empty($input['campaign_id']) ? (int)$input['campaign_id'] : null;

    $blocksConfig = isset($input['blocks_config']) ? (is_string($input['blocks_config']) ? $input['blocks_config'] : json_encode($input['blocks_config'], JSON_UNESCAPED_UNICODE)) : null;
    $settingsConfig = isset($input['settings_config']) ? (is_string($input['settings_config']) ? $input['settings_config'] : json_encode($input['settings_config'], JSON_UNESCAPED_UNICODE)) : null;

    $html = (string)($input['html_content'] ?? ($input['draft_content'] ?? ($input['html'] ?? '')));

    // Se for template pressel-premium e o HTML estiver vazio ou foi solicitado auto-render
    if ($templateSlug === 'pressel-premium' && (trim($html) === '' || !empty($input['auto_render']))) {
        $bArray = $blocksConfig ? json_decode($blocksConfig, true) : [];
        $sArray = $settingsConfig ? json_decode($settingsConfig, true) : [];
        $html = TemplateRegistry::render('pressel-premium', $bArray ?: [], $sArray ?: [], ['is_editor' => false]);
    }

    $isPublishAction = ($postAction === 'publish' || ($input['status'] ?? '') === 'published');
    $status = $isPublishAction ? 'published' : 'draft';
    $domainId = !empty($input['custom_domain_id']) ? (int)$input['custom_domain_id'] : null;

    if (trim($html) === '') {
        $html = '<div style="font-family:sans-serif; text-align:center; padding:50px;"><h1>Nova Página</h1><p>Comece a editar com o construtor visual.</p></div>';
    }
    if (strlen($html) > 5000000) JsonResponse::error('O HTML ultrapassa o limite de 5 MB.', 413);

    $domainName = null;
    if ($domainId !== null) {
        $domainStmt = $pdo->prepare("SELECT id, domain FROM custom_domains WHERE id = ? AND workspace_id = ?");
        $domainStmt->execute([$domainId, $targetWsId]);
        $domainRow = $domainStmt->fetch();
        if (!$domainRow) JsonResponse::error('Domínio inválido para este workspace.', 422);
        $domainName = $domainRow['domain'];
    } else {
        $mainDomainStmt = $pdo->prepare("SELECT domain FROM custom_domains WHERE workspace_id = ? AND status = 'active' ORDER BY is_primary DESC, id ASC LIMIT 1");
        $mainDomainStmt->execute([$targetWsId]);
        $domainName = $mainDomainStmt->fetchColumn() ?: null;
    }

    // Validação de unicidade de slug
    $slugStmt = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? AND id <> ? LIMIT 1");
    $slugStmt->execute([$targetWsId, $slug, $id]);
    $slugConflictId = (int)($slugStmt->fetchColumn() ?: 0);
    if ($slugConflictId > 0) {
        if ($id === 0) {
            $suffix = 2;
            do {
                $candidateSlug = $slug . '-' . $suffix;
                $slugStmt->execute([$targetWsId, $candidateSlug, $id]);
                $hasConflict = (bool)$slugStmt->fetchColumn();
                $suffix++;
            } while ($hasConflict && $suffix < 100);
            $slug = $candidateSlug;
        } else {
            JsonResponse::error(
                'Este slug já pertence a outra página deste cliente. Escolha outro slug.',
                409,
                ['code' => 'funnel_slug_in_use', 'funnel_id' => $slugConflictId]
            );
        }
    }

    try {
        if ($id > 0) {
            $existingStmt = $pdo->prepare("SELECT id, published_content, status FROM funnels WHERE id = ? AND workspace_id = ?");
            $existingStmt->execute([$id, $targetWsId]);
            $existingRow = $existingStmt->fetch();
            if (!$existingRow) JsonResponse::error('Funil não encontrado.', 404);

            if ($isPublishAction) {
                // Publicar: copia draft para published_content e html_content, seta status = 'published'
                $stmt = $pdo->prepare("
                    UPDATE funnels SET
                        custom_domain_id = ?, campaign_id = ?, name = ?, slug = ?, template_slug = ?,
                        draft_content = ?, published_content = ?, html_content = ?,
                        blocks_config = ?, settings_config = ?,
                        status = 'published', is_home = 0,
                        published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND workspace_id = ?
                ");
                $stmt->execute([
                    $domainId, $campaignId, $name, $slug, $templateSlug,
                    $html, $html, $html,
                    $blocksConfig, $settingsConfig,
                    $id, $targetWsId
                ]);
            } else {
                // Salvar rascunho: atualiza draft_content, mantendo published_content inalterado
                $stmt = $pdo->prepare("
                    UPDATE funnels SET
                        custom_domain_id = ?, campaign_id = ?, name = ?, slug = ?, template_slug = ?,
                        draft_content = ?, html_content = ?,
                        blocks_config = ?, settings_config = ?,
                        is_home = 0, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND workspace_id = ?
                ");
                $stmt->execute([
                    $domainId, $campaignId, $name, $slug, $templateSlug,
                    $html, $html,
                    $blocksConfig, $settingsConfig,
                    $id, $targetWsId
                ]);
            }
            $funnelId = $id;
        } else {
            // Inserir novo
            $publishedContent = $isPublishAction ? $html : null;
            $stmt = $pdo->prepare("
                INSERT INTO funnels (
                    workspace_id, campaign_id, custom_domain_id, name, slug, template_slug,
                    draft_content, published_content, html_content,
                    blocks_config, settings_config,
                    status, is_home, published_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, CASE WHEN ? = 'published' THEN CURRENT_TIMESTAMP ELSE NULL END)
            ");
            $stmt->execute([
                $targetWsId, $campaignId, $domainId, $name, $slug, $templateSlug,
                $html, $publishedContent, $html,
                $blocksConfig, $settingsConfig,
                $status, $status
            ]);
            $funnelId = (int)$pdo->lastInsertId();
        }

        $pdo->prepare("UPDATE funnels SET is_home = 0 WHERE workspace_id = ?")->execute([$targetWsId]);
    } catch (PDOException $e) {
        JsonResponse::error('Não foi possível salvar a página agora: ' . $e->getMessage(), 500, ['code' => 'funnel_save_failed']);
    }

    $urls = buildFunnelUrls($slug, $domainName);

    JsonResponse::success([
        'message' => $isPublishAction ? 'Página publicada com sucesso! Link exclusivo disponível.' : 'Rascunho da página salvo com sucesso.',
        'funnel_id' => $funnelId,
        'status_value' => $status,
        'is_published' => $isPublishAction,
        'name' => $name,
        'slug' => $slug,
        'template_slug' => $templateSlug,
        'campaign_id' => $campaignId,
        'domain' => $domainName,
        'public_url' => $urls['public_url'],
        'direct_url' => $urls['direct_url'],
    ]);
}


if ($request->method() === 'DELETE') {
    $id = (int)($request->get('id') ?? 0);
    $stmt = $pdo->prepare("DELETE FROM funnels WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$id, $targetWsId]);
    if ($stmt->rowCount() === 0) JsonResponse::error('Funil não encontrado.', 404);
    JsonResponse::success(['message' => 'Página removida com sucesso.']);
}

JsonResponse::error('Method not allowed', 405);

