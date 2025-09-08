<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';
require_once '../order_tracking.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Check if order ID and status are provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['error' => 'Order ID and status are required']);
    exit;
}

$order_id = $_POST['id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

try {
    // First check if the order contains products from this shop owner
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND p.shop_owner_id = ?
    ");
    $stmt->execute([$order_id, $shop_owner_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] === 0) {
        echo json_encode(['error' => 'You do not have permission to update this order']);
        exit;
    }
    
    // For multi-shop orders, only update the status of items from this shop
    // Get shop information for tracking
    $stmt = $pdo->prepare("SELECT name FROM shops WHERE owner_id = ?");
    $stmt->execute([$shop_owner_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    $shopName = $shop ? $shop['name'] : 'Your shop';
    
    // Create description for tracking
    $description = "Status updated to {$status} by {$shopName}";
    
    // Check if this is a multi-shop order
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.shop_owner_id) as shop_count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['shop_count'] > 1) {
        // This is a multi-shop order
        // Update status of items from this shop and create a tracking event
        // In a real-world scenario, you would maintain a separate status for each shop's items
        
        // For now, just update the main order status
        $success = updateOrderStatus($pdo, $order_id, $status, $description, $shop_owner_id);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update order status'
            ]);
        }
    } else {
        // This is a single-shop order, update the entire order
        $success = updateOrderStatus($pdo, $order_id, $status, $description, $shop_owner_id);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);
            
            // If status is shipped, automatically create delivery assignment if not already exists
            if ($status === 'shipped') {
                // Check if delivery assignment exists
                $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$delivery) {
                    // Create a delivery request
                    $stmt = $pdo->prepare("
                        INSERT INTO delivery_requests (order_id, shop_id, status, created_at)
                        VALUES (?, (SELECT id FROM shops WHERE owner_id = ?), 'pending', NOW())
                    ");
                    $stmt->execute([$order_id, $shop_owner_id]);
                }
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update order status'
            ]);
        }
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
