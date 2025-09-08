<?php
// Get customer profile information
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

try {
    // Get customer profile
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Customer profile not found']);
        exit;
    }
    
    // Remove sensitive information
    unset($profile['password']);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($profile);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
