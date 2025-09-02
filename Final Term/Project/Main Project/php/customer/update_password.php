<?php
// Update customer password
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Get customer ID
$customer_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['current_password']) || empty($data['current_password'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Current password is required']);
    exit;
}

if (!isset($data['new_password']) || empty($data['new_password'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'New password is required']);
    exit;
}

// Validate password strength
if (strlen($data['new_password']) < 8) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Password must be at least 8 characters long']);
    exit;
}

if (!preg_match('/[A-Z]/', $data['new_password'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Password must contain at least one uppercase letter']);
    exit;
}

if (!preg_match('/[a-z]/', $data['new_password'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Password must contain at least one lowercase letter']);
    exit;
}

if (!preg_match('/[0-9]/', $data['new_password'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Password must contain at least one number']);
    exit;
}

try {
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM customers WHERE id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($data['current_password'], $result['password'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }
    
    // Hash new password
    $new_password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE customers SET 
                           password = :password,
                           updated_at = NOW()
                           WHERE id = :customer_id");
    
    $stmt->bindParam(':password', $new_password_hash);
    $stmt->bindParam(':customer_id', $customer_id);
    
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
