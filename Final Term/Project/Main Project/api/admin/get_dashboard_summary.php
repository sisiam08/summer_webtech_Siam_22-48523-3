<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Get dashboard summary data
$totalOrders = 0;
$totalProducts = 0;
$totalUsers = 0;
$totalRevenue = 0;

// Get total orders
$ordersSql = "SELECT COUNT(*) as count FROM orders";
$ordersResult = fetchOne($ordersSql);
$totalOrders = $ordersResult ? $ordersResult['count'] : 0;

// Get total products
$productsSql = "SELECT COUNT(*) as count FROM products";
$productsResult = fetchOne($productsSql);
$totalProducts = $productsResult ? $productsResult['count'] : 0;

// Get total users
$usersSql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$usersResult = fetchOne($usersSql);
$totalUsers = $usersResult ? $usersResult['count'] : 0;

// Get total revenue
$revenueSql = "SELECT SUM(total) as total FROM orders WHERE status != 'Cancelled'";
$revenueResult = fetchOne($revenueSql);
$totalRevenue = $revenueResult ? $revenueResult['total'] : 0;

// Return dashboard summary
echo json_encode([
    'total_orders' => $totalOrders,
    'total_products' => $totalProducts,
    'total_users' => $totalUsers,
    'total_revenue' => $totalRevenue
]);
?>
