<?php

namespace App\AI;

use PDO;
use RuntimeException;

/** Global counters persist across deployments and workspace switches. Failed calls also consume a slot. */
final class GenerationQuota
{
    public function __construct(private PDO $pdo, private int $totalLimit = 100, private int $dailyLimit = 20) {}

    public function status(): array
    {
        $q = $this->pdo->prepare('SELECT bucket, attempts FROM ai_generation_quota WHERE bucket IN (?, ?)');
        $q->execute(['total', gmdate('Y-m-d')]);
        $counts = $q->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'total_remaining' => max(0, $this->totalLimit - (int)($counts['total'] ?? 0)),
            'daily_remaining' => max(0, $this->dailyLimit - (int)($counts[gmdate('Y-m-d')] ?? 0)),
            'total_limit' => $this->totalLimit, 'daily_limit' => $this->dailyLimit,
        ];
    }

    public function reserve(): array
    {
        $this->pdo->beginTransaction();
        try {
            // Acquire the global row lock first, serializing reservations in MySQL and SQLite.
            $this->increment('total', $this->totalLimit);
            $sql = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
                ? 'INSERT IGNORE INTO ai_generation_quota (bucket, attempts) VALUES (?, 0)'
                : 'INSERT OR IGNORE INTO ai_generation_quota (bucket, attempts) VALUES (?, 0)';
            $this->pdo->prepare($sql)->execute([gmdate('Y-m-d')]);
            $this->increment(gmdate('Y-m-d'), $this->dailyLimit);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
        return $this->status();
    }

    private function increment(string $bucket, int $limit): void
    {
        $q = $this->pdo->prepare('UPDATE ai_generation_quota SET attempts = attempts + 1 WHERE bucket = ? AND attempts < ?');
        $q->execute([$bucket, max(0, $limit)]);
        if ($q->rowCount() !== 1) {
            throw new RuntimeException('Limite de tentativas de IA atingido. Confira os limites diário e total no servidor.', 429);
        }
    }
}
