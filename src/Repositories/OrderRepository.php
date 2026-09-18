<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class OrderRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (
                workspace_id, customer_id, offer_id, flow_type, status,
                external_order_id, base_amount, upsell_amount, total_amount,
                utm_source, utm_medium, utm_campaign
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['workspace_id'],
            $data['customer_id'] ?? null,
            $data['offer_id'] ?? null,
            $data['flow_type'] ?? 'post_pix_upsell',
            $data['status'] ?? 'pending',
            $data['external_order_id'] ?? null,
            $data['base_amount'] ?? 0,
            $data['upsell_amount'] ?? 0,
            $data['total_amount'] ?? 0,
            $data['utm_source'] ?? null,
            $data['utm_medium'] ?? null,
            $data['utm_campaign'] ?? null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }

    public function markPaid(int $orderId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'paid', paid_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$orderId]);
    }

    public function findByExternalOrderId(string $externalOrderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE external_order_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$externalOrderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
