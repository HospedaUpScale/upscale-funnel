<?php

namespace Tests\Unit;

use App\Support\AuditLog;
use PDO;
use Tests\TestCase;

/** O histórico é só do dono: login master do ambiente, ADMIN_EMAIL ou OWNER_EMAILS. */
class AuditLogOwnerTest extends TestCase
{
    private function db(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)");
        $pdo->exec("INSERT INTO users (id, email) VALUES (1, 'dono@x.com'), (2, 'lucas@x.com'), (3, 'socio@x.com')");
        return $pdo;
    }

    public function testOnlyOwnerCanSeeHistory(): void
    {
        $pdo = $this->db();
        $config = ['admin_email' => 'Dono@x.com'];
        $this->assertTrue(AuditLog::isOwner(['role' => 'super_admin', 'user_id' => 0], $config, $pdo), 'login master do ambiente');
        $this->assertTrue(AuditLog::isOwner(['role' => 'super_admin', 'user_id' => 1], $config, $pdo), 'usuário com ADMIN_EMAIL');
        $this->assertFalse(AuditLog::isOwner(['role' => 'super_admin', 'user_id' => 2], $config, $pdo), 'outro Super admin');
        $this->assertFalse(AuditLog::isOwner(['role' => 'merchant_admin', 'user_id' => 1], $config, $pdo), 'papel de cliente');
        $this->assertFalse(AuditLog::isOwner(null, $config, $pdo), 'sem login');

        putenv('OWNER_EMAILS=socio@x.com');
        $this->assertTrue(AuditLog::isOwner(['role' => 'super_admin', 'user_id' => 3], $config, $pdo), 'OWNER_EMAILS');
        putenv('OWNER_EMAILS');
    }
}
