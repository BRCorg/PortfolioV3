<?php
require __DIR__ . '/bootstrap.php';

use config\Database;

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->query('SELECT file_path FROM project_images LIMIT 1');
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "file_path stocké en base : " . $result['file_path'];
