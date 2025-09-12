<?php
// Get a specific address by ID
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

// Get address ID from request
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Address ID is required']);
    exit;
}

$address_id = intval($_GET['id']);

try {
    // Get the address and make sure it belongs to the customer
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = :address_id AND customer_id = :customer_id");
    $stmt->bindParam(':address_id', $address_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$address) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Address not found or you do not have permission to view it']);
        exit;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['address' => $address]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
