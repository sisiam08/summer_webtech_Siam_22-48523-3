<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../Database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Check if token session data exists
if (!isset($_SESSION['password_reset_token']) || !isset($_SESSION['password_reset_email']) || !isset($_SESSION['token_generated_time'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
    exit;
}

// Check if token is still valid (30 minutes expiry)
$tokenGeneratedTime = $_SESSION['token_generated_time'];
$currentTime = time();
$tokenExpiryTime = 30 * 60; // 30 minutes

if (($currentTime - $tokenGeneratedTime) > $tokenExpiryTime) {
    // Clear expired token session
    unset($_SESSION['password_reset_token']);
    unset($_SESSION['password_reset_email']);
    unset($_SESSION['token_generated_time']);
    
    echo json_encode(['success' => false, 'message' => 'Reset token has expired. Please start the process again']);
    exit;
}

// Verify token
if ($_SESSION['password_reset_token'] !== $token) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
    exit;
}

try {
    $email = $_SESSION['password_reset_email'];
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    
    if ($stmt->execute()) {
        // Clear reset token session data
        unset($_SESSION['password_reset_token']);
        unset($_SESSION['password_reset_email']);
        unset($_SESSION['token_generated_time']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset successfully! Redirecting to login page...'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>