<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\Connection;
$pdo = Connection::make();

try {
    $pdo->beginTransaction();
    $pdo->exec("UPDATE campaigns SET status = 'ended' WHERE id = 1");
    echo "Update succeeded!\n";
    $pdo->rollBack();
} catch (\Throwable $e) {
    echo "Update failed: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) $pdo->rollBack();
}
