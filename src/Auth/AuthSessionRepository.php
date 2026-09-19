<?php

namespace App\Auth;

use PDO;

class AuthSessionRepository
{
    private static bool $schemaChecked = false;

    public function __construct(private PDO $pdo) {}

    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        try {
            $this->pdo->query('SELECT 1 FROM admin_sessions LIMIT 1');
        } catch (\Throwable $e) {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'mysql' : 'sqlite';
            $file = dirname(__DIR__, 2) . "/migrations/0013_admin_sessions.{$driver}.sql";
            $sql = is_file($file) ? file_get_contents($file) : false;
            if ($sql !== false && trim($sql) !== '') {
                $this->pdo->exec($sql);
            }
        }

        self::$schemaChecked = true;
    }

    public function create(int $userId, int $workspaceId, string $role, int $ttlHours = 168): array
    {
        $this->ensureSchema();

        $now = gmdate('Y-m-d H:i:s');
        $this->pdo->prepare('DELETE FROM admin_sessions WHERE expires_at < ?')->execute([$now]);

        $raw = 'ups_' . bin2hex(random_bytes(24));
        $expires = gmdate('Y-m-d H:i:s', time() + $ttlHours * 3600);

        $this->pdo->prepare(
            'INSERT INTO admin_sessions (token_hash, user_id, workspace_id, role, expires_at) VALUES (?, ?, ?, ?, ?)'
        )->execute([hash('sha256', $raw), $userId, $workspaceId, $role, $expires]);

        return ['token' => $raw, 'expires_at' => $expires];
    }

    public function find(string $rawToken): ?array
    {
        $this->ensureSchema();

        $stmt = $this->pdo->prepare('SELECT * FROM admin_sessions WHERE token_hash = ? AND expires_at > ? LIMIT 1');
        $stmt->execute([hash('sha256', $rawToken), gmdate('Y-m-d H:i:s')]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function revoke(string $rawToken): void
    {
        $this->ensureSchema();
        $this->pdo->prepare('DELETE FROM admin_sessions WHERE token_hash = ?')->execute([hash('sha256', $rawToken)]);
    }

    public function revokeForUserWorkspace(int $userId, int $workspaceId): void
    {
        $this->ensureSchema();
        $this->pdo->prepare('DELETE FROM admin_sessions WHERE user_id = ? AND workspace_id = ?')->execute([$userId, $workspaceId]);
    }
}
