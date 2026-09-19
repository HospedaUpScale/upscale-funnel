<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Auth\AuthSessionRepository;
use App\Http\JsonResponse;
use App\Repositories\UserRepository;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$input = $request->all();
$type = strtolower(trim((string) ($input['type'] ?? '')));
$username = trim((string) ($input['username'] ?? $input['email'] ?? ''));
$password = trim((string) ($input['password'] ?? ''));
$directToken = trim((string) ($input['token'] ?? ''));

$superAdminToken = (string) ($config['super_admin_token'] ?? '');

function login_fail(string $message): void
{
    usleep(400000);
    JsonResponse::error($message, 401);
}

// 1. Login direto por token administrativo (avançado)
if ($directToken !== '') {
    $adminAuth = new AdminAuth($wsRepo, $superAdminToken);
    $auth = $adminAuth->authenticate($directToken);
    if (!$auth) {
        login_fail('Token administrativo inválido.');
    }

    JsonResponse::send([
        'status' => 'success',
        'token'  => $directToken,
        'role'   => $auth['role'],
        'login_type' => $auth['role'] === 'super_admin' ? 'master' : 'client',
        'user'   => [
            'name'  => $auth['role'] === 'super_admin' ? 'upScale Master' : ($auth['workspace']->name ?? 'Administrador'),
            'email' => '',
            'role'  => $auth['role'],
        ],
    ]);
}

if ($username === '' || $password === '') {
    JsonResponse::error('Informe usuário e senha.', 400);
}

// 2. Login MASTER (administração global da upScale)
if ($type === '' || $type === 'master') {
    $masterUser  = strtolower((string) (getenv('ADMIN_USER') ?: 'admin'));
    $masterEmail = strtolower((string) (getenv('ADMIN_EMAIL') ?: 'admin@upscalefunnel.site'));
    $masterPass  = (string) (getenv('ADMIN_PASSWORD') ?: 'admin123');

    $userOk = in_array(strtolower($username), [$masterUser, $masterEmail], true);
    $passOk = hash_equals($masterPass, $password);

    if ($userOk && $passOk) {
        if ($superAdminToken === '') {
            JsonResponse::error('SUPER_ADMIN_TOKEN não está configurado no servidor (.env / variáveis de ambiente).', 500);
        }

        JsonResponse::send([
            'status' => 'success',
            'token'  => $superAdminToken,
            'role'   => 'super_admin',
            'login_type' => 'master',
            'user'   => [
                'name'  => 'Administrador upScale',
                'email' => $masterEmail,
                'role'  => 'super_admin',
            ],
        ]);
    }

    if ($type === 'master') {
        login_fail('Usuário ou senha Master incorretos.');
    }
}

// 3. Login de CLIENTE / PARCEIRO (e-mail + senha vinculados a um workspace)
$users = new UserRepository($pdo);
$user = $users->findByEmail($username);

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    login_fail($type === 'client'
        ? 'E-mail ou senha incorretos. Confira os dados enviados pela sua agência.'
        : 'Usuário ou senha incorretos. Verifique suas credenciais.');
}

$memberships = $users->activeMemberships((int) $user['id']);
if (!$memberships) {
    login_fail('Este usuário não possui acesso a nenhum workspace ativo. Fale com o administrador.');
}

$wanted = (int) ($input['workspace_id'] ?? 0);
$chosen = $memberships[0];
foreach ($memberships as $m) {
    if ($wanted > 0 && (int) $m['workspace_id'] === $wanted) {
        $chosen = $m;
        break;
    }
}

$role = $chosen['membership_role'] === 'partner_admin' ? 'partner_admin' : 'merchant_admin';
$session = (new AuthSessionRepository($pdo))->create((int) $user['id'], (int) $chosen['workspace_id'], $role);

JsonResponse::send([
    'status' => 'success',
    'token'  => $session['token'],
    'role'   => $role,
    'login_type' => 'client',
    'expires_at' => $session['expires_at'],
    'user'   => [
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $role,
    ],
    'workspace' => [
        'id'   => (int) $chosen['workspace_id'],
        'name' => $chosen['workspace_name'],
        'slug' => $chosen['workspace_slug'],
    ],
    'workspaces' => array_map(fn($m) => [
        'id'   => (int) $m['workspace_id'],
        'name' => $m['workspace_name'],
    ], $memberships),
]);
