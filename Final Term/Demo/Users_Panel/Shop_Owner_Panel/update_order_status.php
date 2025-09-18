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

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit;
}

$orderId = (int)$input['id'];
$newStatus = trim($input['status']);

// Validate status values
$allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    $conn = connectDB();
    
    // First, verify that the order belongs to the shop owner's shop
    $verifyQuery = "SELECT DISTINCT o.id, o.status 
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.id = :order_id AND p.shop_id = :shop_id";
    
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->bindParam(':shop_id', $shopId, PDO::PARAM_INT);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to your shop']);
        exit;
    }
    
    // Check if the status transition is valid
    $currentStatus = $order['status'];
    $validTransitions = [
        'Pending' => ['Processing', 'Cancelled'],
        'Processing' => ['Shipped', 'Cancelled'],
        'Shipped' => ['Delivered'],
        'Delivered' => [], // Cannot change from delivered
        'Cancelled' => []  // Cannot change from cancelled
    ];
    
    if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
        echo json_encode(['success' => false, 'message' => "Cannot change status from {$currentStatus} to {$newStatus}"]);
        exit;
    }
    
    // Update the order status
    $updateQuery = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :order_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
    $updateStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Log the status change for audit purposes
        $logQuery = "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, changed_at) 
                     VALUES (:order_id, :old_status, :new_status, :changed_by, NOW())";
        
        try {
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $logStmt->bindParam(':old_status', $currentStatus, PDO::PARAM_STR);
            $logStmt->bindParam(':new_status', $newStatus, PDO::PARAM_STR);
            $logStmt->bindParam(':changed_by', $_SESSION['user_id'], PDO::PARAM_INT);
            $logStmt->execute();
        } catch (PDOException $e) {
            // Log the error but don't fail the status update
            error_log("Failed to log order status change: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'order_id' => $orderId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating order status']);
}
?>