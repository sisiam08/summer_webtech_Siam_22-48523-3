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
    // Get recent orders for this shop owner's products
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.id, o.order_number, o.created_at, o.status, o.total_amount,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.shop_owner_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$shop_owner_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the orders data
    echo json_encode([
        'orders' => $orders
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
