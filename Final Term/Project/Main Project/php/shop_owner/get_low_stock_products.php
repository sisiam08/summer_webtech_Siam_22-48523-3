<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

try {
    // Get low stock products (less than or equal to 10 items)
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.stock, p.image, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.shop_owner_id = ? AND p.stock <= 10
        ORDER BY p.stock ASC
        LIMIT 5
    ");
    $stmt->execute([$shop_owner_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the products data
    echo json_encode([
        'products' => $products
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
