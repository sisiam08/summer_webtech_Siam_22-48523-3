<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database for pending counts
require_once __DIR__ . '/../../Database/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get customer ID
$customer_id = $_GET['id'] ?? '';
if (empty($customer_id) || !is_numeric($customer_id)) {
    header("Location: customers.php");
    exit;
}

// Get admin details
$conn = connectDB();
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminName = $admin['name'] ?? 'Admin';

// Get customer details
$stmt = $conn->prepare("SELECT id, name, email, phone, is_active, created_at FROM users WHERE id = ? AND role = 'customer'");
$stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Get customer order statistics (handle missing orders table)
$order_stats = [
    'total_orders' => 0,
    'total_spent' => 0,
    'avg_order_value' => 0,
    'last_order_date' => null,
    'first_order_date' => null
];

$recent_orders = [];
$status_distribution = [];
$favorite_categories = [];

try {
    // Check if orders table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($check_table->rowCount() > 0) {
        // Check columns in orders table
        $check_columns = $conn->query("DESCRIBE orders");
        $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine the correct customer ID column name
        $customer_id_column = 'customer_id';
        if (in_array('user_id', $columns)) {
            $customer_id_column = 'user_id';
        }
        
        // Get customer order statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_spent,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                MAX(created_at) as last_order_date,
                MIN(created_at) as first_order_date
            FROM orders 
            WHERE $customer_id_column = ?
        ");
        $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $order_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $order_stats;

        // Get recent orders
        $stmt = $conn->prepare("
            SELECT id, total_amount, status, created_at 
            FROM orders 
            WHERE $customer_id_column = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get order status distribution
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count 
            FROM orders 
            WHERE $customer_id_column = ? 
            GROUP BY status
        ");
        $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get favorite categories (if products and categories tables exist)
        try {
            $check_products = $conn->query("SHOW TABLES LIKE 'products'");
            $check_categories = $conn->query("SHOW TABLES LIKE 'categories'");
            $check_order_items = $conn->query("SHOW TABLES LIKE 'order_items'");
            
            if ($check_products->rowCount() > 0 && $check_categories->rowCount() > 0 && $check_order_items->rowCount() > 0) {
                $stmt = $conn->prepare("
                    SELECT c.name as category_name, COUNT(*) as order_count
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    WHERE o.$customer_id_column = ?
                    GROUP BY c.id, c.name
                    ORDER BY order_count DESC
                    LIMIT 5
                ");
                $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
                $stmt->execute();
                $favorite_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Related tables don't exist, continue without favorite categories
        }
    }
} catch (PDOException $e) {
    // Orders table doesn't exist, continue with default values
}

// Get pending counts for badges
$sql = "SELECT COUNT(*) as count FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwnersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$sql = "SELECT COUNT(*) as count FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .details-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .details-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .customer-name {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .customer-email {
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .customer-phone {
            opacity: 0.8;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-align: center;
        }
        
        .status-active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .status-inactive {
            background-color: rgba(244, 67, 54, 0.2);
            color: #ffcdd2;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #4caf50;
        }
        
        .stat-card.blue {
            border-left-color: #2196f3;
        }
        
        .stat-card.orange {
            border-left-color: #ff9800;
        }
        
        .stat-card.purple {
            border-left-color: #9c27b0;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .section-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-table th,
        .order-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .order-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .badge-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        
        .category-list {
            list-style: none;
            padding: 0;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-count {
            color: #666;
            font-size: 0.9em;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .customer-info {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body class="admin">
    <div class="admin-sidebar">
        <div class="brand">
            Grocery Admin
        </div>
        <div class="menu">
            <a href="admin_index.php" class="menu-item">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="banner_management.php" class="menu-item">
                <i class="material-icons">view_carousel</i> Banner Management
            </a>
            <a href="shop_owners.php" class="menu-item">
                <i class="material-icons">store</i> Shop Owners
                <?php if ($pendingShopOwnersCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingShopOwnersCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="delivery_men.php" class="menu-item">
                <i class="material-icons">delivery_dining</i> Delivery Men
                <?php if ($pendingDeliveryMenCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingDeliveryMenCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="employees.php" class="menu-item">
                <i class="material-icons">people</i> Employees
            </a>
            <a href="customers.php" class="menu-item active">
                <i class="material-icons">person</i> Customers
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Customer Details</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="admin_profile.php">Profile</a>
                        <a href="../../Authentication/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <a href="customers.php" class="back-btn">
            <i class="material-icons">arrow_back</i>
            Back to Customers
        </a>

        <!-- Customer Info Header -->
        <div class="details-container">
            <div class="details-header">
                <div class="customer-info">
                    <div>
                        <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                        <div class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                        <div class="customer-phone"><?php echo htmlspecialchars($customer['phone'] ?? 'No phone provided'); ?></div>
                    </div>
                    <div>
                        <div class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $customer['is_active'] ? 'Active Customer' : 'Inactive Customer'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($order_stats['total_orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-number">৳<?php echo number_format($order_stats['total_spent'], 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number">৳<?php echo number_format($order_stats['avg_order_value'], 2); ?></div>
                <div class="stat-label">Average Order Value</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-number">
                    <?php 
                    if ($order_stats['first_order_date']) {
                        $days = (time() - strtotime($order_stats['first_order_date'])) / (60 * 60 * 24);
                        echo number_format($days);
                    } else {
                        echo '0';
                    }
                    ?>
                </div>
                <div class="stat-label">Days as Customer</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Recent Orders -->
            <div class="details-container">
                <div style="padding: 20px;">
                    <h3 class="section-title">Recent Orders</h3>
                    <?php if (empty($recent_orders)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No orders found for this customer.</p>
                    <?php else: ?>
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>৳<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Customer Insights -->
            <div class="details-container">
                <div style="padding: 20px;">
                    <h3 class="section-title">Customer Insights</h3>
                    
                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 10px; color: #333;">Account Information</h4>
                        <div style="color: #666; line-height: 1.6;">
                            <div><strong>Customer Since:</strong> <?php echo date('M d, Y', strtotime($customer['created_at'])); ?></div>
                            <div><strong>Customer ID:</strong> #<?php echo $customer['id']; ?></div>
                            <?php if ($order_stats['last_order_date']): ?>
                                <div><strong>Last Order:</strong> <?php echo date('M d, Y', strtotime($order_stats['last_order_date'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($favorite_categories)): ?>
                        <div style="margin-bottom: 25px;">
                            <h4 style="margin-bottom: 10px; color: #333;">Favorite Categories</h4>
                            <ul class="category-list">
                                <?php foreach ($favorite_categories as $category): ?>
                                    <li class="category-item">
                                        <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        <span class="category-count"><?php echo $category['order_count']; ?> orders</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($status_distribution)): ?>
                        <div>
                            <h4 style="margin-bottom: 10px; color: #333;">Order Status Distribution</h4>
                            <ul class="category-list">
                                <?php foreach ($status_distribution as $status): ?>
                                    <li class="category-item">
                                        <span class="category-name"><?php echo ucfirst($status['status']); ?></span>
                                        <span class="category-count"><?php echo $status['count']; ?> orders</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>