<?php

namespace App\Repositories;

use PDO;

class UserRepository
{
    public const CLIENT_ROLES = ['partner_admin', 'merchant_admin', 'merchant_operator'];

    public function __construct(private PDO $pdo) {}

    /**
     * Regra fixa de papéis:
     * - vínculo no workspace administrativo (tipo super_admin) é sempre "super_admin";
     * - "super_admin" nunca fica em workspace de cliente: o pedido de Super admin vira
     *   vínculo no workspace administrativo, e quem é de cliente fica só no próprio workspace.
     */
    public function roleForWorkspace(int $workspaceId, string $requestedRole): ?string
    {
        $type = $this->workspaceType($workspaceId);
        if ($type === null) return null;
        if ($type === 'super_admin') return 'super_admin';
        if ($requestedRole === 'super_admin' || !in_array($requestedRole, self::CLIENT_ROLES, true)) {
            return $type === 'partner_whitelabel' ? 'partner_admin' : 'merchant_admin';
        }
        if ($requestedRole === 'partner_admin' && $type !== 'partner_whitelabel') return 'merchant_admin';
        return $requestedRole;
    }

    /** Aplica a regra a uma lista de vínculos [{workspace_id, role}] e remove repetidos. */
    public function normalizeMemberships(array $memberships): array
    {
        $out = [];
        foreach ($memberships as $m) {
            $wsId = (int) ($m['workspace_id'] ?? 0);
            $role = (string) ($m['role'] ?? 'merchant_admin');
            if ($wsId <= 0) continue;
            if ($role === 'super_admin' && $this->workspaceType($wsId) !== 'super_admin') {
                // Pedido de Super admin feito com outro workspace selecionado: vai para o administrativo.
                $adminWs = $this->adminWorkspaceId();
                if ($adminWs) $out[$adminWs] = 'super_admin';
                continue;
            }
            $final = $this->roleForWorkspace($wsId, $role);
            if ($final !== null && !isset($out[$wsId])) $out[$wsId] = $final;
        }
        $list = [];
        foreach ($out as $wsId => $role) $list[] = ['workspace_id' => $wsId, 'role' => $role];
        return $list;
    }

    public function adminWorkspaceId(): ?int
    {
        $stmt = $this->pdo->query("SELECT id FROM workspaces WHERE type = 'super_admin' ORDER BY CASE WHEN slug = 'admin-upscale' THEN 0 ELSE 1 END, id ASC LIMIT 1");
        $id = $stmt ? (int) $stmt->fetchColumn() : 0;
        return $id ?: null;
    }

    private array $typeCache = [];

