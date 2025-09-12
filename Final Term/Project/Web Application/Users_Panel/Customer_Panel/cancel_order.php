<?php
// Cancel an order
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Get customer ID
$customer_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || empty($data['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = intval($data['order_id']);

try {
    // First, check if the order belongs to the logged-in customer and is in 'pending' status
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = :order_id AND customer_id = :customer_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found or you do not have permission to cancel it']);
        exit;
    }
    
    if ($order['status'] !== 'pending') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Only pending orders can be cancelled']);
        exit;
    }
    
    // Update order status to 'cancelled'
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Order has been cancelled successfully']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
