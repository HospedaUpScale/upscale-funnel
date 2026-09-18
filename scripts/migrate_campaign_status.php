<?php

require_once __DIR__ . '/../src/autoload.php';
use App\Database\Connection;

$pdo = Connection::make();

echo "Migrating campaigns table schema to support 'published', 'ended', 'draft'...\n";

$pdo->beginTransaction();
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campaigns_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workspace_id INTEGER NOT NULL,
            external_platform TEXT NOT NULL DEFAULT 'sorteamos',
            external_campaign_id TEXT NULL,
            title TEXT NOT NULL,
            ticket_price NUMERIC(10,2) NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive','published','ended','draft')),
            published_at TEXT NULL,
            ended_at TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (workspace_id, external_platform, external_campaign_id),
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        INSERT INTO campaigns_new (id, workspace_id, external_platform, external_campaign_id, title, ticket_price, status, created_at, updated_at)
        SELECT id, workspace_id, external_platform, external_campaign_id, title, ticket_price, status, created_at, updated_at FROM campaigns;
    ");

    $pdo->exec("DROP TABLE campaigns;");
    $pdo->exec("ALTER TABLE campaigns_new RENAME TO campaigns;");

    $pdo->commit();
    echo "Migration successful!\n";
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