    private function workspaceType(int $workspaceId): ?string
    {
        if (!array_key_exists($workspaceId, $this->typeCache)) {
            $stmt = $this->pdo->prepare('SELECT type FROM workspaces WHERE id = ?');
            $stmt->execute([$workspaceId]);
            $type = $stmt->fetchColumn();
            $this->typeCache[$workspaceId] = $type === false ? null : (string) $type;
        }
        return $this->typeCache[$workspaceId];
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function activeMemberships(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.role AS membership_role, w.id AS workspace_id, w.name AS workspace_name, w.slug AS workspace_slug, w.type AS workspace_type
            FROM workspace_memberships m
            INNER JOIN workspaces w ON w.id = m.workspace_id
            WHERE m.user_id = ? AND w.status = 'active'
            ORDER BY w.id ASC
        ");
        $stmt->execute([$userId]);

        return array_values(array_filter(
            $stmt->fetchAll(),
            fn($r) => in_array($r['membership_role'], array_merge(self::CLIENT_ROLES, ['super_admin']), true)
        ));
    }

    public function membershipWorkspaceIds(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT workspace_id FROM workspace_memberships WHERE user_id = ?');
        $stmt->execute([$userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function listForWorkspace(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.name, u.email, m.role
            FROM workspace_memberships m
            INNER JOIN users u ON u.id = m.user_id
            WHERE m.workspace_id = ?
            ORDER BY u.email ASC
        ");
        $stmt->execute([$workspaceId]);

        return array_map(fn($r) => [
            'id'    => (int) $r['id'],
            'name'  => $r['name'],
            'email' => $r['email'],
            'role'  => $r['role'],
        ], $stmt->fetchAll());
    }

    public function upsertForWorkspace(int $workspaceId, string $role, string $email, ?string $password, ?string $name): array
    {
        $role = $this->roleForWorkspace($workspaceId, $role) ?? 'merchant_admin';
        $email = strtolower(trim($email));
        $generated = false;
        if ($password === null || $password === '') {
            $password = self::generatePassword();
            $generated = true;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $user = $this->findByEmail($email);
        if ($user) {
            $userId = (int) $user['id'];
            $newName = ($name !== null && trim($name) !== '') ? trim($name) : $user['name'];
            $this->pdo->prepare('UPDATE users SET password_hash = ?, name = ? WHERE id = ?')->execute([$hash, $newName, $userId]);
            $created = false;
        } else {
            $this->pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
                ->execute([($name !== null && trim($name) !== '') ? trim($name) : $email, $email, $hash]);
            $userId = (int) $this->pdo->lastInsertId();
            $created = true;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM workspace_memberships WHERE workspace_id = ? AND user_id = ?');
        $stmt->execute([$workspaceId, $userId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare('UPDATE workspace_memberships SET role = ? WHERE workspace_id = ? AND user_id = ?')
                ->execute([$role, $workspaceId, $userId]);
        } else {
            $this->pdo->prepare('INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, ?)')
                ->execute([$workspaceId, $userId, $role]);
        }

        return [
            'user_id'   => $userId,
            'email'     => $email,
            'password'  => $generated ? $password : null,
            'generated' => $generated,
            'created'   => $created,
        ];
    }

    public function revoke(int $workspaceId, int $userId): void
    {
        $this->pdo->prepare('DELETE FROM workspace_memberships WHERE workspace_id = ? AND user_id = ?')
            ->execute([$workspaceId, $userId]);
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT u.id, u.name, u.email, u.phone, u.created_at,
                   CASE WHEN u.password_hash IS NOT NULL AND u.password_hash != '' THEN 1 ELSE 0 END AS has_password
            FROM users u
            ORDER BY u.id DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $memStmt = $this->pdo->query("
            SELECT m.user_id, m.workspace_id, m.role, w.name AS workspace_name, w.slug AS workspace_slug, w.type AS workspace_type
            FROM workspace_memberships m
            INNER JOIN workspaces w ON w.id = m.workspace_id
            ORDER BY w.id ASC
        ");
        $membershipsByUser = [];
        foreach ($memStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $uid = (int) $m['user_id'];
            $membershipsByUser[$uid][] = [
                'workspace_id'   => (int) $m['workspace_id'],
                'workspace_name' => $m['workspace_name'],
                'workspace_slug' => $m['workspace_slug'],
                'workspace_type' => $m['workspace_type'],
                'role'           => $m['role'],
            ];
        }

        foreach ($users as &$u) {
            $u['id'] = (int) $u['id'];
            $u['has_password'] = (bool) $u['has_password'];
            $u['workspaces'] = $membershipsByUser[$u['id']] ?? [];
        }

        return $users;
    }

    public function createGlobalUser(string $name, string $email, ?string $password, ?string $phone = null, array $memberships = []): array
    {
        $email = strtolower(trim($email));
        $generated = false;
        if ($password === null || $password === '') {
            $password = self::generatePassword();
            $generated = true;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $existing = $this->findByEmail($email);
        if ($existing) {
            throw new \RuntimeException("O e-mail '{$email}' já está em uso por outro usuário.");
        }

        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $phone]);
        $userId = (int) $this->pdo->lastInsertId();

        foreach ($this->normalizeMemberships($memberships) as $m) {
            $this->pdo->prepare('INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, ?)')
                ->execute([$m['workspace_id'], $userId, $m['role']]);
        }

        return [
            'id'           => $userId,
            'name'         => $name,
            'email'        => $email,
            'phone'        => $phone,
            'has_password' => true,
            'password'     => $password,
            'generated'    => $generated,
        ];
    }

    public function findByEmailAndWorkspace(string $email, int $workspaceId): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT u.id, u.name, u.email, u.phone, m.role, m.workspace_id
            FROM users u
            INNER JOIN workspace_memberships m ON m.user_id = u.id
            WHERE u.id = ? AND m.workspace_id = ?
            LIMIT 1
        ');
        $stmt->execute([(int) $user['id'], $workspaceId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateGlobalUser(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $fields[] = 'name = ?';
            $params[] = trim((string) $data['name']);
        }
        if (isset($data['phone'])) {
            $fields[] = 'phone = ?';
            $params[] = trim((string) $data['phone']);
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        if (!empty($fields)) {
            $params[] = $userId;
            $this->pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')
                ->execute($params);
        }

        if (isset($data['memberships']) && is_array($data['memberships'])) {
            $this->pdo->prepare('DELETE FROM workspace_memberships WHERE user_id = ?')->execute([$userId]);
            foreach ($this->normalizeMemberships($data['memberships']) as $m) {
                $this->pdo->prepare('INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, ?)')
                    ->execute([$m['workspace_id'], $userId, $m['role']]);
            }
        }

        return true;
    }

    public function deleteGlobalUser(int $userId): bool
    {
        $this->pdo->prepare('DELETE FROM workspace_memberships WHERE user_id = ?')->execute([$userId]);
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        return $stmt->rowCount() > 0;
    }

    public static function generatePassword(int $length = 12): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
