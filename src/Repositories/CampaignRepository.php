<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class CampaignRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function defaultForWorkspace(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM campaigns 
            WHERE workspace_id = ? AND status = 'active' 
            ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetch() ?: null;
    }
}
