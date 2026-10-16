<?php
/**
 * Cria (ou atualiza) um usuário administrador global (super_admin) que enxerga e
 * gerencia TODOS os workspaces.
 *
 * Idempotente: pode rodar a cada deploy sem duplicar nada. As credenciais vêm do
 * ambiente (nunca ficam no código, pois o repositório é público):
 *
 *   ADMIN_EMAIL=seu@email.com ADMIN_PASSWORD='SuaSenha' php scripts/create_admin.php
 *
 * Também aceita, nesta ordem de prioridade: argumentos de linha de comando
 *   php scripts/create_admin.php seu@email.com 'SuaSenha' 'Seu Nome'
 * e, por fim, as variáveis já existentes do .env.
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Database\Connection;

$config = require __DIR__ . '/../config/config.php';

$email = strtolower(trim((string)($argv[1] ?? getenv('ADMIN_EMAIL') ?: '')));
$password = (string)($argv[2] ?? getenv('ADMIN_PASSWORD') ?: '');
$name = trim((string)($argv[3] ?? getenv('ADMIN_NAME') ?: 'Administrador'));

if ($email === '' || $password === '') {
    fwrite(STDERR, "Defina ADMIN_EMAIL e ADMIN_PASSWORD (variáveis de ambiente ou argumentos).\n");
    fwrite(STDERR, "Ex.: ADMIN_EMAIL=seu@email.com ADMIN_PASSWORD='SuaSenha' php scripts/create_admin.php\n");
    exit(1);
}

$pdo = Connection::make();
$hash = password_hash($password, PASSWORD_DEFAULT);

// 1. Garante um workspace para ancorar a associação super_admin.
//    (No modelo atual, basta UMA associação super_admin para o usuário ver todos os workspaces.)
$wsId = (int) ($pdo->query("SELECT id FROM workspaces ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
if ($wsId === 0) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $pdo->prepare("
        INSERT INTO workspaces (parent_id, type, name, slug, api_token_hash, admin_token_hash, gateway_provider, status)
        VALUES (NULL, 'super_admin', 'Administração Global', 'global', ?, ?, 'mock', 'active')
    ")->execute([hash('sha256', bin2hex(random_bytes(16))), hash('sha256', bin2hex(random_bytes(16)))]);
    $wsId = (int) $pdo->lastInsertId();
    echo "Workspace de administração criado (ID {$wsId}).\n";
}

// 2. Cria ou atualiza o usuário (idempotente pelo e-mail).
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$userId = (int) ($stmt->fetchColumn() ?: 0);

if ($userId > 0) {
    $pdo->prepare("UPDATE users SET password_hash = ?, name = ? WHERE id = ?")->execute([$hash, $name, $userId]);
    echo "Usuário administrador atualizado (ID {$userId}, {$email}).\n";
} else {
    $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")->execute([$name, $email, $hash]);
    $userId = (int) $pdo->lastInsertId();
    echo "Usuário administrador criado (ID {$userId}, {$email}).\n";
}

// 3. Garante a associação super_admin (sem duplicar).
$stmt = $pdo->prepare("SELECT id FROM workspace_memberships WHERE workspace_id = ? AND user_id = ?");
$stmt->execute([$wsId, $userId]);
if ($stmt->fetchColumn()) {
    $pdo->prepare("UPDATE workspace_memberships SET role = 'super_admin' WHERE workspace_id = ? AND user_id = ?")
        ->execute([$wsId, $userId]);
} else {
    $pdo->prepare("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, 'super_admin')")
        ->execute([$wsId, $userId]);
}

echo "Pronto. Acesse o painel com {$email} e a senha definida. Ele gerencia todos os workspaces.\n";
