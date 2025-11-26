<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'];

// Save theme to session
$_SESSION['theme'] = $theme;

echo json_encode(['success' => true]);
?>
