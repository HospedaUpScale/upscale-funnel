<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\Connection;
$pdo = Connection::make();

echo "--- CAMPAIGNS ---\n";
$stmt = $pdo->query("SELECT * FROM campaigns");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- OFFERS ---\n";
$stmt = $pdo->query("SELECT id, workspace_id, campaign_id, title, is_active FROM upsell_offers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
