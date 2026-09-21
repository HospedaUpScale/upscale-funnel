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

if ($request->method() === 'GET') {
    $stmt = $pdo->prepare("SELECT f.*, d.domain FROM funnels f LEFT JOIN custom_domains d ON d.id = f.custom_domain_id WHERE f.workspace_id = ? ORDER BY f.id DESC");
    $stmt->execute([$targetWsId]);
    $funnels = $stmt->fetchAll();
    foreach ($funnels as &$funnel) {
        $funnel['id'] = (int)$funnel['id'];
        $funnel['workspace_id'] = (int)$funnel['workspace_id'];
        $funnel['custom_domain_id'] = $funnel['custom_domain_id'] !== null ? (int)$funnel['custom_domain_id'] : null;
        $funnel['is_home'] = (bool)$funnel['is_home'];
    }
    JsonResponse::success(['funnels' => $funnels]);
}

if ($request->method() === 'POST') {
    $input = $request->all();
    $id = (int)($input['id'] ?? 0);
    $name = trim((string)($input['name'] ?? ''));
    $slug = funnelSlug((string)($input['slug'] ?? $name));
    $html = (string)($input['html_content'] ?? '');
    $status = ($input['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $isHome = !empty($input['is_home']) ? 1 : 0;
    $domainId = !empty($input['custom_domain_id']) ? (int)$input['custom_domain_id'] : null;

    if ($name === '' || $slug === '') JsonResponse::error('Nome e slug são obrigatórios.', 422);
    if (trim($html) === '') JsonResponse::error('Cole o HTML completo do funil.', 422);
    if (strlen($html) > 2000000) JsonResponse::error('O HTML ultrapassa o limite de 2 MB.', 413);

    if ($domainId !== null) {
        $domainStmt = $pdo->prepare("SELECT id FROM custom_domains WHERE id = ? AND workspace_id = ?");
        $domainStmt->execute([$domainId, $targetWsId]);
        if (!$domainStmt->fetch()) JsonResponse::error('Domínio inválido para este workspace.', 422);
    }

    $slugStmt = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? AND id <> ? LIMIT 1");
    $slugStmt->execute([$targetWsId, $slug, $id]);
    $slugConflictId = (int)($slugStmt->fetchColumn() ?: 0);
    if ($slugConflictId > 0) {
        JsonResponse::error(
            'Este slug já pertence a outro funil deste cliente. Abra o funil existente ou informe outro slug.',
            409,
            ['code' => 'funnel_slug_in_use', 'funnel_id' => $slugConflictId]
        );
    }

    if ($isHome) {
        if ($domainId === null) {
            $pdo->prepare("UPDATE funnels SET is_home = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ? AND custom_domain_id IS NULL")->execute([$targetWsId]);
        } else {
            $pdo->prepare("UPDATE funnels SET is_home = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ? AND custom_domain_id = ?")->execute([$targetWsId, $domainId]);
        }
    }

    try {
        if ($id > 0) {
            $existingStmt = $pdo->prepare("SELECT id FROM funnels WHERE id = ? AND workspace_id = ?");
            $existingStmt->execute([$id, $targetWsId]);
            if (!$existingStmt->fetch()) JsonResponse::error('Funil não encontrado.', 404);

            $stmt = $pdo->prepare("UPDATE funnels SET custom_domain_id = ?, name = ?, slug = ?, html_content = ?, status = ?, is_home = ?, published_at = CASE WHEN ? = 'published' THEN COALESCE(published_at, CURRENT_TIMESTAMP) ELSE published_at END, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND workspace_id = ?");
            $stmt->execute([$domainId, $name, $slug, $html, $status, $isHome, $status, $id, $targetWsId]);
            $funnelId = $id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO funnels (workspace_id, custom_domain_id, name, slug, html_content, status, is_home, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'published' THEN CURRENT_TIMESTAMP ELSE NULL END)");
            $stmt->execute([$targetWsId, $domainId, $name, $slug, $html, $status, $isHome, $status]);
            $funnelId = (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        JsonResponse::error('Não foi possível salvar o funil agora.', 500, ['code' => 'funnel_save_failed']);
    }

    JsonResponse::success(['message' => 'Funil salvo com sucesso.', 'funnel_id' => $funnelId, 'status_value' => $status]);
}

if ($request->method() === 'DELETE') {
    $id = (int)($request->get('id') ?? 0);
    $stmt = $pdo->prepare("DELETE FROM funnels WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$id, $targetWsId]);
    if ($stmt->rowCount() === 0) JsonResponse::error('Funil não encontrado.', 404);
    JsonResponse::success(['message' => 'Funil removido.']);
}

JsonResponse::error('Method not allowed', 405);
