<?php

namespace App\Database;

use PDO;
use Exception;
use RuntimeException;

class Migrator
{
    protected PDO $pdo;
    protected string $driver;
    protected string $migrationsPath;

    public function __construct(PDO $pdo, string $driver, string $migrationsPath)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->driver = strtolower(trim($driver));
        $this->migrationsPath = rtrim($migrationsPath, '/\\');
    }

    /**
     * Executa todas as migrações pendentes.
     *
     * @return array Lista de arquivos de migração aplicados.
     */
    public function run(): array
    {
        $this->createMigrationsTableIfNeeded();

        $appliedMigrations = $this->getAppliedMigrations();
        $allMigrationFiles = $this->getMigrationFiles();

        $pendingMigrations = array_diff($allMigrationFiles, $appliedMigrations);
        $applied = [];

        foreach ($pendingMigrations as $migration) {
            $filePath = $this->migrationsPath . '/' . $migration;

            if (!file_exists($filePath)) {
                continue;
            }

            $sql = file_get_contents($filePath);

            try {
                // Se o driver for MySQL, converte expressões SQLite como datetime('now') para CURRENT_TIMESTAMP
                if ($this->driver === 'mysql') {
                    $sql = preg_replace("/datetime\s*\(\s*'now'\s*\)/i", 'CURRENT_TIMESTAMP', $sql);
                    $sql = preg_replace("/DEFAULT\s+\(\s*datetime\s*\(\s*'now'\s*\)\s*\)/i", 'DEFAULT CURRENT_TIMESTAMP', $sql);
                    $sql = preg_replace("/\(\s*'now'\s*\)/i", 'CURRENT_TIMESTAMP', $sql);
                }

                // Remove linhas de comentário SQL (-- ...) para não interferir na separação de comandos
                $cleanSql = preg_replace('/^\s*--.*$/m', '', $sql);

                // Divide o arquivo SQL por ';' e executa cada instrução individualmente
                $statements = explode(';', $cleanSql);

                foreach ($statements as $statement) {
                    $stmtTrimmed = trim($statement);
                    if ($stmtTrimmed !== '') {
                        $this->pdo->exec($stmtTrimmed);
                    }
                }

                // Regista a migração aplicada usando data gerada pelo PHP
                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, executed_at) VALUES (:migration, :executed_at)");
                $stmt->execute([
                    'migration'   => $migration,
                    'executed_at' => date('Y-m-d H:i:s'),
                ]);

                $applied[] = $migration;
            } catch (Exception $e) {
                throw new RuntimeException("Failed applying migration {$migration}: " . $e->getMessage(), 0, $e);
            }
        }

        return $applied;
    }

    protected function createMigrationsTableIfNeeded(): void
    {
        if ($this->driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        }

        $this->pdo->exec($sql);
    }

    protected function getAppliedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM migrations");
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        if (!$files) {
            return [];
        }

        $migrationFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Filtra estritamente os arquivos do driver ativo (.mysql.sql ou .sqlite.sql)
            if ($this->driver === 'mysql' && str_ends_with($file, '.sqlite.sql')) {
                continue;
            }
            if ($this->driver === 'sqlite' && str_ends_with($file, '.mysql.sql')) {
                continue;
            }

            if (str_ends_with($file, ".{$this->driver}.sql") || (str_ends_with($file, '.sql') && !str_ends_with($file, '.mysql.sql') && !str_ends_with($file, '.sqlite.sql'))) {
                $migrationFiles[] = $file;
            }
        }

        sort($migrationFiles, SORT_NATURAL);
        return $migrationFiles;
    }
}
