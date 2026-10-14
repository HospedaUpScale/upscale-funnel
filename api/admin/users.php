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

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Não autenticado. Entre novamente.', 401);
}
if ($auth && $auth['role'] === 'merchant_admin') {
    JsonResponse::error('Clientes não podem gerenciar acessos.', 403);
}

$users = new UserRepository($pdo);
$input = $request->all();
$isMaster = (!$auth || $auth['role'] === 'super_admin');
$targetWsId = (int) ($input['workspace_id'] ?? ($request->get('workspace_id') ?? 0));
$action = $input['action'] ?? ($request->get('action') ?? 'list');

// 1. GET: Listar usuários
if ($request->method() === 'GET') {
    // Se for Master e não especificou workspace_id OU pediu expressamente todos os usuários
    if (($targetWsId <= 0 || $action === 'list_all') && $isMaster) {
        JsonResponse::send([
            'status' => 'success',
            'is_master' => true,
            'total' => count($all = $users->listAll()),
            'users' => $all,
        ]);
    }

    if ($targetWsId <= 0) {
        JsonResponse::error('Informe o workspace do acesso.', 400);
    }

    $target = $wsRepo->findById($targetWsId);
    if (!$target) {
        JsonResponse::error('Workspace não encontrado.', 404);
    }

    if ($auth && $auth['role'] === 'partner_admin') {
        $allowed = [$auth['workspace']->id];
        foreach ($wsRepo->findDescendants($auth['workspace']->id) as $d) {
            $allowed[] = $d->id;
        }
        if (!in_array($targetWsId, $allowed, true)) {
            JsonResponse::error('Acesso negado para este workspace.', 403);
        }
    }

    JsonResponse::send([
        'status' => 'success',
        'workspace' => ['id' => $target->id, 'name' => $target->name, 'slug' => $target->slug],
        'users' => $users->listForWorkspace($targetWsId),
    ]);
}

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

// 2. Ações Globais de Usuários (Master)
if ($action === 'create_user' || $action === 'create_global') {
    if (!$isMaster) {
        JsonResponse::error('Apenas o usuário Master pode criar usuários globais.', 403);
    }

    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $phone = isset($input['phone']) ? trim((string) $input['phone']) : null;
    $memberships = is_array($input['memberships'] ?? null) ? $input['memberships'] : [];

    if ($name === '') {
        JsonResponse::error('O nome do usuário é obrigatório.', 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        JsonResponse::error('Informe um e-mail válido.', 400);
    }
    if ($password !== '' && strlen($password) < 8) {
        JsonResponse::error('A senha deve ter pelo menos 8 caracteres.', 400);
    }

    try {
        $created = $users->createGlobalUser($name, $email, $password ?: null, $phone, $memberships);
        JsonResponse::send([
            'status' => 'success',
            'message' => 'Usuário cadastrado com sucesso no sistema.',
            'user' => $created,
        ], 201);
    } catch (\Throwable $e) {
        JsonResponse::error($e->getMessage(), 409);
    }
}

if ($action === 'update_user' || $action === 'update_global') {
    if (!$isMaster) {
        JsonResponse::error('Apenas o usuário Master pode editar usuários globais.', 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId <= 0) {
        JsonResponse::error('ID de usuário inválido.', 400);
    }

    $updateData = [];
    if (isset($input['name'])) $updateData['name'] = trim((string) $input['name']);
    if (isset($input['phone'])) $updateData['phone'] = trim((string) $input['phone']);
    if (!empty($input['password'])) {
        if (strlen((string) $input['password']) < 8) {
            JsonResponse::error('A senha deve ter pelo menos 8 caracteres.', 400);
        }
        $updateData['password'] = (string) $input['password'];
    }
    if (isset($input['memberships']) && is_array($input['memberships'])) {
        $updateData['memberships'] = $input['memberships'];
    }

    $users->updateGlobalUser($userId, $updateData);
    (new AuthSessionRepository($pdo))->revokeForUser($userId);

    JsonResponse::send([
        'status' => 'success',
        'message' => 'Usuário atualizado com sucesso.',
    ]);
}

if ($action === 'delete_user' || $action === 'delete_global') {
    if (!$isMaster) {
        JsonResponse::error('Apenas o usuário Master pode remover usuários do sistema.', 403);
    }

    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId <= 0) {
        JsonResponse::error('ID de usuário inválido.', 400);
    }

    (new AuthSessionRepository($pdo))->revokeForUser($userId);
    $users->deleteGlobalUser($userId);

    JsonResponse::send([
        'status' => 'success',
        'message' => 'Usuário removido do sistema.',
    ]);
}

// 3. Ações no escopo de um Workspace específico
if ($targetWsId <= 0) {
    JsonResponse::error('Informe o workspace do acesso.', 400);
}

$target = $wsRepo->findById($targetWsId);
if (!$target) {
    JsonResponse::error('Workspace não encontrado.', 404);
}

if ($auth && $auth['role'] === 'partner_admin') {
    $allowed = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $d) {
        $allowed[] = $d->id;
    }
    if (!in_array($targetWsId, $allowed, true)) {
        JsonResponse::error('Acesso negado para este workspace.', 403);
    }
}

if ($action === 'revoke' || $action === 'delete') {
    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId <= 0) {
        JsonResponse::error('Informe o usuário a remover.', 400);
    }
    $users->revoke($targetWsId, $userId);
    (new AuthSessionRepository($pdo))->revokeForUserWorkspace($userId, $targetWsId);
    JsonResponse::send(['status' => 'success', 'message' => 'Acesso removido do workspace.']);
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

if ($auth && $auth['role'] === 'partner_admin') {
    $existing = $users->findByEmail($email);
    if ($existing && array_diff($users->membershipWorkspaceIds((int) $existing['id']), $allowed)) {
        JsonResponse::error('Este e-mail já está em uso em outro workspace. Use outro e-mail.', 409);
    }
}

$role = $target->type === 'partner_whitelabel' ? 'partner_admin' : 'merchant_admin';
$result = $users->upsertForWorkspace($targetWsId, $role, $email, $password, $name ?: $target->name);

if (!$result['created']) {
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
