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
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    $conn = connectDB();
    
    // Get shop ID for the current shop owner (same logic as get_orders.php)
    $stmt = $conn->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode(['success' => false, 'message' => 'No shop found for this account']);
        exit();
    }
    
    $shopId = $shop['id'];
    
    // Get order status - ensure the order belongs to the shop owner's shop
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND shop_id = ?");
    $stmt->execute([$orderId, $shopId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to your shop']);
        exit();
    }
    
    // Return success response with order status
    echo json_encode([
        'success' => true,
        'status' => $order['status'],
        'order_id' => $orderId
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while getting order status']);
}
?>