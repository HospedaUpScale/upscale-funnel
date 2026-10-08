<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Database\Connection;
use PDO;

class DatabaseIntegrityTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Connection::make();
    }

    public function testRequiredTablesExist(): void
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $expectedTables = [
            'workspaces',
            'users',
            'workspace_memberships',
            'partner_split_rules',
            'campaigns',
            'upsell_offers',
            'customers',
            'orders',
            'transactions',
            'conversion_events',
            'funnels',
            'admin_sessions',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(in_array($table, $tables, true), "Database must contain table '{$table}'.");
        }
    }

    public function testDefaultWorkspaceExists(): void
    {
        $stmt = $this->pdo->prepare("SELECT id, slug, status FROM workspaces WHERE slug = ?");
        $stmt->execute(['sincero']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue($row !== false, "Default workspace 'sincero' must exist in database.");
        $this->assertEquals('active', $row['status'] ?? null);
    }
}
