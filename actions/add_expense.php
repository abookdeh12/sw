<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, currency, expense_date) 
                               VALUES (?, ?, ?, CURDATE())");
        $stmt->execute([$description, $amount, $currency]);
        
        header('Location: ../dashboard.php?success=1');
    } catch (Exception $e) {
        header('Location: ../dashboard.php?error=1');
    }
} else {
    header('Location: ../dashboard.php');
}
?>
