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

// Get the logged-in shop owner's shop ID (same logic as get_orders.php)
$conn = connectDB();
$stmt = $conn->prepare("SELECT id FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'No shop found for this account']);
    exit();
}

$shopId = $shop['id'];

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
    exit();
}

$orderId = (int)$input['id'];
$newStatus = trim($input['status']);
$deliveryPersonId = isset($input['delivery_person_id']) ? (int)$input['delivery_person_id'] : null;

// If status is being set to Delivered, delivery person ID is required
if (strtolower($newStatus) === 'delivered' && !$deliveryPersonId) {
    echo json_encode(['success' => false, 'message' => 'Delivery person must be assigned when marking order as delivered']);
    exit();
}

// Validate status values (case insensitive)
$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array(strtolower($newStatus), $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // First, verify that the order belongs to the shop owner's shop (same logic as get_orders.php)
    $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND shop_id = ?");
    $stmt->execute([$orderId, $shopId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to your shop']);
        exit();
    }
    
    // Check if the status transition is valid (normalize to lowercase for comparison)
    $currentStatus = $order['status'];
    $currentStatusNormalized = strtolower($currentStatus);
    $newStatusNormalized = strtolower($newStatus);
    
    $validTransitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [], // Cannot change from delivered
        'cancelled' => []  // Cannot change from cancelled
    ];
    
    if (!isset($validTransitions[$currentStatusNormalized]) || !in_array($newStatusNormalized, $validTransitions[$currentStatusNormalized])) {
        echo json_encode(['success' => false, 'message' => "Cannot change status from {$currentStatus} to {$newStatus}"]);
        exit();
    }
    
    // Update the order status
    if (strtolower($newStatus) === 'delivered') {
        // When marking as delivered, also assign delivery person and set delivery time
        if ($deliveryPersonId) {
            $updateQuery = "UPDATE orders SET status = ?, delivery_person_id = ?, delivery_time = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$newStatus, $deliveryPersonId, $orderId]);
        } else {
            $updateQuery = "UPDATE orders SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$newStatus, $orderId]);
        }
    } else {
        // For other status changes, just update the status
        $updateQuery = "UPDATE orders SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateResult = $updateStmt->execute([$newStatus, $orderId]);
    }
    
    if ($updateResult) {
        // Log the status change for audit purposes (optional - skip if table doesn't exist)
        try {
            $logQuery = "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, changed_at) 
                         VALUES (?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->execute([$orderId, $currentStatus, $newStatus, $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Log the error but don't fail the status update
            error_log("Failed to log order status change: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'order_id' => $orderId,
            'delivery_person_id' => $deliveryPersonId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>