<?php
// Get all addresses for a customer
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
    // Get all addresses for the customer
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE customer_id = :customer_id ORDER BY is_default DESC, id DESC");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['addresses' => $addresses]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
