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
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo json_encode(['error' => 'Order not found or you do not have permission to update it']);
        exit;
    }
    
    // Update the order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    
    // Add record to order history
    $stmt = $pdo->prepare("
        INSERT INTO order_history (order_id, status, updated_by, user_role)
        VALUES (?, ?, ?, 'shop_owner')
    ");
    $stmt->execute([$order_id, $status, $shop_owner_id]);
    
    // Return success message
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
