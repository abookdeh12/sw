<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = $_POST;
$client_id = $data['client_id'];
$package_id = $data['package_id'];

// Get package details
$stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->execute([$package_id]);
$package = $stmt->fetch();

if (!$package) {
    echo json_encode(['success' => false, 'message' => 'Package not found']);
    exit();
}

// Calculate dates
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime("+{$package['duration']} days"));

try {
    // Insert client package
    $stmt = $pdo->prepare("INSERT INTO client_packages (client_id, package_id, start_date, end_date, payment_amount) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$client_id, $package_id, $start_date, $end_date, $package['price']]);
    
    $client_package_id = $pdo->lastInsertId();
    
    // Record package sale
    $stmt = $pdo->prepare("INSERT INTO package_sales (client_package_id, amount, currency, sale_date) 
                           VALUES (?, ?, ?, CURDATE())");
    $stmt->execute([$client_package_id, $package['price'], $package['currency']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
