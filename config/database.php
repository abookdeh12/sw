<?php
// Database configuration
$host = 'localhost';
$dbname = 'gym_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check user permissions
function hasAccess($page) {
    if ($_SESSION['role'] == 'admin') {
        return true;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM staff_permissions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $permissions = $stmt->fetch();
    
    $access_map = [
        'dashboard' => 'dashboard_access',
        'clients' => 'clients_access',
        'gym' => 'gym_access',
        'pos' => 'pos_access',
        'reports' => 'reports_access',
        'packages_items' => 'packages_items_access',
        'social_media' => 'social_media_access',
        'settings' => 'settings_access'
    ];
    
    return isset($access_map[$page]) && $permissions[$access_map[$page]];
}
?>
