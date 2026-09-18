<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class OfferRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function find(int $id, ?int $workspaceId = null): ?array
    {
        $sql = "SELECT * FROM upsell_offers WHERE id = ?";
        $params = [$id];
        if ($workspaceId !== null) {
            $sql .= " AND workspace_id = ?";
            $params[] = $workspaceId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function defaultActiveForWorkspace(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM upsell_offers 
            WHERE workspace_id = ? AND is_active = 1 
            ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
