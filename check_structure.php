<?php
require __DIR__ . '/bootstrap.php';

use config\Database;

$db = new Database();
$pdo = $db->connect();

echo "=== Structure table projects ===\n";
$stmt = $pdo->query('DESCRIBE projects');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Structure table project_images ===\n";
$stmt = $pdo->query('DESCRIBE project_images');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Exemple de données project_images ===\n";
$stmt = $pdo->query('SELECT * FROM project_images LIMIT 1');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);

echo "\n\n=== Image principale d'un projet ===\n";
$stmt = $pdo->query('SELECT id, title, image FROM projects LIMIT 1');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);
