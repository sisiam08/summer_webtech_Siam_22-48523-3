<?php
// Start session if not already started
session_start();

// Include database connection and functions
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Include vendor functions if file exists
$vendorFunctionsPath = __DIR__ . '/../../Includes/vendor_functions.php';
if (file_exists($vendorFunctionsPath)) {
    require_once $vendorFunctionsPath;
}

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
$pendingOrders = 0;
$totalProducts = 0;
$lowStock = 0;
$totalUsers = 0;
$newUsers = 0;
$totalRevenue = 0;
$monthlyRevenue = 0;

// Get total orders
$ordersSql = "SELECT COUNT(*) as count FROM orders";
$ordersResult = fetchOne($ordersSql);
$totalOrders = $ordersResult ? $ordersResult['count'] : 0;

// Get pending orders
$pendingOrdersSql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$pendingOrdersResult = fetchOne($pendingOrdersSql);
$pendingOrders = $pendingOrdersResult ? $pendingOrdersResult['count'] : 0;

// Get total products
$productsSql = "SELECT COUNT(*) as count FROM products";
$productsResult = fetchOne($productsSql);
$totalProducts = $productsResult ? $productsResult['count'] : 0;

// Get low stock products
$lowStockSql = "SELECT COUNT(*) as count FROM products WHERE stock < 10 AND stock > 0";
$lowStockResult = fetchOne($lowStockSql);
$lowStock = $lowStockResult ? $lowStockResult['count'] : 0;

// Get total users
$usersSql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$usersResult = fetchOne($usersSql);
$totalUsers = $usersResult ? $usersResult['count'] : 0;

// Get new users this month
$newUsersSql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
$newUsersResult = fetchOne($newUsersSql);
$newUsers = $newUsersResult ? $newUsersResult['count'] : 0;

// Get total revenue
$revenueSql = "SELECT SUM(total) as total FROM orders WHERE status != 'cancelled'";
$revenueResult = fetchOne($revenueSql);
$totalRevenue = $revenueResult ? $revenueResult['total'] : 0;

// Get monthly revenue
$monthlyRevenueSql = "SELECT SUM(total) as total FROM orders WHERE status != 'cancelled' AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
$monthlyRevenueResult = fetchOne($monthlyRevenueSql);
$monthlyRevenue = $monthlyRevenueResult ? $monthlyRevenueResult['total'] : 0;

// Get recent orders
$recentOrdersSql = "
    SELECT o.id, o.total as amount, o.status, o.created_at as date, u.name as customer
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
";
$recentOrdersResult = fetchAll($recentOrdersSql);
$recentOrders = [];

if ($recentOrdersResult) {
    foreach ($recentOrdersResult as $order) {
        // Format date
        $order['date'] = date('M d, Y', strtotime($order['date']));
        
        // Format amount
        $order['amount'] = number_format($order['amount'], 2);
        
        // Capitalize status
        $order['status'] = ucfirst($order['status']);
        
        $recentOrders[] = $order;
    }
}

// Get top selling products
$topProductsSql = "
    SELECT p.id, p.name, p.price, c.name as category,
           COUNT(oi.id) as sold,
           SUM(oi.price * oi.quantity) as revenue
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
";
$topProductsResult = fetchAll($topProductsSql);
$topProducts = [];

if ($topProductsResult) {
    foreach ($topProductsResult as $product) {
        // Format price and revenue
        $product['price'] = number_format($product['price'], 2);
        $product['revenue'] = number_format($product['revenue'], 2);
        
        $topProducts[] = $product;
    }
}

// Get vendor statistics
$vendorStats = [
    'total' => 0,
    'active' => 0,
    'pending' => 0
];

// Get total shops
$shopsSql = "SELECT COUNT(*) as count FROM shops";
$shopsResult = fetchOne($shopsSql);
$vendorStats['total'] = $shopsResult ? $shopsResult['count'] : 0;

// Get active shops
$activeVendorsSql = "SELECT COUNT(*) as count FROM shops WHERE status = 'active'";
$activeVendorsResult = fetchOne($activeVendorsSql);
$vendorStats['active'] = $activeVendorsResult ? $activeVendorsResult['count'] : 0;

// Get pending shops
$pendingVendorsSql = "SELECT COUNT(*) as count FROM shops WHERE status = 'pending'";
$pendingVendorsResult = fetchOne($pendingVendorsSql);
$vendorStats['pending'] = $pendingVendorsResult ? $pendingVendorsResult['count'] : 0;

// Return dashboard summary
echo json_encode([
    'orders' => [
        'total' => (int)$totalOrders,
        'pending' => (int)$pendingOrders
    ],
    'products' => [
        'total' => (int)$totalProducts,
        'lowStock' => (int)$lowStock
    ],
    'users' => [
        'total' => (int)$totalUsers,
        'new' => (int)$newUsers
    ],
    'revenue' => [
        'total' => number_format((float)$totalRevenue, 2),
        'monthly' => number_format((float)$monthlyRevenue, 2)
    ],
    'recentOrders' => $recentOrders,
    'topProducts' => $topProducts,
    'vendorStats' => $vendorStats
]);
?>
