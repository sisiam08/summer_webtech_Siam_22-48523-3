<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate data
if (!isset($data['current_password']) || !isset($data['new_password'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

$current_password = $data['current_password'];
$new_password = $data['new_password'];

// Connect to database
require_once '../db_connect.php';

try {
    // Get user's current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $current_hash = $user['password'];
    
    // Verify current password
    if (!password_verify($current_password, $current_hash)) {
        echo json_encode([
            'success' => false,
            'error' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Validate new password strength
    if (strlen($new_password) < 8) {
        echo json_encode([
            'success' => false,
            'error' => 'New password must be at least 8 characters long'
        ]);
        exit;
    }
    
    // Hash new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update password'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
