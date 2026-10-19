<?php
/**
 * Histórico de tudo que foi criado/alterado no painel. Só o dono da conta acessa
 * (login master do ambiente, ADMIN_EMAIL ou OWNER_EMAILS). Outros Super admins recebem 403.
 * GET ?q=&entity=&actor=&from=YYYY-MM-DD&to=YYYY-MM-DD&page=1
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Support\AuditLog;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

$isLocal = ($config['app_env'] ?? 'production') === 'local';
if (!$auth && !$isLocal) {
    JsonResponse::error('Não autenticado. Entre novamente.', 401);
}
// Qualquer pessoa logada que não seja o dono é recusada, inclusive outros Super admins.
if ($auth && !AuditLog::isOwner($auth, $config, $pdo)) {
    JsonResponse::error('O histórico é exclusivo do dono da conta.', 403);
}
if ($request->method() !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

$where = [];
$params = [];
$q = trim((string)($request->get('q') ?? ''));
if ($q !== '') {
    $where[] = '(summary LIKE ? OR entity_label LIKE ? OR actor_email LIKE ? OR workspace_name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$entity = trim((string)($request->get('entity') ?? ''));
if ($entity !== '') { $where[] = 'entity = ?'; $params[] = $entity; }
$actor = trim((string)($request->get('actor') ?? ''));
if ($actor !== '') {
    if ($actor === 'master') { $where[] = 'actor_user_id IS NULL'; }
    else { $where[] = 'actor_user_id = ?'; $params[] = (int)$actor; }
}
$from = (string)($request->get('from') ?? '');
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'updated_at >= ?'; $params[] = $from . ' 00:00:00'; }
$to = (string)($request->get('to') ?? '');
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where[] = 'updated_at <= ?'; $params[] = $to . ' 23:59:59'; }

$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$perPage = 50;
$page = max(1, (int)($request->get('page') ?? 1));

try {
    $st = $pdo->prepare('SELECT COUNT(*) FROM audit_log' . $sqlWhere);
    $st->execute($params);
    $total = (int)$st->fetchColumn();

    $st = $pdo->prepare('SELECT * FROM audit_log' . $sqlWhere . ' ORDER BY updated_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $actors = $pdo->query("SELECT actor_user_id AS id, MAX(actor_name) AS name, MAX(actor_email) AS email FROM audit_log GROUP BY actor_user_id ORDER BY MAX(actor_name)")->fetchAll(PDO::FETCH_ASSOC);
    $entities = $pdo->query('SELECT DISTINCT entity FROM audit_log ORDER BY entity')->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    JsonResponse::error('O histórico ainda não está disponível. Faça o deploy para criar a tabela.', 503);
}

JsonResponse::send([
    'status'   => 'success',
    'total'    => $total,
    'page'     => $page,
    'pages'    => max(1, (int)ceil($total / $perPage)),
    'items'    => array_map(fn($r) => [
        'id'            => (int)$r['id'],
        'created_at'    => $r['created_at'],
        'updated_at'    => $r['updated_at'],
        'repeat_count'  => (int)$r['repeat_count'],
        'actor'         => ['id' => $r['actor_user_id'] !== null ? (int)$r['actor_user_id'] : null, 'name' => $r['actor_name'], 'email' => $r['actor_email'], 'role' => $r['actor_role']],
        'workspace'     => $r['workspace_id'] ? ['id' => (int)$r['workspace_id'], 'name' => $r['workspace_name']] : null,
        'entity'        => $r['entity'],
        'action'        => $r['action'],
        'entity_id'     => $r['entity_id'],
        'entity_label'  => $r['entity_label'],
        'summary'       => $r['summary'],
        'details'       => $r['details'] ? json_decode($r['details'], true) : null,
        'ip'            => $r['ip'],
    ], $rows),
    'filters'  => [
        'actors'   => array_map(fn($a) => ['id' => $a['id'] !== null ? (int)$a['id'] : 'master', 'name' => $a['name'] ?: 'Master', 'email' => $a['email']], $actors),
        'entities' => $entities,
    ],
]);
