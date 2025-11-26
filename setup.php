<?php
// Setup script - Run this once to create admin user with correct password
// Access this file at: http://localhost/gym_system/setup.php

$host = 'localhost';
$dbname = 'gym_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Generate correct password hash for admin123
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update existing admin password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$admin_password]);
        echo "<h2>Admin password updated successfully!</h2>";
    } else {
        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$admin_password]);
        $user_id = $pdo->lastInsertId();
        
        // Add permissions
        $stmt = $pdo->prepare("INSERT INTO staff_permissions (user_id, dashboard_access, clients_access, gym_access, pos_access, reports_access, packages_items_access, social_media_access, settings_access) VALUES (?, 1, 1, 1, 1, 1, 1, 1, 1)");
        $stmt->execute([$user_id]);
        
        echo "<h2>Admin user created successfully!</h2>";
    }
    
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
    echo "<hr>";
    echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this setup.php file after use for security!</p>";
    
} catch(PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure you have:</p>";
    echo "<ol>";
    echo "<li>Started MySQL in XAMPP</li>";
    echo "<li>Created the database 'gym_system' in phpMyAdmin</li>";
    echo "<li>Imported the gym_system.sql file</li>";
    echo "</ol>";
}
?>
