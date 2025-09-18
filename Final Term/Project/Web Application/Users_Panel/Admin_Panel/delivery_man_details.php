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

// Get delivery man ID from URL
$deliveryManId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($deliveryManId <= 0) {
    $_SESSION['error_message'] = "Invalid delivery man ID.";
    header("Location: delivery_men.php");
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

// Get delivery man details
$sql = "SELECT u.id as user_id, u.name, u.email, u.phone, u.created_at, u.is_active
        FROM users u 
        WHERE u.id = ? AND u.role = 'delivery_man'";
$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $deliveryManId, PDO::PARAM_INT);
$stmt->execute();
$deliveryMan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deliveryMan) {
    $_SESSION['error_message'] = "Delivery man not found.";
    header("Location: delivery_men.php");
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

// Get delivery statistics (placeholder data since orders table structure is uncertain)
$totalDeliveries = 0;
$completedDeliveries = 0;
$pendingDeliveries = 0;
$averageRating = 0;

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Man Details - Admin Dashboard</title>
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
            background: linear-gradient(135deg, #17a2b8, #117a8b);
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            color: #17a2b8;
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
        .rating {
            color: #ffc107;
            font-size: 18px;
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
            <a href="delivery_men.php" class="menu-item active">
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
            <h2>Delivery Man Details</h2>
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
            <a href="delivery_men.php" class="back-button">
                <i class="material-icons">arrow_back</i> Back to Delivery Men
            </a>
        </div>

        <div class="details-container">
            <div class="details-header">
                <h3><?php echo htmlspecialchars($deliveryMan['name']); ?></h3>
                <p>Delivery Man ID: #<?php echo $deliveryMan['user_id']; ?></p>
            </div>
            
            <div class="details-content">
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Personal Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($deliveryMan['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($deliveryMan['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($deliveryMan['phone'] ?: 'Not provided'); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge status-<?php echo $deliveryMan['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $deliveryMan['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <p><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($deliveryMan['created_at'])); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h4>Performance Rating</h4>
                        <p><strong>Average Rating:</strong></p>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="material-icons"><?php echo $i <= $averageRating ? 'star' : 'star_border'; ?></i>
                            <?php endfor; ?>
                            <span style="color: #666; font-size: 14px; margin-left: 10px;">
                                (<?php echo number_format($averageRating, 1); ?>/5.0)
                            </span>
                        </div>
                        <p style="margin-top: 15px;"><strong>Address:</strong> Not available</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?php echo $totalDeliveries; ?></div>
                        <div class="label">Total Deliveries</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $completedDeliveries; ?></div>
                        <div class="label">Completed Deliveries</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $pendingDeliveries; ?></div>
                        <div class="label">Pending Deliveries</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo $completedDeliveries > 0 ? round(($completedDeliveries / $totalDeliveries) * 100) : 0; ?>%</div>
                        <div class="label">Success Rate</div>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Recent Delivery History</h4>
                    <p>No delivery history available. This section will show recent deliveries, delivery routes, and performance metrics when the full order management system is implemented.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>