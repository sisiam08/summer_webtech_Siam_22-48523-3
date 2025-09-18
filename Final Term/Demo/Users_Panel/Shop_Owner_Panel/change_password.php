<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include database connection
require_once __DIR__ . '/../../Database/database.php';

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    
    // Basic validation
    if (empty($currentPassword) || empty($newPassword)) {
        throw new Exception("Current and new passwords are required");
    }
    
    if (strlen($newPassword) < 8) {
        throw new Exception("New password must be at least 8 characters long");
    }
    
    // Get current password hash
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $result->fetch_assoc();
    $storedHash = $userData['password'];
    
    // Verify current password
    if (!password_verify($currentPassword, $storedHash)) {
        throw new Exception("Current password is incorrect");
    }
    
    // Hash new password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('si', $newHash, $userId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to change password: " . $conn->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
