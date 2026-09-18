<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class TransactionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByGatewayTxId(string $txId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE gateway_tx_id = ?");
        $stmt->execute([$txId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (
                order_id, type, status, gateway, gateway_tx_id,
                pix_copy_paste, pix_qr_base64, amount, split_details,
                mock_auto_confirm_at, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $splits = !empty($data['split_details'])
            ? (is_string($data['split_details']) ? $data['split_details'] : json_encode($data['split_details']))
            : null;

        $stmt->execute([
            $data['order_id'],
            $data['type'] ?? 'upsell_order',
            $data['status'] ?? 'pending',
            $data['gateway'] ?? 'mock',
            $data['gateway_tx_id'] ?? null,
            $data['pix_copy_paste'] ?? null,
            $data['pix_qr_base64'] ?? null,
            $data['amount'] ?? 0,
            $splits,
            $data['mock_auto_confirm_at'] ?? null,
            $data['expires_at'] ?? null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }

    public function updateCharge(int $id, array $chargeData): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE transactions 
            SET gateway_tx_id = ?, pix_copy_paste = ?, pix_qr_base64 = ?,
                expires_at = ?, mock_auto_confirm_at = ?, split_details = ?
            WHERE id = ?
        ");

        $splits = !empty($chargeData['split_details'])
            ? (is_string($chargeData['split_details']) ? $chargeData['split_details'] : json_encode($chargeData['split_details']))
            : null;

        return $stmt->execute([
            $chargeData['gateway_tx_id'],
            $chargeData['pix_copy_paste'],
            $chargeData['pix_qr_base64'],
            $chargeData['expires_at'],
            $chargeData['mock_auto_confirm_at'] ?? null,
            $splits,
            $id
        ]);
    }

    public function markPaid(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE transactions 
            SET status = 'paid', paid_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public function markExpired(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE transactions 
            SET status = 'expired' 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public function findByOrderId(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
