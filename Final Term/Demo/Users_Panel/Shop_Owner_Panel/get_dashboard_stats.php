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
    
    // Get total number of products for this shop
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE shop_id = ?");
    $stmt->execute([$shopId]);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total number of orders containing products from this shop
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ?
    ");
    $stmt->execute([$shopId]);
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total revenue from this shop's products
    $stmt = $conn->prepare("
        SELECT SUM(oi.price * oi.quantity) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ?
    ");
    $stmt->execute([$shopId]);
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get pending orders containing products from this shop
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ? AND o.status = 'pending'
    ");
    $stmt->execute([$shopId]);
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Return the statistics
    echo json_encode([
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'totalRevenue' => number_format($totalRevenue, 2),
        'pendingOrders' => $pendingOrders
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error in get_dashboard_stats.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ? AND o.status IN ('pending', 'processing')
    ");
    $stmt->execute([$shop_owner_id]);
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Return the dashboard data
    echo json_encode([
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'pendingOrders' => $pendingOrders
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
