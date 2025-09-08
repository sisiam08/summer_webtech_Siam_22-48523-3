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

// Check if order ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = $_GET['id'];

try {
    // First check if the order contains products from this shop owner
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND p.shop_owner_id = ?
    ");
    $stmt->execute([$order_id, $shop_owner_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo json_encode(['error' => 'Order not found or you do not have permission to view it']);
        exit;
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, 
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        a.address_line as shipping_address,
        a.city as shipping_city,
        a.postal_code as shipping_postal_code,
        a.country as shipping_country
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN addresses a ON o.shipping_address_id = a.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image, p.sku
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND p.shop_owner_id = ?
    ");
    $stmt->execute([$order_id, $shop_owner_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order history
    $stmt = $pdo->prepare("
        SELECT oh.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM order_history oh
        LEFT JOIN users u ON oh.updated_by = u.id
        WHERE oh.order_id = ?
        ORDER BY oh.created_at ASC
    ");
    $stmt->execute([$order_id]);
    $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return all the order information
    echo json_encode([
        'order' => $order,
        'orderItems' => $orderItems,
        'orderHistory' => $orderHistory
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
