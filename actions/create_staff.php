<?php
require_once '../config/database.php';

if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$password = password_hash($data['password'], PASSWORD_DEFAULT);
$permissions = $data['permissions'];

try {
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Create user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'staff')");
    $stmt->execute([$username, $password]);
    $user_id = $pdo->lastInsertId();
    
    // Set permissions
    $stmt = $pdo->prepare("INSERT INTO staff_permissions (user_id, dashboard_access, clients_access, gym_access, pos_access, reports_access, packages_items_access, social_media_access, settings_access) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        isset($permissions['dashboard_access']) ? 1 : 0,
        isset($permissions['clients_access']) ? 1 : 0,
        isset($permissions['gym_access']) ? 1 : 0,
        isset($permissions['pos_access']) ? 1 : 0,
        isset($permissions['reports_access']) ? 1 : 0,
        isset($permissions['packages_items_access']) ? 1 : 0,
        isset($permissions['social_media_access']) ? 1 : 0,
        isset($permissions['settings_access']) ? 1 : 0
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
