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
    // Get total number of products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE shop_owner_id = ?");
    $stmt->execute([$shop_owner_id]);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total number of orders
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$shop_owner_id]);
    $totalOrders = $stmt->rowCount();
    
    // Get total revenue
    $stmt = $pdo->prepare("
        SELECT SUM(oi.price * oi.quantity) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ?
    ");
    $stmt->execute([$shop_owner_id]);
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get pending orders
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ? AND o.status IN ('pending', 'processing')
    ");
    $stmt->execute([$shop_owner_id]);
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Return the dashboard data
    echo json_encode([
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'pendingOrders' => $pendingOrders
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
