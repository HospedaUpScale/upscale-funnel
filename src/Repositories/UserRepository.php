<?php

namespace App\Repositories;

use PDO;

class UserRepository
{
    public const CLIENT_ROLES = ['partner_admin', 'merchant_admin', 'merchant_operator'];

    public function __construct(private PDO $pdo) {}

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
            SELECT m.role AS membership_role, w.id AS workspace_id, w.name AS workspace_name, w.slug AS workspace_slug
            FROM workspace_memberships m
            INNER JOIN workspaces w ON w.id = m.workspace_id
            WHERE m.user_id = ? AND w.status = 'active'
            ORDER BY w.id ASC
        ");
        $stmt->execute([$userId]);

        return array_values(array_filter(
            $stmt->fetchAll(),
            fn($r) => in_array($r['membership_role'], self::CLIENT_ROLES, true)
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
