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
$currency = $data['currency'];
$duration = $data['duration'];

try {
    $stmt = $pdo->prepare("INSERT INTO packages (name, price, currency, duration) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $currency, $duration]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
