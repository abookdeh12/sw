<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$client_id = $data['client_id'];

try {
    // Get total debt amount
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM debts WHERE client_id = ? AND paid = 0");
    $stmt->execute([$client_id]);
    $total = $stmt->fetch()['total'];
    
    // Mark debts as paid
    $stmt = $pdo->prepare("UPDATE debts SET paid = 1, paid_date = NOW() WHERE client_id = ? AND paid = 0");
    $stmt->execute([$client_id]);
    
    // Record as POS sale
    $stmt = $pdo->prepare("INSERT INTO pos_sales (item_id, quantity, total_amount, currency, client_id) 
                           VALUES (NULL, 1, ?, 'LBP', ?)");
    $stmt->execute([$total, $client_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
