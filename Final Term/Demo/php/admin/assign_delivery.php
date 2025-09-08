<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';
require_once '../order_tracking.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if required fields are provided
if (!isset($_POST['order_id']) || !isset($_POST['delivery_person_id']) || !isset($_POST['estimated_delivery'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$orderId = intval($_POST['order_id']);
$deliveryPersonId = intval($_POST['delivery_person_id']);
$estimatedDelivery = $_POST['estimated_delivery'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : null;
$requestId = isset($_POST['request_id']) && !empty($_POST['request_id']) ? intval($_POST['request_id']) : null;
$userId = $_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if delivery already exists for this order
    $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $existingDelivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingDelivery) {
        // Update existing delivery assignment
        $stmt = $pdo->prepare("
            UPDATE deliveries 
            SET delivery_person_id = ?, 
                status = 'assigned', 
                assigned_at = NOW(), 
                estimated_delivery = ?, 
                delivery_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$deliveryPersonId, $estimatedDelivery, $notes, $existingDelivery['id']]);
        
        // Create reassignment tracking event
        $trackingDescription = "Order reassigned to a different delivery person";
        createOrderTrackingEvent($pdo, $orderId, 'reassigned', $trackingDescription, $userId);
        
        $message = "Delivery reassigned successfully";
    } else {
        // Create new delivery assignment
        $stmt = $pdo->prepare("
            INSERT INTO deliveries (order_id, delivery_person_id, status, assigned_at, estimated_delivery, delivery_notes)
            VALUES (?, ?, 'assigned', NOW(), ?, ?)
        ");
        $stmt->execute([$orderId, $deliveryPersonId, $estimatedDelivery, $notes]);
        
        // Create tracking event
        $trackingDescription = "Order assigned to delivery personnel";
        createOrderTrackingEvent($pdo, $orderId, 'assigned_to_delivery', $trackingDescription, $userId);
        
        // Update order status to processing if it's still pending
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['status'] === 'pending') {
            updateOrderStatus($pdo, $orderId, 'processing', "Order is being prepared for delivery", $userId);
        }
        
        $message = "Delivery assigned successfully";
    }
    
    // If this was from a delivery request, update its status
    if ($requestId) {
        $stmt = $pdo->prepare("UPDATE delivery_requests SET status = 'assigned', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Helper function to create a custom tracking event
 */
function createOrderTrackingEvent($pdo, $orderId, $status, $description, $userId) {
    try {
        $sql = "INSERT INTO order_tracking (order_id, status, description, created_by, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $status, $description, $userId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating tracking event: " . $e->getMessage());
        return false;
    }
}
?>
