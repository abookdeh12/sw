<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$search = $data['search'];

// Find client
$stmt = $pdo->prepare("SELECT id FROM clients WHERE name LIKE ? OR phone LIKE ? LIMIT 1");
$stmt->execute(["%$search%", "%$search%"]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['success' => false, 'message' => 'Client not found']);
    exit();
}

// Get active package
$stmt = $pdo->prepare("SELECT package_id FROM client_packages WHERE client_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$client['id']]);
$package = $stmt->fetch();

if (!$package) {
    echo json_encode(['success' => false, 'message' => 'No active package found']);
    exit();
}

// Check if already marked today
$stmt = $pdo->prepare("SELECT id FROM attendance WHERE client_id = ? AND DATE(check_in_time) = CURDATE()");
$stmt->execute([$client['id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Attendance already marked today']);
    exit();
}

// Mark attendance
$stmt = $pdo->prepare("INSERT INTO attendance (client_id, package_id) VALUES (?, ?)");
$stmt->execute([$client['id'], $package['package_id']]);

echo json_encode(['success' => true]);
?>
