<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Get shop ID for the logged-in shop owner
$shopId = getShopIdForOwner();
if (!$shopId) {
    echo json_encode(['error' => 'No shop found for this account']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get recent orders for this shop owner's products
    $stmt = $conn->prepare("
        SELECT DISTINCT o.id, o.created_at, o.status, o.total_amount,
        u.name as customer_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.shop_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$shopId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for frontend
    foreach ($orders as &$order) {
        $order['order_date'] = date('M j, Y', strtotime($order['created_at']));
        $order['status'] = ucfirst($order['status']);
    }
    
    // Return the orders data
    echo json_encode([
        'orders' => $orders
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error in get_recent_orders.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
