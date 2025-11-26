<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'];
$customer_id = $data['customer_id'];

try {
    $pdo->beginTransaction();
    
    foreach ($items as $item) {
        $total = $item['price'] * $item['quantity'];
        
        // Add to debts table
        $stmt = $pdo->prepare("INSERT INTO debts (client_id, item_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$customer_id, $item['id'], $total]);
        
        // Update stock
        $stmt = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
