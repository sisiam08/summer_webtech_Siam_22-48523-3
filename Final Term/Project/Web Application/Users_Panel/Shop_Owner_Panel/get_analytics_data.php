<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection and functions
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

try {
    $conn = connectDB();
    
    // Get shop ID for the current shop owner
    $stmt = $conn->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode(['error' => 'Shop not found']);
        exit();
    }
    
    $shop_id = $shop['id'];
    
    // Get time period from query parameter
    $period = $_GET['period'] ?? 'month';
    
    // Define date range based on period
    $date_condition = '';
    switch ($period) {
        case 'week':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'quarter':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Get total sales
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ? 
        AND o.status NOT IN ('cancelled', 'refunded')
        $date_condition
    ");
    $stmt->execute([$shop_id]);
    $totalSales = $stmt->fetchColumn();

    // Get total orders
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as total_orders
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ? 
        AND o.status NOT IN ('cancelled', 'refunded')
        $date_condition
    ");
    $stmt->execute([$shop_id]);
    $totalOrders = $stmt->fetchColumn();

    // Get total products sold
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(oi.quantity), 0) as products_sold
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_id = ? 
        AND o.status NOT IN ('cancelled', 'refunded')
        $date_condition
    ");
    $stmt->execute([$shop_id]);
    $productsSold = $stmt->fetchColumn();

    // Calculate average order value
    $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

    echo json_encode([
        'totalSales' => intval(ceil($totalSales)),
        'totalOrders' => intval($totalOrders),
        'productsSold' => intval($productsSold),
        'avgOrderValue' => intval(ceil($avgOrderValue)),
        'period' => $period
    ]);

} catch (PDOException $e) {
    error_log('Database error in get_analytics_data.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Error in get_analytics_data.php: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>