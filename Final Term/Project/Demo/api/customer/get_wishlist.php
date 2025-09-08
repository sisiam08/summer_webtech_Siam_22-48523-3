<?php
// Get all wishlist items for a customer
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
    // Get all wishlist items with product details
    $stmt = $conn->prepare("SELECT w.id, w.product_id, w.created_at,
                                  p.name, p.description, p.price, p.sale_price, p.stock, p.image, 
                                  p.category_id, c.name as category_name
                           FROM wishlist w
                           JOIN products p ON w.product_id = p.id
                           LEFT JOIN categories c ON p.category_id = c.id
                           WHERE w.customer_id = :customer_id
                           ORDER BY w.created_at DESC");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['items' => $items]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
