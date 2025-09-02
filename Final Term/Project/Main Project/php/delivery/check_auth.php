<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'isAuthenticated' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

// Check if user has delivery role
if ($_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'isAuthenticated' => false,
        'error' => 'Unauthorized access',
        'role' => $_SESSION['role']
    ]);
    exit;
}

// User is authenticated and has delivery role
// Get delivery person details from database
require_once '../db_connect.php';

try {
    $stmt = $conn->prepare("SELECT * FROM delivery_personnel WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'isAuthenticated' => false,
            'error' => 'Delivery personnel not found'
        ]);
        exit;
    }
    
    $delivery_person = $result->fetch_assoc();
    
    // Get user details
    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    echo json_encode([
        'isAuthenticated' => true,
        'role' => $_SESSION['role'],
        'id' => $delivery_person['id'],
        'user_id' => $_SESSION['user_id'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email'],
        'status' => $delivery_person['status']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'isAuthenticated' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
