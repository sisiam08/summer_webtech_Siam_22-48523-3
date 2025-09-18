<?php
// Start session
session_start();

// Force browser to clear cache for this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include functions
require_once __DIR__ . '/../../Includes/functions.php';
require_once __DIR__ . '/../../Database/database.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Not logged in or not an admin, redirect to login page
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get admin user data
$adminUser = getCurrentUser();
$adminName = $adminUser ? $adminUser['name'] : 'Admin';

// Get pending counts for badges and system statistics
$conn = connectDB();

// Get pending shop owners count
$sql = "SELECT COUNT(*) as count FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwnersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending delivery men count
$sql = "SELECT COUNT(*) as count FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get system statistics
$systemStats = [];

// User statistics
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$systemStats['total_users'] = $stmt->fetch()['total_users'];

$stmt = $conn->query("SELECT COUNT(*) as active_users FROM users WHERE is_active = 1");
$systemStats['active_users'] = $stmt->fetch()['active_users'];

$stmt = $conn->query("SELECT COUNT(*) as shop_owners FROM users WHERE role = 'shop_owner'");
$systemStats['shop_owners'] = $stmt->fetch()['shop_owners'];

$stmt = $conn->query("SELECT COUNT(*) as delivery_men FROM users WHERE role = 'delivery_man'");
$systemStats['delivery_men'] = $stmt->fetch()['delivery_men'];

$stmt = $conn->query("SELECT COUNT(*) as customers FROM users WHERE role = 'customer'");
$systemStats['customers'] = $stmt->fetch()['customers'];

