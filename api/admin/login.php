<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Auth\AuthSessionRepository;
use App\Http\JsonResponse;
use App\Repositories\UserRepository;
use App\Security\RateLimiter;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$ip = trim(explode(',', $ip)[0]);
$rateLimiter = new RateLimiter();
$rateKey = 'login_ip_' . $ip;

$input = $request->all();
$type = strtolower(trim((string) ($input['type'] ?? '')));
$username = trim((string) ($input['username'] ?? $input['email'] ?? ''));
$password = trim((string) ($input['password'] ?? ''));
$directToken = trim((string) ($input['token'] ?? ''));

$superAdminToken = (string) ($config['super_admin_token'] ?? '');

// Sem bloqueio de acesso: erros seguidos só deixam cada nova resposta de erro um pouco mais lenta
// (veja login_fail), o que atrapalha quem tenta adivinhar senhas sem trancar quem errou a senha.

function login_fail(string $message, ?RateLimiter $limiter = null, ?string $key = null): void
{
    $attempts = 0;
    if ($limiter && $key) {
        // Zera a contagem 15 minutos depois do primeiro erro (efeito de isTooManyAttempts).
        $limiter->isTooManyAttempts($key, 1, 900);
        $attempts = $limiter->hit($key);
    }
    // 0,4 s por erro; a partir do 6º erro seguido, +0,3 s por erro, até no máximo 2 s.
    $delayMs = 400 + max(0, $attempts - 5) * 300;
    usleep(min(2000, $delayMs) * 1000);
    JsonResponse::error($message, 401);
}

/**
 * Cria uma sessão master com validade (em vez de entregar o SUPER_ADMIN_TOKEN ao navegador)
 * e responde com a lista de workspaces.
 */
function login_master_success(PDO $pdo, int $userId, string $name, string $email): void
{
    $session = (new AuthSessionRepository($pdo))->create($userId, 0, 'super_admin', 12);

    $allWs = (new \App\Repositories\WorkspaceRepository($pdo))->all();
    $wsList = array_map(fn($w) => [
        'id'   => $w->id,
        'name' => $w->name,
        'slug' => $w->slug,
        'type' => $w->type,
    ], $allWs);

    JsonResponse::send([
        'status'     => 'success',
        'token'      => $session['token'],
        'expires_at' => $session['expires_at'],
        'role'       => 'super_admin',
        'login_type' => 'master',
        'user'       => [
            'name'  => $name,
            'email' => $email,
            'role'  => 'super_admin',
        ],
        // Workspace de pouso: prefere o "Admin upScale" (slug admin-upscale),
        // depois qualquer outro do tipo super_admin, por fim o primeiro da lista.
        'workspace' => (function () use ($wsList) {
            foreach ($wsList as $w) { if (($w['slug'] ?? '') === 'admin-upscale') return $w; }
            foreach ($wsList as $w) { if (($w['type'] ?? '') === 'super_admin') return $w; }
            return $wsList[0] ?? ['id' => 1, 'name' => 'upScale Hub Global', 'slug' => 'global'];
        })(),
        'workspaces' => $wsList,
    ]);
}

// 1. Login direto por token administrativo (avançado)
if ($directToken !== '') {
    $adminAuth = new AdminAuth($wsRepo, $superAdminToken);
    $auth = $adminAuth->authenticate($directToken);
    if (!$auth) {
        login_fail('Token administrativo inválido.', $rateLimiter, $rateKey);
    }

    $rateLimiter->clear($rateKey);

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
// Credenciais vêm somente das variáveis ADMIN_USER / ADMIN_EMAIL / ADMIN_PASSWORD.
// Sem ADMIN_PASSWORD configurado, o login master por senha fica desativado.
if ($type === '' || $type === 'master') {
    $masterPass = (string) ($config['admin_password'] ?? '');
    $masterIds = array_values(array_filter(array_map(
        fn($v) => strtolower(trim((string) $v)),
        [$config['admin_user'] ?? '', $config['admin_email'] ?? '']
    )));

    $isMaster = $masterPass !== ''
        && in_array(strtolower($username), $masterIds, true)
        && hash_equals($masterPass, $password);

    if ($isMaster) {
        $rateLimiter->clear($rateKey);
        login_master_success($pdo, 0, 'upScale Master', $username);
    }

    // Não para aqui: Super admins cadastrados pelo painel (e-mail + senha) também entram pela aba Master.
}

// 3. Login de CLIENTE / PARCEIRO (e-mail + senha vinculados a um workspace)
$users = new UserRepository($pdo);
$user = $users->findByEmail($username);

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    login_fail($type === 'client'
        ? 'E-mail ou senha incorretos. Confira os dados enviados pela sua agência.'
        : 'Usuário ou senha incorretos. Verifique suas credenciais.', $rateLimiter, $rateKey);
}

$memberships = $users->activeMemberships((int) $user['id']);
if (!$memberships) {
    login_fail('Este usuário não possui acesso a nenhum workspace ativo. Fale com o administrador.', $rateLimiter, $rateKey);
}

// Usuário com perfil super_admin recebe uma sessão master.
// Regra: o acesso master vem do workspace administrativo; vínculo de cliente nunca abre os outros painéis.
// Exceção de segurança: se o sistema ainda não tem workspace administrativo (banco antigo),
// o papel super_admin continua valendo para ninguém perder o acesso.
$hasAdminWorkspace = (bool) $users->adminWorkspaceId();
$hasSuperAdmin = false;
foreach ($memberships as $m) {
    if ($m['membership_role'] === 'super_admin' && (($m['workspace_type'] ?? '') === 'super_admin' || !$hasAdminWorkspace)) {
        $hasSuperAdmin = true;
        break;
    }
}

if ($hasSuperAdmin) {
    $rateLimiter->clear($rateKey);
    login_master_success($pdo, (int) $user['id'], $user['name'] ?: 'upScale Master', $user['email']);
}

// Daqui em diante só vínculos de cliente (o administrativo já saiu acima como master).
$memberships = array_values(array_filter($memberships, fn($m) => ($m['workspace_type'] ?? '') !== 'super_admin' && $m['membership_role'] !== 'super_admin'));
if (!$memberships) {
    login_fail('Este usuário não possui acesso a nenhum workspace ativo. Fale com o administrador.', $rateLimiter, $rateKey);
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

$rateLimiter->clear($rateKey);


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
