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

// Get number of days for the report
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

try {
    // Calculate the date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    // Generate date labels for the chart (last N days)
    $dateLabels = [];
    $dateValues = [];
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dateLabels[] = date('M d', strtotime($date));
        $dateValues[$date] = ['sales' => 0, 'orders' => 0];
    }
    
    // Get sales and orders data for the chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.created_at) as order_date,
            COUNT(DISTINCT o.id) as order_count,
            SUM(oi.price * oi.quantity) as daily_sales
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ? AND o.created_at BETWEEN ? AND ?
        GROUP BY DATE(o.created_at)
        ORDER BY order_date
    ");
    $stmt->execute([$shop_owner_id, $startDate, $endDate . ' 23:59:59']);
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process sales data
    $salesChartData = [
        'labels' => $dateLabels,
        'sales' => [],
        'orders' => []
    ];
    
    foreach ($salesData as $data) {
        $date = date('Y-m-d', strtotime($data['order_date']));
        if (isset($dateValues[$date])) {
            $dateValues[$date]['sales'] = (float)$data['daily_sales'];
            $dateValues[$date]['orders'] = (int)$data['order_count'];
        }
    }
    
    // Extract the values for the chart
    foreach ($dateValues as $data) {
        $salesChartData['sales'][] = $data['sales'];
        $salesChartData['orders'][] = $data['orders'];
    }
    
    // Get sales summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(oi.price * oi.quantity) as total_sales
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ? AND o.created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$shop_owner_id, $startDate, $endDate . ' 23:59:59']);
    $salesSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSales = (float)$salesSummary['total_sales'];
    $totalOrders = (int)$salesSummary['total_orders'];
    $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
    
    // Get top selling products
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            SUM(oi.quantity) as quantity,
            SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.shop_owner_id = ? AND o.created_at BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY quantity DESC
        LIMIT 5
    ");
    $stmt->execute([$shop_owner_id, $startDate, $endDate . ' 23:59:59']);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order status distribution
    $stmt = $pdo->prepare("
        SELECT 
            o.status,
            COUNT(DISTINCT o.id) as count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.shop_owner_id = ? AND o.created_at BETWEEN ? AND ?
        GROUP BY o.status
    ");
    $stmt->execute([$shop_owner_id, $startDate, $endDate . ' 23:59:59']);
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $orderStatus = [];
    foreach ($statusData as $status) {
        $orderStatus[$status['status']] = (int)$status['count'];
    }
    
    // Get inventory summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN stock <= 10 AND stock > 0 THEN 1 END) as low_stock,
            COUNT(CASE WHEN stock <= 0 THEN 1 END) as out_of_stock
        FROM products
        WHERE shop_owner_id = ?
    ");
    $stmt->execute([$shop_owner_id]);
    $inventorySummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get low stock and out of stock products
    $stmt = $pdo->prepare("
        SELECT id, name, stock
        FROM products
        WHERE shop_owner_id = ? AND stock <= 10
        ORDER BY stock ASC
        LIMIT 10
    ");
    $stmt->execute([$shop_owner_id]);
    $inventoryStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return all the report data
    echo json_encode([
        'salesChart' => $salesChartData,
        'salesSummary' => [
            'totalSales' => $totalSales,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue
        ],
        'topProducts' => $topProducts,
        'orderStatus' => $orderStatus,
        'inventorySummary' => [
            'lowStockCount' => (int)$inventorySummary['low_stock'],
            'outOfStockCount' => (int)$inventorySummary['out_of_stock']
        ],
        'inventoryStatus' => $inventoryStatus
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
