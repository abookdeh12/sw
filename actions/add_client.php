<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $family_name = $_POST['family_name'] ?? '';
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $referral = $_POST['referral'] ?? '';
    $referral_other = $_POST['referral_other'] ?? '';
    $note = $_POST['note'] ?? '';
    
    // If "Other" is selected, use the referral_other field
    if ($referral == 'Other') {
        $referral = null;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (name, family_name, phone, gender, referral, referral_other, note) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $family_name, $phone, $gender, $referral, $referral_other, $note]);
        
        header('Location: ../clients.php?success=1');
    } catch (Exception $e) {
        header('Location: ../clients.php?error=1');
    }
} else {
    header('Location: ../clients.php');
}
?>
