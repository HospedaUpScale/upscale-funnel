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
        $this->driver = $driver;
        $this->migrationsPath = rtrim($migrationsPath, '/\\');
    }

    /**
     * Executa as migrações pendentes.
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
                // Executa o SQL da migração
                $this->pdo->exec($sql);

                // Regista a migração como aplicada (Corrigido para usar NOW() de forma segura no MySQL)
                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, executed_at) VALUES (:migration, NOW())");
                $stmt->execute([
                    'migration' => $migration
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
        $driver = $this->driver;

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL
            );";
        } else {
            // Padrão para MySQL / MariaDB
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        }

        $this->pdo->exec($sql);
    }

    protected function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    protected function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);
        if (!$files) {
            return [];
        }

        // Filtra apenas os arquivos compatíveis com o driver atual (ex: .mysql.sql ou .sqlite.sql ou .sql geral)
        $migrationFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Verifica se o arquivo corresponde ao driver ou é um .sql genérico
            if (str_ends_with($file, ".{$this->driver}.sql") || str_ends_with($file, '.sql')) {
                // Evita misturar arquivos de drivers diferentes se houver extensão específica
                if ($this->driver === 'mysql' && str_ends_with($file, '.sqlite.sql')) {
                    continue;
                }
                if ($this->driver === 'sqlite' && str_ends_with($file, '.mysql.sql')) {
                    continue;
                }
                $migrationFiles[] = $file;
            }
        }

        sort($migrationFiles);
        return $migrationFiles;
    }
}
