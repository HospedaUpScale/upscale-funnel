<?php

namespace App\Database;

use PDO;
use RuntimeException;

class Connection
{
    private static ?PDO $instance = null;

    public static function make(?array $config = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if ($config === null) {
            $appConfig = require __DIR__ . '/../../config/config.php';
            $config = $appConfig['db'];
        }

        $driver = strtolower($config['driver'] ?? 'sqlite');

        if ($driver === 'sqlite') {
            $path = $config['sqlite_path'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdo = new PDO("sqlite:{$path}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            self::$instance = $pdo;
            return self::$instance;
        }

        if ($driver === 'mysql') {
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $db = $config['database'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $options);
            self::$instance = $pdo;
            return self::$instance;
        }

        throw new RuntimeException("Unsupported database driver: {$driver}");
    }

    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
