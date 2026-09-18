<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class CustomerRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function upsert(int $workspaceId, string $name, ?string $phone = null, ?string $cpf = null, ?string $email = null): array
    {
        // Clean CPF if present
        $cleanCpf = $cpf ? preg_replace('/\D/', '', $cpf) : null;
        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;

        $existing = null;
        if (!empty($cleanCpf)) {
            $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE workspace_id = ? AND cpf = ?");
            $stmt->execute([$workspaceId, $cleanCpf]);
            $existing = $stmt->fetch();
        }

        if (!$existing && !empty($cleanPhone)) {
            $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE workspace_id = ? AND phone = ?");
            $stmt->execute([$workspaceId, $cleanPhone]);
            $existing = $stmt->fetch();
        }

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE customers 
                SET name = ?, phone = COALESCE(?, phone), cpf = COALESCE(?, cpf), email = COALESCE(?, email), updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$name, $cleanPhone, $cleanCpf, $email, $existing['id']]);
            $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$existing['id']]);
            return $stmt->fetch();
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO customers (workspace_id, name, phone, cpf, email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$workspaceId, $name, $cleanPhone, $cleanCpf, $email]);

        $id = (int)$this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
