<?php
require __DIR__ . '/bootstrap.php';

use config\Database;

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->query('SELECT long_description FROM projects WHERE id = 22');
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== Description brute ===\n";
echo $row['long_description'];
echo "\n\n=== Description avec échappement visible ===\n";
echo str_replace(["\n", "\r"], ['\\n', '\\r'], $row['long_description']);
