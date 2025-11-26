<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

$search = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($search) < 2) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, phone FROM clients WHERE name LIKE ? OR phone LIKE ? LIMIT 10");
$stmt->execute(["%$search%", "%$search%"]);
$clients = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($clients);
?>
