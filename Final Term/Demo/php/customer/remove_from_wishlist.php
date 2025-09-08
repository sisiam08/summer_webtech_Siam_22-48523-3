<?php
// Remove a product from the wishlist
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

if (!isset($data['product_id']) || empty($data['product_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$product_id = intval($data['product_id']);

try {
    // Delete the wishlist item
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE customer_id = :customer_id AND product_id = :product_id");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Product removed from wishlist successfully'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
