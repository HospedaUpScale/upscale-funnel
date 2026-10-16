<?php
/**
 * Script de execução das migrations.
 *
 * IMPORTANTE: este arquivo é o SCRIPT (que conecta ao banco e dispara o migrator).
 * A CLASSE Migrator fica em src/Database/Migrator.php. Em algum momento este
 * arquivo foi sobrescrito com o código da classe por engano, o que fazia o
 * `php scripts/migrate.php` sair com código 0 sem criar nenhuma tabela.
 */

use App\Database\Connection;
use App\Database\Migrator;

require_once __DIR__ . '/../src/autoload.php';
$config = require __DIR__ . '/../config/config.php';

echo "Connecting to database ({$config['db']['driver']})...\n";

try {
    $pdo = Connection::make();
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRO ao conectar no banco: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Verifique DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD no Ambiente.\n");
    exit(1);
}

$migrator = new Migrator($pdo, $config['db']['driver'], dirname(__DIR__) . '/migrations');

try {
    $applied = $migrator->run();
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRO ao aplicar migrations: " . $e->getMessage() . "\n");
    exit(1);
}

if (empty($applied)) {
    echo "No new migrations to apply.\n";
} else {
    echo "Applied " . count($applied) . " migrations:\n";
    foreach ($applied as $file) {
        echo " - {$file}\n";
    }
}

// Seed inicial (idempotente: pula se já tiver workspace cadastrado)
require_once dirname(__DIR__) . '/migrations/seed_legacy_workspace.php';

// Cria/atualiza o administrador global se as credenciais estiverem no ambiente (.env).
// Idempotente: roda a cada deploy sem duplicar. Mantém a senha fora do código.
if (getenv('ADMIN_EMAIL') && getenv('ADMIN_PASSWORD')) {
    echo "Configurando administrador global a partir do ambiente...\n";
    require dirname(__DIR__) . '/scripts/create_admin.php';
}

echo "Migration and seed finished!\n";
