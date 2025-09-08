<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized access';
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Get number of days for the report
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_report_' . $days . '_days.csv');

// Create output handle
$output = fopen('php://output', 'w');

try {
    // Calculate the date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    // Add report header
    fputcsv($output, ['Sales Report', 'From: ' . $startDate, 'To: ' . $endDate]);
    fputcsv($output, []);
    
    // Add shop owner info
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$shop_owner_id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    fputcsv($output, ['Shop Owner:', $owner['first_name'] . ' ' . $owner['last_name']]);
    fputcsv($output, ['Email:', $owner['email']]);
    fputcsv($output, []);
    
    // Add sales summary
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
    
    fputcsv($output, ['SALES SUMMARY']);
    fputcsv($output, ['Total Sales:', '$' . number_format($totalSales, 2)]);
    fputcsv($output, ['Total Orders:', $totalOrders]);
    fputcsv($output, ['Average Order Value:', '$' . number_format($avgOrderValue, 2)]);
    fputcsv($output, []);
    
    // Add daily sales data
    fputcsv($output, ['DAILY SALES']);
    fputcsv($output, ['Date', 'Orders', 'Sales Amount']);
    
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
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            date('Y-m-d', strtotime($row['order_date'])),
            $row['order_count'],
            '$' . number_format($row['daily_sales'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Add top selling products
    fputcsv($output, ['TOP SELLING PRODUCTS']);
    fputcsv($output, ['Product ID', 'Name', 'Quantity Sold', 'Revenue']);
    
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
        LIMIT 10
    ");
    $stmt->execute([$shop_owner_id, $startDate, $endDate . ' 23:59:59']);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['quantity'],
            '$' . number_format($row['revenue'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Add order status distribution
    fputcsv($output, ['ORDER STATUS DISTRIBUTION']);
    fputcsv($output, ['Status', 'Count', 'Percentage']);
    
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
    
    $totalStatusCount = array_sum(array_column($statusData, 'count'));
    
    foreach ($statusData as $status) {
        $percentage = $totalStatusCount > 0 ? ($status['count'] / $totalStatusCount) * 100 : 0;
        fputcsv($output, [
            ucfirst($status['status']),
            $status['count'],
            number_format($percentage, 2) . '%'
        ]);
    }
    
    fputcsv($output, []);
    
    // Add low stock products
    fputcsv($output, ['LOW STOCK PRODUCTS']);
    fputcsv($output, ['Product ID', 'Name', 'Current Stock']);
    
    $stmt = $pdo->prepare("
        SELECT id, name, stock
        FROM products
        WHERE shop_owner_id = ? AND stock <= 10 AND stock > 0
        ORDER BY stock ASC
    ");
    $stmt->execute([$shop_owner_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['stock']
        ]);
    }
    
    fputcsv($output, []);
    
    // Add out of stock products
    fputcsv($output, ['OUT OF STOCK PRODUCTS']);
    fputcsv($output, ['Product ID', 'Name', 'Current Stock']);
    
    $stmt = $pdo->prepare("
        SELECT id, name, stock
        FROM products
        WHERE shop_owner_id = ? AND stock <= 0
        ORDER BY name
    ");
    $stmt->execute([$shop_owner_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['stock']
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['Report generated on:', date('Y-m-d H:i:s')]);
    
} catch (PDOException $e) {
    // Log the error
    error_log('Database error: ' . $e->getMessage());
    fputcsv($output, ['Error generating report']);
}

fclose($output);
?>
