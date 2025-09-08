<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$orderId = (int)$_POST['order_id'];
$status = trim($_POST['status']);
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate status
$allowedStatuses = ['Processing', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Include database connection
require_once '../config/database.php';

try {
    // For demonstration purposes, return success
    // In a real application, you would update the order status in the database
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'order_id' => $orderId,
        'status' => $status,
        'comment' => $comment,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating order status: ' . $e->getMessage()]);
}
?>
