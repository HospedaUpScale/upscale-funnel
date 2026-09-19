<?php

// Gestão dos acessos (e-mail + senha) de clientes e parceiros por workspace.
// Master: qualquer workspace. Parceiro: o próprio e suas subcontas. Cliente: sem acesso.

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Auth\AuthSessionRepository;
use App\Http\JsonResponse;
use App\Repositories\UserRepository;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth) {
    JsonResponse::error('Não autenticado. Entre novamente.', 401);
}
if ($auth['role'] === 'merchant_admin') {
    JsonResponse::error('Clientes não podem gerenciar acessos.', 403);
}

$input = $request->all();
$targetWsId = (int) ($input['workspace_id'] ?? 0);
if ($targetWsId <= 0) {
    JsonResponse::error('Informe o workspace do acesso.', 400);
}

$target = $wsRepo->findById($targetWsId);
if (!$target) {
    JsonResponse::error('Workspace não encontrado.', 404);
}

if ($auth['role'] === 'partner_admin') {
    $allowed = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $d) {
        $allowed[] = $d->id;
    }
    if (!in_array($targetWsId, $allowed, true)) {
        JsonResponse::error('Acesso negado para este workspace.', 403);
    }
}

$users = new UserRepository($pdo);

if ($request->method() === 'GET') {
    JsonResponse::send([
        'status' => 'success',
        'workspace' => ['id' => $target->id, 'name' => $target->name, 'slug' => $target->slug],
        'users' => $users->listForWorkspace($targetWsId),
    ]);
}

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

$action = $input['action'] ?? 'save';

if ($action === 'revoke') {
    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId <= 0) {
        JsonResponse::error('Informe o usuário a remover.', 400);
    }
    $users->revoke($targetWsId, $userId);
    (new AuthSessionRepository($pdo))->revokeForUserWorkspace($userId, $targetWsId);
    JsonResponse::send(['status' => 'success', 'message' => 'Acesso removido.']);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = isset($input['password']) ? (string) $input['password'] : '';
$name = isset($input['name']) ? trim((string) $input['name']) : null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    JsonResponse::error('Informe um e-mail válido para o acesso.', 400);
}
if ($password !== '' && strlen($password) < 8) {
    JsonResponse::error('A senha precisa ter pelo menos 8 caracteres.', 400);
}

if ($auth['role'] === 'partner_admin') {
    $existing = $users->findByEmail($email);
    if ($existing && array_diff($users->membershipWorkspaceIds((int) $existing['id']), $allowed)) {
        JsonResponse::error('Este e-mail já está em uso em outro workspace. Use outro e-mail.', 409);
    }
}

$role = $target->type === 'partner_whitelabel' ? 'partner_admin' : 'merchant_admin';
$result =$users->upsertForWorkspace($targetWsId, $role, $email, $password, $name ?: $target->name);

if (!$result['created']) {
    // Senha redefinida: derruba sessões abertas desse usuário nesse workspace.
    (new AuthSessionRepository($pdo))->revokeForUserWorkspace($result['user_id'], $targetWsId);
}

JsonResponse::send([
    'status' => 'success',
    'message' => $result['created'] ? 'Acesso criado com sucesso.' : 'Senha do acesso redefinida.',
    'access' => [
        'email' => $result['email'],
        'password' => $result['password'],
        'created' => $result['created'],
        'workspace' => ['id' => $target->id, 'name' => $target->name],
    ],
]);
