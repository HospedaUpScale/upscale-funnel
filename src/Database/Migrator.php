<?php

namespace App\Database;

use PDO;

class Migrator
{
    public function __construct(
        private PDO $pdo,
        private string $driver,
        private string $migrationsDir
    ) {}

    public function run(): array
    {
        $this->ensureMigrationsTable();

        $ext = ($this->driver === 'mysql') ? 'mysql.sql' : 'sqlite.sql';
        $files = glob($this->migrationsDir . DIRECTORY_SEPARATOR . "*." . $ext);
        sort($files);

        $applied = [];
        $stmt = $this->pdo->query("SELECT filename FROM migrations");
        $alreadyRun = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $alreadyRun, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            $useTransaction = $this->driver !== 'mysql';
            if ($useTransaction) {
                $this->pdo->beginTransaction();
            }
            try {
                $this->pdo->exec($sql);
                $ins = $this->pdo->prepare("INSERT INTO migrations (filename, applied_at) VALUES (?, datetime('now'))");
                if ($this->driver === 'mysql') {
                    $ins = $this->pdo->prepare("INSERT INTO migrations (filename, applied_at) VALUES (?, NOW())");
                }
                $ins->execute([$filename]);
                if ($useTransaction) {
                    $this->pdo->commit();
                }
                $applied[] = $filename;
            } catch (\Throwable $e) {
                if ($useTransaction && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw new \RuntimeException("Failed applying migration {$filename}: " . $e->getMessage(), 0, $e);
            }
        }

        return $applied;
    }

    private function ensureMigrationsTable(): void
    {
        if ($this->driver === 'mysql') {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(190) NOT NULL UNIQUE,
                    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    filename TEXT NOT NULL UNIQUE,
                    applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            ");
        }
    }
}
