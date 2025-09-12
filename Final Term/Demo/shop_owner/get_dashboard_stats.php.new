<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get shop ID from session
$shop_id = $_SESSION['shop_id'] ?? 0;

// Prepare response data
$response = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'total_products' => 0,
    'total_revenue' => 0
];

// Get total orders for this shop
$sql = "SELECT COUNT(*) as total FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.shop_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['total_orders'] = (int)$row['total'];
}

// Get pending orders for this shop
$sql = "SELECT COUNT(DISTINCT o.id) as total FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.shop_id = ? AND o.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['pending_orders'] = (int)$row['total'];
}

// Get total products for this shop
$sql = "SELECT COUNT(*) as total FROM products WHERE shop_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response['total_products'] = (int)$row['total'];
}

// Get total revenue for this shop
$sql = "SELECT SUM(oi.price * oi.quantity) as total FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.shop_id = ? AND o.status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc() && $row['total'] !== null) {
    $response['total_revenue'] = (float)$row['total'];
}

// Return dashboard stats
echo json_encode($response);
?>
