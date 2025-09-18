<?php
session_start();
require_once __DIR__ . '/../../Database/database.php';

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../Authentication/login.html");
    exit();
}

// Get shop ID from URL
$shopId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($shopId <= 0) {
    $_SESSION['error_message'] = "Invalid shop ID.";
    header("Location: shop_owners.php");
    exit();
}

// Get admin details
$conn = connectDB();
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminName = $admin['name'] ?? 'Admin';

// Get shop details with owner information
$sql = "SELECT s.id as shop_id, s.name as shop_name, s.description, s.created_at as shop_created,
               u.id as user_id, u.name as owner_name, u.email, u.phone, u.created_at as owner_created, u.is_active
        FROM shops s 
        JOIN users u ON s.owner_id = u.id 
        WHERE s.id = ? AND u.role = 'shop_owner'";
$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $shopId, PDO::PARAM_INT);
$stmt->execute();
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    $_SESSION['error_message'] = "Shop not found.";
    header("Location: shop_owners.php");
    exit();
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

// Get shop statistics (placeholder data since products table structure is uncertain)
$totalProducts = 0;
$totalOrders = 0;
$totalRevenue = 0;

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Details - Admin Dashboard</title>
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
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
            padding: 20px;
        }
        .details-header h3 {
            margin: 0;
            font-size: 24px;
        }
        .details-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .details-content {
            padding: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
        }
        .info-card h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .info-card p {
            margin: 5px 0;
            color: #666;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #4caf50;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        .back-button {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .back-button:hover {
            background: #5a6268;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
            <a href="shop_owners.php" class="menu-item active">
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
            <h2>Shop Details</h2>
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

        <div style="margin-bottom: 20px;">
            <a href="shop_owners.php" class="back-button">
                <i class="material-icons">arrow_back</i> Back to Shop Owners
            </a>
        </div>

        <div class="details-container">
            <div class="details-header">
                <h3><?php echo htmlspecialchars($shop['shop_name']); ?></h3>
                <p>Shop ID: #<?php echo $shop['shop_id']; ?></p>
            </div>
            
            <div class="details-content">
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Shop Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($shop['description'] ?: 'No description available'); ?></p>
                        <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($shop['shop_created'])); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h4>Owner Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($shop['owner_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($shop['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($shop['phone'] ?: 'Not provided'); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge status-<?php echo $shop['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $shop['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <p><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($shop['owner_created'])); ?></p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?php echo $totalProducts; ?></div>
                        <div class="label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $totalOrders; ?></div>
                        <div class="label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">$<?php echo number_format($totalRevenue, 2); ?></div>
                        <div class="label">Total Revenue</div>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Recent Activity</h4>
                    <p>No recent activity data available. This section will show shop performance metrics, recent orders, and product updates when the full system is implemented.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>