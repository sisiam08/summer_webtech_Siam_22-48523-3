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

// Get recent orders for this shop
$sql = "SELECT DISTINCT o.id, o.order_number, o.total_amount, o.status, o.created_at, u.name as customer_name 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        JOIN users u ON o.customer_id = u.id
        WHERE p.shop_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    // Format date
    $date = new DateTime($row['created_at']);
    $row['formatted_date'] = $date->format('M j, Y');
    
    // Format status to capitalize first letter
    $row['status_formatted'] = ucfirst($row['status']);
    
    $orders[] = $row;
}

// Return recent orders
echo json_encode(['orders' => $orders]);
?>
