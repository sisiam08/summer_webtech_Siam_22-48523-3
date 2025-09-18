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

// Get admin details
$conn = connectDB();
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminName = $admin['name'] ?? 'Admin';

// Get pending shop owners (those without shops)
$sql = "SELECT u.id as user_id, u.name, u.email, u.phone, u.created_at 
        FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0
        ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active shop owners with shops
$sql = "SELECT s.id as shop_id, s.name as shop_name, s.description,
               u.id as user_id, u.name as owner_name, u.email, u.phone, u.created_at, u.is_active,
               0 as total_products
        FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND u.is_active = 1
        ORDER BY u.name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$activeShopOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $action = $_POST['action'];
    
    if ($action === 'approve_shop_owner' && $userId > 0) {
        // Get user details
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Activate the user first
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Create a shop for this owner
            $shopName = $user['name'] . "'s Shop";
            $description = "Shop managed by " . $user['name'];
            
            $stmt = $conn->prepare("INSERT INTO shops (name, owner_id, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bindParam(1, $shopName, PDO::PARAM_STR);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->bindParam(3, $description, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Shop owner approved successfully. Shop '{$shopName}' created.";
            } else {
                $_SESSION['error_message'] = "Error creating shop for approved owner.";
            }
        }
        
        header('Location: shop_owners.php');
        exit();
    }
    else if ($action === 'suspend_user' && $userId > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Shop owner suspended successfully.";
        } else {
            $_SESSION['error_message'] = "Error suspending shop owner.";
        }
        
        header('Location: shop_owners.php');
        exit();
    }
    else if ($action === 'activate_user' && $userId > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Shop owner activated successfully.";
        } else {
            $_SESSION['error_message'] = "Error activating shop owner.";
        }
        
        header('Location: shop_owners.php');
        exit();
    }
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owners Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .tab-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .tab-buttons {
            display: none; /* Hide tab buttons since we only have shop owners here */
        }
        .tab-content {
            padding: 20px;
        }
        .tab-content.active {
            display: block;
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
        .section {
            margin-bottom: 30px;
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
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        .btn-success {
            background-color: #28a745;
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
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .text-center {
            text-align: center;
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
            <h2>Shop Owners Management</h2>
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

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <div class="tab-container">
            <!-- Shop Owners Content -->
            <div class="tab-content active">
                <?php if (count($pendingShopOwners) > 0): ?>
                <div class="section">
                    <h3>Pending Shop Owner Approvals</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingShopOwners as $owner): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                <td><?php echo htmlspecialchars($owner['phone'] ?: 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($owner['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $owner['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve_shop_owner">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="section">
                    <h3>Active Shop Owners</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Shop Name</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Total Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeShopOwners)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No active shop owners found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($activeShopOwners as $shop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($shop['shop_name'] ?: 'No Shop'); ?></td>
                                    <td><?php echo htmlspecialchars($shop['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['email']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['phone'] ?: 'N/A'); ?></td>
                                    <td>N/A</td>
                                    <td><?php echo intval($shop['total_products']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $shop['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $shop['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($shop['is_active']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $shop['user_id']; ?>">
                                                <input type="hidden" name="action" value="suspend_user">
                                                <button type="submit" class="btn btn-sm btn-warning">Suspend</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $shop['user_id']; ?>">
                                                <input type="hidden" name="action" value="activate_user">
                                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="shop_details.php?id=<?php echo $shop['shop_id']; ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>