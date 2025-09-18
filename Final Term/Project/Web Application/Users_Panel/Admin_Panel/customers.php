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

// Get admin details
$conn = connectDB();
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminName = $admin['name'] ?? 'Admin';

// Handle customer actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'toggle_status') {
            // Enable/disable customer
            $customer_id = $_POST['customer_id'] ?? '';
            $is_active = $_POST['is_active'] ?? '';
            
            if (empty($customer_id) || $is_active === '') {
                $error_message = 'Customer ID and status are required.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'");
                $stmt->bindParam(1, $is_active, PDO::PARAM_INT);
                $stmt->bindParam(2, $customer_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $success_message = 'Customer status updated successfully.';
                } else {
                    $error_message = 'Error updating customer status.';
                }
            }
        } elseif ($_POST['action'] === 'delete_customer') {
            // Delete customer (soft delete)
            $customer_id = $_POST['customer_id'] ?? '';
            
            if (empty($customer_id)) {
                $error_message = 'Customer ID is required.';
            } else {
                // Check if customer has active orders (if orders table exists)
                $has_orders = false;
                try {
                    $check_table = $conn->query("SHOW TABLES LIKE 'orders'");
                    if ($check_table->rowCount() > 0) {
                        // Check which customer ID column exists
                        $check_columns = $conn->query("DESCRIBE orders");
                        $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
                        
                        $customer_id_column = 'customer_id';
                        if (in_array('user_id', $columns)) {
                            $customer_id_column = 'user_id';
                        }
                        
                        $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE $customer_id_column = ? AND status IN ('pending', 'processing', 'shipped')");
                        $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $has_orders = $result['order_count'] > 0;
                    }
                } catch (PDOException $e) {
                    // Orders table doesn't exist, safe to delete
                }
                
                if ($has_orders) {
                    $error_message = 'Cannot delete customer with active orders.';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET is_active = 0, email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()) WHERE id = ? AND role = 'customer'");
                    $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Customer deleted successfully.';
                    } else {
                        $error_message = 'Error deleting customer.';
                    }
                }
            }
        }
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Build customer query with filters
$where_conditions = ["u.role = 'customer'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "u.is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
}

$where_clause = implode(' AND ', $where_conditions);

// Determine sort order
$order_by = match($sort) {
    'oldest' => 'u.created_at ASC',
    'name_asc' => 'u.name ASC',
    'name_desc' => 'u.name DESC',
    'email_asc' => 'u.email ASC',
    'email_desc' => 'u.email DESC',
    default => 'u.created_at DESC'
};

// Get customers with order statistics (handling case where orders table might not exist)
$sql = "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
               0 as total_orders,
               0 as total_spent,
               NULL as last_order_date
        FROM users u
        WHERE $where_clause
        ORDER BY $order_by";

$stmt = $conn->prepare($sql);
foreach ($params as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Try to get order statistics if orders table exists
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
        
        // Update customers with real order statistics
        foreach ($customers as &$customer) {
            $order_sql = "SELECT 
                            COUNT(*) as total_orders,
                            COALESCE(SUM(total_amount), 0) as total_spent,
                            MAX(created_at) as last_order_date
                          FROM orders 
                          WHERE $customer_id_column = ?";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bindParam(1, $customer['id'], PDO::PARAM_INT);
            $order_stmt->execute();
            $order_data = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order_data) {
                $customer['total_orders'] = $order_data['total_orders'];
                $customer['total_spent'] = $order_data['total_spent'];
                $customer['last_order_date'] = $order_data['last_order_date'];
            }
        }
    }
} catch (PDOException $e) {
    // Orders table doesn't exist or has different structure, continue with default values
}

// Get customer statistics
$stats_sql = "SELECT 
                COUNT(*) as total_customers,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_customers,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_customers,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_customers_month
              FROM users 
              WHERE role = 'customer'";
$stmt = $conn->prepare($stats_sql);
$stmt->execute();
$customer_stats = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <title>Customer Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .customer-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        
        .stat-card.red {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .search-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .alert {
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-high {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .text-center {
            text-align: center;
        }
        
        .customer-name {
            font-weight: 600;
            color: #333;
        }
        
        .customer-email {
            color: #666;
            font-size: 0.9em;
        }
        
        .order-stats {
            font-size: 0.9em;
        }
        
        .total-spent {
            font-weight: 600;
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            <h2>Customer Management</h2>
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

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="material-icons">check_circle</i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="material-icons">error</i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Customer Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($customer_stats['total_customers']); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo number_format($customer_stats['active_customers']); ?></div>
                <div class="stat-label">Active Customers</div>
            </div>
            <div class="stat-card red">
                <div class="stat-number"><?php echo number_format($customer_stats['inactive_customers']); ?></div>
                <div class="stat-label">Inactive Customers</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number"><?php echo number_format($customer_stats['new_customers_month']); ?></div>
                <div class="stat-label">New This Month</div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="search">Search Customers</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, email, or phone">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="email_asc" <?php echo $sort === 'email_asc' ? 'selected' : ''; ?>>Email A-Z</option>
                            <option value="email_desc" <?php echo $sort === 'email_desc' ? 'selected' : ''; ?>>Email Z-A</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons" style="vertical-align: middle; font-size: 16px;">search</i>
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Customer List -->
        <div class="customer-container">
            <div style="padding: 20px;">
                <h3>Customers (<?php echo count($customers); ?> found)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer Info</th>
                            <th>Contact</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Last Order</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No customers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                        <div class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?></td>
                                    <td>
                                        <div class="order-stats">
                                            <?php echo number_format($customer['total_orders']); ?> orders
                                        </div>
                                    </td>
                                    <td>
                                        <div class="total-spent">৳<?php echo number_format($customer['total_spent'], 2); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_order_date']): ?>
                                            <?php echo date('M d, Y', strtotime($customer['last_order_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">No orders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <a href="customer_details.php?id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="material-icons" style="font-size: 16px;">visibility</i>
                                            </a>
                                            
                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $customer['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" 
                                                        class="btn btn-sm <?php echo $customer['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                                        onclick="return confirm('Are you sure you want to <?php echo $customer['is_active'] ? 'deactivate' : 'activate'; ?> this customer?')"
                                                        title="<?php echo $customer['is_active'] ? 'Deactivate' : 'Activate'; ?> Customer">
                                                    <i class="material-icons" style="font-size: 16px;">
                                                        <?php echo $customer['is_active'] ? 'block' : 'check_circle'; ?>
                                                    </i>
                                                </button>
                                            </form>
                                            
                                            <?php if ($customer['total_orders'] == 0): ?>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_customer">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')"
                                                            title="Delete Customer">
                                                        <i class="material-icons" style="font-size: 16px;">delete</i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>