<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class ConversionEventRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function log(int $workspaceId, ?int $orderId, string $eventName, ?array $metadata = null): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO conversion_events (workspace_id, order_id, event_name, metadata)
            VALUES (?, ?, ?, ?)
        ");
        $metaJson = $metadata ? json_encode($metadata) : null;
        return $stmt->execute([$workspaceId, $orderId, $eventName, $metaJson]);
    }
}
