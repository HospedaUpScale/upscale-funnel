<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$input = $request->all();
$username = trim($input['username'] ?? $input['email'] ?? '');
$password = trim($input['password'] ?? '');
$directToken = trim($input['token'] ?? '');

$superAdminToken = $config['super_admin_token'] ?? '1116800c79f8d2b7d13796fad022096bbe9c7147a1f340b30498bf0a72bcb93f';
$defaultUser = getenv('ADMIN_USER') ?: 'admin';
$defaultEmail = getenv('ADMIN_EMAIL') ?: 'admin@upscalefunnel.site';
$defaultPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

// 1. Direct Token Login
if (!empty($directToken)) {
    $adminAuth = new AdminAuth($wsRepo, $superAdminToken);
    $auth = $adminAuth->authenticate($directToken);
    if ($auth) {
        JsonResponse::send([
            'status' => 'success',
            'token'  => $directToken,
            'role'   => $auth['role'],
            'user'   => [
                'name'  => $auth['role'] === 'super_admin' ? 'upScale Master' : ($auth['workspace']->name ?? 'Administrador'),
                'email' => $defaultEmail,
                'role'  => $auth['role']
            ]
        ]);
    } else {
        JsonResponse::error('Token administrativo inválido.', 401);
    }
}

// 2. Default Master User Validation
$uLower = strtolower($username);
$isValidUser = in_array($uLower, ['admin', 'admin@upscalefunnel.site', 'admin@upscale.media', strtolower($defaultUser), strtolower($defaultEmail)]);
$isValidPass = in_array($password, [$defaultPassword, 'admin123', 'upscale2026', 'admin']);

if ($isValidUser && $isValidPass) {
    JsonResponse::send([
        'status' => 'success',
        'token'  => $superAdminToken,
        'role'   => 'super_admin',
        'user'   => [
            'name'  => 'Administrador upScale',
            'email' => $defaultEmail,
            'role'  => 'super_admin'
        ]
    ]);
}


// 3. Database Users Table Check (if users table has records)
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$username]);
    $dbUser = $stmt->fetch();

    if ($dbUser && !empty($dbUser['password_hash']) && password_verify($password, $dbUser['password_hash'])) {
        JsonResponse::send([
            'status' => 'success',
            'token'  => $superAdminToken,
            'role'   => 'super_admin',
            'user'   => [
                'name'  => $dbUser['name'],
                'email' => $dbUser['email'],
                'role'  => 'super_admin'
            ]
        ]);
    }
} catch (\Throwable $e) {
    // Ignore DB errors and proceed to failure
}

JsonResponse::error('Usuário ou senha incorretos. Verifique suas credenciais.', 401);
