<?php
// Update customer profile information
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
$required_fields = ['first_name', 'last_name', 'email', 'phone'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Field ' . $field . ' is required']);
        exit;
    }
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    // Check if email exists and belongs to another user
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = :email AND id != :customer_id");
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Email already in use by another account']);
        exit;
    }
    
    // Update customer profile
    $stmt = $conn->prepare("UPDATE customers SET 
                           first_name = :first_name,
                           last_name = :last_name,
                           email = :email,
                           phone = :phone,
                           updated_at = NOW()
                           WHERE id = :customer_id");
    
    $stmt->bindParam(':first_name', $data['first_name']);
    $stmt->bindParam(':last_name', $data['last_name']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':customer_id', $customer_id);
    
    $stmt->execute();
    
    // Update session data
    $_SESSION['name'] = $data['first_name'] . ' ' . $data['last_name'];
    $_SESSION['email'] = $data['email'];
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
