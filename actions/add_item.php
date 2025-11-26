<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'];
$price = $data['price'];
$stock = $data['stock'];

try {
    $stmt = $pdo->prepare("INSERT INTO items (name, price, stock) VALUES (?, ?, ?)");
    $stmt->execute([$name, $price, $stock]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
