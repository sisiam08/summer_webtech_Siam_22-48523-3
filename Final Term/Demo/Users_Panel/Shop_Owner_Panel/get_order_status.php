<?php
// Start session and include required files
session_start();
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isLoggedIn() || !isShopOwner()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the logged-in shop owner's shop ID
$shopId = getShopIdForOwner();
if (!$shopId) {
    echo json_encode(['success' => false, 'message' => 'No shop found for this account']);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    $conn = connectDB();
    
    // Get order status - ensure the order belongs to the shop owner's shop
    // This query checks if the order contains products from the shop owner's shop
    $query = "SELECT DISTINCT o.status, o.id 
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN products p ON oi.product_id = p.id
              WHERE o.id = :order_id AND p.shop_id = :shop_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->bindParam(':shop_id', $shopId, PDO::PARAM_INT);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to your shop']);
        exit;
    }
    
    // Return success response with order status
    echo json_encode([
        'success' => true,
        'status' => $order['status'],
        'order_id' => $order['id']
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while getting order status']);
}
?>