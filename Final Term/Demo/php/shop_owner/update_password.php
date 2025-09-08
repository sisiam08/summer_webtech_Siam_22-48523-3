<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Check if required fields are provided
if (
    !isset($_POST['current_password']) || empty($_POST['current_password']) ||
    !isset($_POST['new_password']) || empty($_POST['new_password']) ||
    !isset($_POST['confirm_password']) || empty($_POST['confirm_password'])
) {
    echo json_encode(['error' => 'All password fields are required']);
    exit;
}

// Check if new password and confirm password match
if ($_POST['new_password'] !== $_POST['confirm_password']) {
    echo json_encode(['error' => 'New password and confirm password do not match']);
    exit;
}

try {
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$shop_owner_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify current password
    if (!password_verify($_POST['current_password'], $user['password'])) {
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }
    
    // Hash new password
    $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $shop_owner_id]);
    
    // Return success message
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
