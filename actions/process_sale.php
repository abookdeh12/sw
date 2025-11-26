<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'];
$customer_id = $data['customer_id'] ?: null;

try {
    $pdo->beginTransaction();
    
    foreach ($items as $item) {
        // Record sale
        $stmt = $pdo->prepare("INSERT INTO pos_sales (item_id, quantity, total_amount, currency, client_id) 
                               VALUES (?, ?, ?, 'LBP', ?)");
        $total = $item['price'] * $item['quantity'];
        $stmt->execute([$item['id'], $item['quantity'], $total, $customer_id]);
        
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
