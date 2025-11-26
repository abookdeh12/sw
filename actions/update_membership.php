<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$search = $data['search'];
$action = $data['action'];

// Find client
$stmt = $pdo->prepare("SELECT id FROM clients WHERE name LIKE ? OR family_name LIKE ? OR phone LIKE ? LIMIT 1");
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['success' => false, 'message' => 'Client not found']);
    exit();
}

// Get active package
$stmt = $pdo->prepare("SELECT id, end_date FROM client_packages WHERE client_id = ? ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$client['id']]);
$package = $stmt->fetch();

if (!$package) {
    echo json_encode(['success' => false, 'message' => 'No package found for this client']);
    exit();
}

try {
    if ($action == 'extend') {
        $days = intval($data['days']);
        $stmt = $pdo->prepare("UPDATE client_packages SET end_date = DATE_ADD(end_date, INTERVAL ? DAY), status = 'active' WHERE id = ?");
        $stmt->execute([$days, $package['id']]);
    } else {
        $new_end_date = $data['end_date'];
        $stmt = $pdo->prepare("UPDATE client_packages SET end_date = ?, status = 'active' WHERE id = ?");
        $stmt->execute([$new_end_date, $package['id']]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