// Check if other tables exist for additional stats
try {
    $stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
    $systemStats['total_orders'] = $stmt->fetch()['total_orders'];
    
    $stmt = $conn->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $systemStats['pending_orders'] = $stmt->fetch()['pending_orders'];
    
    $stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
    $systemStats['total_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;
} catch (PDOException $e) {
    $systemStats['total_orders'] = 0;
    $systemStats['pending_orders'] = 0;
    $systemStats['total_revenue'] = 0;
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
    $systemStats['total_products'] = $stmt->fetch()['total_products'];
    
    $stmt = $conn->query("SELECT COUNT(*) as active_products FROM products WHERE status = 'active'");
    $systemStats['active_products'] = $stmt->fetch()['active_products'];
} catch (PDOException $e) {
    $systemStats['total_products'] = 0;
    $systemStats['active_products'] = 0;
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total_shops FROM shops");
    $systemStats['total_shops'] = $stmt->fetch()['total_shops'];
    
    $stmt = $conn->query("SELECT COUNT(*) as active_shops FROM shops WHERE status = 'active'");
    $systemStats['active_shops'] = $stmt->fetch()['active_shops'];
} catch (PDOException $e) {
    $systemStats['total_shops'] = 0;
    $systemStats['active_shops'] = 0;
}

// Get recent activities for dashboard
$recentActivities = [];
try {
    $stmt = $conn->query("
        SELECT 'New User' as type, name as description, created_at as timestamp 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentActivities = array_merge($recentActivities, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Handle gracefully if tables don't exist
}

// Create arrays for compatibility with existing code
$pendingShopOwners = array_fill(0, $pendingShopOwnersCount, null);
$pendingDeliveryMen = array_fill(0, $pendingDeliveryMenCount, null);

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GroceryBD</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4caf50;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.primary {
            border-left-color: #2196F3;
        }
        
        .stat-card.success {
            border-left-color: #4CAF50;
        }
        
        .stat-card.warning {
            border-left-color: #FF9800;
        }
        
        .stat-card.danger {
            border-left-color: #F44336;
        }
        
        .stat-card.purple {
            border-left-color: #9C27B0;
        }
        
        .stat-card.teal {
            border-left-color: #009688;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, transparent 30%, rgba(76, 175, 80, 0.1));
            border-radius: 0 12px 0 50px;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .stat-icon.success { background: linear-gradient(135deg, #4CAF50, #388E3C); }
        .stat-icon.warning { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .stat-icon.danger { background: linear-gradient(135deg, #F44336, #D32F2F); }
        .stat-icon.purple { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
        .stat-icon.teal { background: linear-gradient(135deg, #009688, #00695C); }
        
        .stat-number {
            font-size: 2.2em;
            font-weight: 700;
            color: #333;
            margin: 10px 0 5px 0;
            line-height: 1;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.95em;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .stat-trend {
            font-size: 0.85em;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-trend.down {
            color: #F44336;
        }
        
        .stat-trend.neutral {
            color: #666;
        }
        
        .system-overview {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .overview-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .refresh-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
        }
        
        .refresh-btn:hover {
            background: #45a049;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .action-icon.blue { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .action-icon.green { background: linear-gradient(135deg, #4CAF50, #388E3C); }
        .action-icon.orange { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .action-icon.purple { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
        .action-icon.teal { background: linear-gradient(135deg, #009688, #00695C); }
        .action-icon.red { background: linear-gradient(135deg, #F44336, #D32F2F); }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .action-desc {
            font-size: 0.85em;
            color: #666;
            line-height: 1.4;
        }
        
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
        }
        
        .activity-time {
            font-size: 0.8em;
            color: #999;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-title {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .dashboard-row {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .overview-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
            <a href="admin_index.php" class="menu-item active">
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
            <a href="customers.php" class="menu-item">
                <i class="material-icons">person</i> Customers
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Dashboard</h2>
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

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h1>
                <p class="welcome-subtitle">Here's what's happening with your GroceryBD platform today.</p>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="system-overview">
            <div class="overview-header">
                <h2 class="overview-title">
                    <i class="material-icons">analytics</i>
                    System Statistics
                </h2>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="material-icons">refresh</i>
                    Refresh
                </button>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="material-icons">people</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-trend">
                        <i class="material-icons">trending_up</i>
                        <?php echo number_format($systemStats['active_users']); ?> active
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="material-icons">store</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['shop_owners']); ?></div>
                    <div class="stat-label">Shop Owners</div>
                    <div class="stat-trend">
                        <i class="material-icons">store_mall_directory</i>
                        <?php echo number_format($systemStats['total_shops']); ?> shops
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="material-icons">delivery_dining</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['delivery_men']); ?></div>
                    <div class="stat-label">Delivery Personnel</div>
                    <div class="stat-trend <?php echo $pendingDeliveryMenCount > 0 ? 'neutral' : ''; ?>">
                        <i class="material-icons"><?php echo $pendingDeliveryMenCount > 0 ? 'pending' : 'check_circle'; ?></i>
                        <?php echo $pendingDeliveryMenCount; ?> pending
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-icon purple">
                            <i class="material-icons">person</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['customers']); ?></div>
                    <div class="stat-label">Customers</div>
                    <div class="stat-trend">
                        <i class="material-icons">shopping_cart</i>
                        Active buyers
                    </div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-header">
                        <div class="stat-icon teal">
                            <i class="material-icons">shopping_cart</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-trend <?php echo $systemStats['pending_orders'] > 0 ? 'neutral' : ''; ?>">
                        <i class="material-icons">pending</i>
                        <?php echo number_format($systemStats['pending_orders']); ?> pending
                    </div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-icon danger">
                            <i class="material-icons">inventory</i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo number_format($systemStats['total_products']); ?></div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-trend">
                        <i class="material-icons">check_circle</i>
                        <?php echo number_format($systemStats['active_products']); ?> active
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="system-overview">
            <div class="overview-header">
                <h2 class="overview-title">
                    <i class="material-icons">flash_on</i>
                    Quick Actions
                </h2>
            </div>
            
            <div class="quick-actions">
                <a href="categories.php" class="action-card">
                    <div class="action-icon blue">
                        <i class="material-icons">category</i>
                    </div>
                    <div class="action-title">Manage Categories</div>
                    <div class="action-desc">Add, edit or organize product categories</div>
                </a>

                <a href="shop_owners.php" class="action-card">
                    <div class="action-icon green">
                        <i class="material-icons">store</i>
                    </div>
                    <div class="action-title">Shop Owners</div>
                    <div class="action-desc">Review and approve shop registrations</div>
                </a>

                <a href="delivery_men.php" class="action-card">
                    <div class="action-icon orange">
                        <i class="material-icons">delivery_dining</i>
                    </div>
                    <div class="action-title">Delivery Personnel</div>
                    <div class="action-desc">Manage delivery team members</div>
                </a>

                <a href="employees.php" class="action-card">
                    <div class="action-icon purple">
                        <i class="material-icons">people</i>
                    </div>
                    <div class="action-title">Employees</div>
                    <div class="action-desc">Manage internal company staff</div>
                </a>

                <a href="customers.php" class="action-card">
                    <div class="action-icon teal">
                        <i class="material-icons">person</i>
                    </div>
                    <div class="action-title">Customers</div>
                    <div class="action-desc">View customer profiles and analytics</div>
                </a>

                <a href="settings.php" class="action-card">
                    <div class="action-icon red">
                        <i class="material-icons">settings</i>
                    </div>
                    <div class="action-title">System Settings</div>
                    <div class="action-desc">Configure platform settings</div>
                </a>
            </div>
        </div>

        <!-- Dashboard Row: Revenue Stats & Recent Activity -->
        <div class="dashboard-row">
            <div class="system-overview">
                <div class="overview-header">
                    <h2 class="overview-title">
                        <i class="material-icons">account_balance_wallet</i>
                        Revenue Overview
                    </h2>
                </div>
                
                <div class="dashboard-grid">
                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <i class="material-icons">payments</i>
                            </div>
                        </div>
                        <div class="stat-number">৳<?php echo number_format($systemStats['total_revenue'], 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-trend">
                            <i class="material-icons">trending_up</i>
                            All-time earnings
                        </div>
                    </div>

                    <div class="stat-card primary">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <i class="material-icons">trending_up</i>
                            </div>
                        </div>
                        <div class="stat-number"><?php echo number_format($systemStats['active_shops']); ?></div>
                        <div class="stat-label">Active Shops</div>
                        <div class="stat-trend">
                            <i class="material-icons">business</i>
                            Currently operating
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="overview-header">
                    <h2 class="overview-title">
                        <i class="material-icons">notifications</i>
                        Recent Activity
                    </h2>
                </div>
                
                <?php if (!empty($recentActivities)): ?>
                    <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="material-icons">person_add</i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?> joined</div>
                                <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="material-icons">info</i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">No recent activity</div>
                            <div class="activity-time">Check back later for updates</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
