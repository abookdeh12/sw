<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

try {
    // Delete staff permissions first (foreign key)
    $stmt = $pdo->prepare("DELETE FROM staff_permissions WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
