<?php

use App\Database\Connection;
use App\Database\Migrator;

require_once __DIR__ . '/../src/autoload.php';
$config = require __DIR__ . '/../config/config.php';

echo "Connecting to database ({$config['db']['driver']})...\n";
$pdo = Connection::make();

$migrator = new Migrator($pdo, $config['db']['driver'], dirname(__DIR__) . '/migrations');
$applied = $migrator->run();

if (empty($applied)) {
    echo "No new migrations to apply.\n";
} else {
    echo "Applied " . count($applied) . " migrations:\n";
    foreach ($applied as $file) {
        echo " - {$file}\n";
    }
}

// Run seed
require_once dirname(__DIR__) . '/migrations/seed_legacy_workspace.php';

// Cria/atualiza o administrador global se as credenciais estiverem no ambiente (.env).
// Idempotente: roda a cada deploy sem duplicar. Mantém a senha fora do código.
if (getenv('ADMIN_EMAIL') && getenv('ADMIN_PASSWORD')) {
    echo "Configurando administrador global a partir do ambiente...\n";
    require dirname(__DIR__) . '/scripts/create_admin.php';
}

echo "Migration and seed finished!\n";
