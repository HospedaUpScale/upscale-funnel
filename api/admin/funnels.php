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

if ($request->method() === 'GET') {
    $stmt = $pdo->prepare("SELECT f.*, d.domain FROM funnels f LEFT JOIN custom_domains d ON d.id = f.custom_domain_id WHERE f.workspace_id = ? ORDER BY f.id DESC");
    $stmt->execute([$targetWsId]);
    $funnels = $stmt->fetchAll();
    foreach ($funnels as &$funnel) {
        $funnel['id'] = (int)$funnel['id'];
        $funnel['workspace_id'] = (int)$funnel['workspace_id'];
        $funnel['custom_domain_id'] = $funnel['custom_domain_id'] !== null ? (int)$funnel['custom_domain_id'] : null;
        $funnel['is_home'] = false; // A raspadinha tem prioridade absoluta na raiz
        $urls = buildFunnelUrls($funnel['slug'], $funnel['domain'] ?? null);
        $funnel['public_url'] = $urls['public_url'];
        $funnel['direct_url'] = $urls['direct_url'];
    }
    JsonResponse::success(['funnels' => $funnels]);
}

if ($request->method() === 'POST') {
    $input = $request->all();
    $id = (int)($input['id'] ?? 0);
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') $name = 'Página HTML ' . date('d/m H:i');
    $rawSlug = trim((string)($input['slug'] ?? ''));
    if ($rawSlug === '') $rawSlug = $name;
    $slug = funnelSlug($rawSlug);
    if ($slug === '') $slug = 'pagina-' . substr(md5(uniqid('', true)), 0, 6);

    $html = (string)($input['html_content'] ?? '');
    $status = ($input['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    // Funis HTML NUNCA sobrescrevem a raiz da raspadinha (is_home sempre 0)
    $isHome = 0;
    $domainId = !empty($input['custom_domain_id']) ? (int)$input['custom_domain_id'] : null;

    if (trim($html) === '') JsonResponse::error('Cole o HTML completo do funil.', 422);
    if (strlen($html) > 2000000) JsonResponse::error('O HTML ultrapassa o limite de 2 MB.', 413);

    $domainName = null;
    if ($domainId !== null) {
        $domainStmt = $pdo->prepare("SELECT id, domain FROM custom_domains WHERE id = ? AND workspace_id = ?");
        $domainStmt->execute([$domainId, $targetWsId]);
        $domainRow = $domainStmt->fetch();
        if (!$domainRow) JsonResponse::error('Domínio inválido para este workspace.', 422);
        $domainName = $domainRow['domain'];
    } else {
        // Busca o domínio ativo principal do workspace se houver
        $mainDomainStmt = $pdo->prepare("SELECT domain FROM custom_domains WHERE workspace_id = ? AND status = 'active' ORDER BY is_primary DESC, id ASC LIMIT 1");
        $mainDomainStmt->execute([$targetWsId]);
        $domainName = $mainDomainStmt->fetchColumn() ?: null;
    }

    // Se houver conflito de slug e for um novo funil, gera um slug único automaticamente
    $slugStmt = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? AND id <> ? LIMIT 1");
    $slugStmt->execute([$targetWsId, $slug, $id]);
    $slugConflictId = (int)($slugStmt->fetchColumn() ?: 0);
    if ($slugConflictId > 0) {
        if ($id === 0) {
            // Auto-incrementa o slug
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
                'Este slug já pertence a outro funil deste cliente. Escolha outro slug.',
                409,
                ['code' => 'funnel_slug_in_use', 'funnel_id' => $slugConflictId]
            );
        }
    }

    try {
        if ($id > 0) {
            $existingStmt = $pdo->prepare("SELECT id FROM funnels WHERE id = ? AND workspace_id = ?");
            $existingStmt->execute([$id, $targetWsId]);
            if (!$existingStmt->fetch()) JsonResponse::error('Funil não encontrado.', 404);

            $stmt = $pdo->prepare("UPDATE funnels SET custom_domain_id = ?, name = ?, slug = ?, html_content = ?, status = ?, is_home = 0, published_at = CASE WHEN ? = 'published' THEN COALESCE(published_at, CURRENT_TIMESTAMP) ELSE published_at END, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND workspace_id = ?");
            $stmt->execute([$domainId, $name, $slug, $html, $status, $status, $id, $targetWsId]);
            $funnelId = $id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO funnels (workspace_id, custom_domain_id, name, slug, html_content, status, is_home, published_at) VALUES (?, ?, ?, ?, ?, ?, 0, CASE WHEN ? = 'published' THEN CURRENT_TIMESTAMP ELSE NULL END)");
            $stmt->execute([$targetWsId, $domainId, $name, $slug, $html, $status, $status]);
            $funnelId = (int)$pdo->lastInsertId();
        }

        // Garante que a raiz do domínio continue 100% livre para a Raspadinha nativa
        $pdo->prepare("UPDATE funnels SET is_home = 0 WHERE workspace_id = ?")->execute([$targetWsId]);
    } catch (PDOException $e) {
        JsonResponse::error('Não foi possível salvar o funil agora.', 500, ['code' => 'funnel_save_failed']);
    }

    $urls = buildFunnelUrls($slug, $domainName);

    JsonResponse::success([
        'message' => $status === 'published' ? 'Funil HTML publicado com sucesso! Link exclusivo gerado.' : 'Rascunho do funil salvo com sucesso.',
        'funnel_id' => $funnelId,
        'status_value' => $status,
        'name' => $name,
        'slug' => $slug,
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
    JsonResponse::success(['message' => 'Funil removido.']);
}

JsonResponse::error('Method not allowed', 405);
