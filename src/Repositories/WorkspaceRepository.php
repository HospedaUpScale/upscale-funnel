<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Tenancy\Workspace;
use PDO;

class WorkspaceRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function findById(int $id): ?Workspace
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Workspace::fromRow($row) : null;
    }

    public function findBySlug(string $slug): ?Workspace
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? Workspace::fromRow($row) : null;
    }

    public function findByCustomDomain(string $domain): ?Workspace
    {
        $domain = strtolower(trim($domain));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        try {
            $stmt = $this->pdo->prepare("SELECT w.* FROM workspaces w INNER JOIN custom_domains d ON d.workspace_id = w.id WHERE d.domain = ? AND d.status = 'active' LIMIT 1");
            $stmt->execute([$domain]);
            $row = $stmt->fetch();
            if ($row) {
                return Workspace::fromRow($row);
            }
        } catch (\PDOException $e) {
        }

        $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE custom_domain = ?");
        $stmt->execute([$domain]);
        $row = $stmt->fetch();
        return $row ? Workspace::fromRow($row) : null;
    }

    public function findByAdminTokenHash(string $hash): ?Workspace
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE admin_token_hash = ?");
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        return $row ? Workspace::fromRow($row) : null;
    }

    public function findByApiTokenHash(string $hash): ?Workspace
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE api_token_hash = ?");
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        return $row ? Workspace::fromRow($row) : null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM workspaces ORDER BY id ASC");
        $workspaces = [];
        while ($row = $stmt->fetch()) {
            $workspaces[] = Workspace::fromRow($row);
        }
        return $workspaces;
    }

    public function findDescendants(int $workspaceId): array
    {
        // Iterative fetch to stay compatible with MySQL 5.7 and SQLite
        $result = [];
        $queue = [$workspaceId];
        $visited = [];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            if (isset($visited[$currentId])) continue;
            $visited[$currentId] = true;

            $stmt = $this->pdo->prepare("SELECT * FROM workspaces WHERE parent_id = ?");
            $stmt->execute([$currentId]);
            while ($row = $stmt->fetch()) {
                $ws = Workspace::fromRow($row);
                $result[] = $ws;
                $queue[] = $ws->id;
            }
        }

        return $result;
    }

    public function create(array $data): Workspace
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workspaces (
                parent_id, type, name, slug, custom_domain, document,
                api_token_hash, admin_token_hash, gateway_provider, gateway_credentials,
                split_recipient_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $creds = !empty($data['gateway_credentials'])
            ? (is_string($data['gateway_credentials']) ? $data['gateway_credentials'] : json_encode($data['gateway_credentials']))
            : null;

        $stmt->execute([
            $data['parent_id'] ?? null,
            $data['type'] ?? 'merchant',
            $data['name'],
            $data['slug'],
            $data['custom_domain'] ?? null,
            $data['document'] ?? null,
            $data['api_token_hash'],
            $data['admin_token_hash'],
            $data['gateway_provider'] ?? 'mock',
            $creds,
            $data['split_recipient_id'] ?? null,
            $data['status'] ?? 'active'
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }
}
