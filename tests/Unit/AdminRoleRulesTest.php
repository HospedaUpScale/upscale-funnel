<?php

namespace Tests\Unit;

use App\Database\Migrator;
use App\Repositories\UserRepository;
use PDO;
use Tests\TestCase;

/** Regra fixa: admin entra pelo workspace administrativo; usuário de cliente nunca vira master. */
class AdminRoleRulesTest extends TestCase
{
    private function db(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        (new Migrator($pdo, 'sqlite', __DIR__ . '/../../migrations'))->run();
        $ins = $pdo->prepare("INSERT INTO workspaces (id, type, name, slug, api_token_hash, admin_token_hash) VALUES (?, ?, ?, ?, 'x', 'x')");
        $ins->execute([10, 'super_admin', 'Admin upScale', 'admin-upscale']);
        $ins->execute([20, 'merchant', 'Loja', 'loja']);
        $ins->execute([30, 'partner_whitelabel', 'Parceiro', 'parceiro']);
        return $pdo;
    }

    private function roles(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT workspace_id, role FROM workspace_memberships WHERE user_id = ? ORDER BY workspace_id');
        $stmt->execute([$userId]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) $out[(int)$r['workspace_id']] = $r['role'];
        return $out;
    }

    public function testAdminWorkspaceAlwaysGivesSuperAdmin(): void
    {
        $pdo = $this->db();
        $users = new UserRepository($pdo);
        $u = $users->createGlobalUser('Novo Admin', 'novo@x.com', 'Senha@2027', null, [['workspace_id' => 10, 'role' => 'merchant_admin']]);
        $this->assertEquals([10 => 'super_admin'], $this->roles($pdo, $u['id']));

        $r = $users->upsertForWorkspace(10, 'merchant_admin', 'outro@x.com', 'Senha@2027', 'Outro');
        $this->assertEquals([10 => 'super_admin'], $this->roles($pdo, $r['user_id']));
    }

    public function testClientUserNeverBecomesMaster(): void
    {
        $pdo = $this->db();
        $users = new UserRepository($pdo);
        // "Super admin" marcado com um workspace de cliente selecionado: vai para o administrativo.
        $u = $users->createGlobalUser('Admin', 'a@x.com', 'Senha@2027', null, [['workspace_id' => 20, 'role' => 'super_admin']]);
        $this->assertEquals([10 => 'super_admin'], $this->roles($pdo, $u['id']));

        // Cliente comum: papéis de cliente, sem master, e parceiro só em workspace de parceiro.
        $c = $users->createGlobalUser('Cliente', 'c@x.com', 'Senha@2027', null, [
            ['workspace_id' => 20, 'role' => 'partner_admin'],
            ['workspace_id' => 30, 'role' => 'merchant_operator'],
        ]);
        $this->assertEquals([20 => 'merchant_admin', 30 => 'merchant_operator'], $this->roles($pdo, $c['id']));

        $users->updateGlobalUser($c['id'], ['memberships' => [['workspace_id' => 20, 'role' => 'merchant_admin']]]);
        $this->assertEquals([20 => 'merchant_admin'], $this->roles($pdo, $c['id']));
    }

    public function testMigrationFixesExistingMemberships(): void
    {
        $pdo = $this->db();
        $pdo->exec("INSERT INTO users (id, name, email, password_hash) VALUES (1, 'A', 'a@x.com', 'h'), (2, 'B', 'b@x.com', 'h'), (3, 'C', 'c@x.com', 'h')");
        $pdo->exec("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES
            (10, 1, 'merchant_admin'),
            (20, 2, 'super_admin'),
            (20, 3, 'merchant_admin')");
        $pdo->exec(file_get_contents(__DIR__ . '/../../migrations/0028_admin_roles_fixed.sqlite.sql'));
        $this->assertEquals([10 => 'super_admin'], $this->roles($pdo, 1));
        $this->assertEquals([10 => 'super_admin'], $this->roles($pdo, 2));
        $this->assertEquals([20 => 'merchant_admin'], $this->roles($pdo, 3));
    }
}
