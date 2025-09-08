<?php
// Start session
session_start();

// Include necessary files
require_once '../php/db_connection.php';
require_once '../php/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Not logged in or not an admin, redirect to login page
    header('Location: login.php');
    exit;
}

// Get admin user data
$adminUser = getCurrentUser();
$adminName = $adminUser ? $adminUser['name'] : 'Admin';

// Get pending shop owners without shops
$sql = "SELECT u.id as user_id, u.name, u.email, u.phone, u.created_at 
        FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL 
        ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all active shop owners with shops
$sql = "SELECT s.id as shop_id, s.name as shop_name, s.description, 
               u.id as user_id, u.name as owner_name, u.email, u.phone, u.created_at, u.is_active
        FROM shops s 
        JOIN users u ON s.owner_id = u.id 
        WHERE u.role = 'shop_owner' 
        ORDER BY s.name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$activeShopOwners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pending delivery man registrations
$sql = "SELECT u.id as user_id, u.name, u.email, u.phone, u.created_at 
        FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0 
        ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get active delivery men
$sql = "SELECT u.id as user_id, u.name, u.email, u.phone, u.created_at, u.is_active 
        FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 1 
        ORDER BY u.name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$activeDeliveryMen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process shop owner approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $action = $_POST['action'];
    
    if ($action === 'approve_shop_owner' && $userId > 0) {
        // Get user details
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        // Create a shop for this owner
        $shopName = $user['name'] . "'s Shop";
        $description = "Shop managed by " . $user['name'];
        
        $stmt = $conn->prepare("INSERT INTO shops (name, owner_id, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sis", $shopName, $userId, $description);
        
        if ($stmt->execute()) {
            $shopId = $conn->insert_id;
            
            // Set success message
            $_SESSION['success_message'] = "Shop owner approved successfully. Shop '{$shopName}' created.";
        } else {
            $_SESSION['error_message'] = "Error approving shop owner: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header('Location: vendors.php');
        exit;
    }
    else if ($action === 'approve_delivery_man' && $userId > 0) {
        // Activate delivery man
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND role = 'delivery_man'");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Delivery man approved successfully.";
        } else {
            $_SESSION['error_message'] = "Error approving delivery man: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header('Location: vendors.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        .tab-button.active {
            border-bottom: 3px solid #4caf50;
            color: #4caf50;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .pending-badge {
            background-color: #ff9800;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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
    </style>
</head>
<body class="admin">
    <div class="admin-sidebar">
        <div class="brand">
            Grocery Admin
        </div>
        <div class="menu">
            <a href="index.php" class="menu-item">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> Orders
            </a>
            <a href="vendors.php" class="menu-item active">
                <i class="material-icons">store</i> Vendors
                <?php if (count($pendingShopOwners) > 0 || count($pendingDeliveryMen) > 0): ?>
                <span class="pending-badge"><?php echo count($pendingShopOwners) + count($pendingDeliveryMen); ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Vendor Management</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="../php/logout.php">Logout</a>
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
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="shop-owners">
                    Shop Owners
                    <?php if (count($pendingShopOwners) > 0): ?>
                    <span class="pending-badge"><?php echo count($pendingShopOwners); ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-button" data-tab="delivery-men">
                    Delivery Men
                    <?php if (count($pendingDeliveryMen) > 0): ?>
                    <span class="pending-badge"><?php echo count($pendingDeliveryMen); ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Shop Owners Tab -->
            <div id="shop-owners" class="tab-content active">
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
                                <td><?php echo htmlspecialchars($owner['phone']); ?></td>
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
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeShopOwners)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No active shop owners found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($activeShopOwners as $shop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['email']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['phone']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $shop['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $shop['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="shop_details.php?id=<?php echo $shop['shop_id']; ?>" class="btn btn-sm btn-info">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delivery Men Tab -->
            <div id="delivery-men" class="tab-content">
                <?php if (count($pendingDeliveryMen) > 0): ?>
                <div class="section">
                    <h3>Pending Delivery Men Approvals</h3>
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
                            <?php foreach ($pendingDeliveryMen as $deliveryMan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($deliveryMan['name']); ?></td>
                                <td><?php echo htmlspecialchars($deliveryMan['email']); ?></td>
                                <td><?php echo htmlspecialchars($deliveryMan['phone']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($deliveryMan['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $deliveryMan['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve_delivery_man">
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
                    <h3>Active Delivery Men</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeDeliveryMen)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No active delivery men found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($activeDeliveryMen as $deliveryMan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($deliveryMan['name']); ?></td>
                                    <td><?php echo htmlspecialchars($deliveryMan['email']); ?></td>
                                    <td><?php echo htmlspecialchars($deliveryMan['phone']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $deliveryMan['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $deliveryMan['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="delivery_man_details.php?id=<?php echo $deliveryMan['user_id']; ?>" class="btn btn-sm btn-info">
                                            View Details
                                        </a>
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

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vendors - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin">
    <div class="admin-sidebar">
        <div class="brand">
            Grocery Admin
        </div>
        <div class="menu">
            <a href="index.php" class="menu-item">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="vendors.php" class="menu-item active">
                <i class="material-icons">store</i> Vendors
            </a>
            <a href="employees.php" class="menu-item">
                <i class="material-icons">people</i> Employees
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> Orders
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
            <h2>Manage Vendors</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="../php/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-actions">
            <button class="btn btn-primary" id="add-vendor-btn">
                <i class="material-icons">add</i> Add Vendor
            </button>
            <div class="search-bar">
                <input type="text" id="vendor-search" placeholder="Search vendors...">
                <i class="material-icons">search</i>
            </div>
        </div>

        <div class="content-wrapper">
            <table class="data-table" id="vendors-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Shop Name</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendors)): ?>
                        <tr>
                            <td colspan="9" class="no-data">No vendors found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><?php echo $vendor['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($vendor['logo'] ?: '../assets/images/shop-placeholder.png'); ?>" alt="<?php echo htmlspecialchars($vendor['shop_name']); ?>" class="vendor-logo">
                                </td>
                                <td><?php echo htmlspecialchars($vendor['shop_name']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['owner_name']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['phone']); ?></td>
                                <td><?php echo $vendor['product_count']; ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($vendor['status']); ?>">
                                        <?php echo ucfirst($vendor['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="icon-btn view-btn" data-id="<?php echo $vendor['id']; ?>">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                    <button class="icon-btn edit-btn" data-id="<?php echo $vendor['id']; ?>">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="icon-btn status-btn <?php echo $vendor['status'] === 'active' ? 'suspend' : 'activate'; ?>" 
                                            data-id="<?php echo $vendor['id']; ?>"
                                            data-action="<?php echo $vendor['status'] === 'active' ? 'suspend' : 'activate'; ?>">
                                        <i class="material-icons"><?php echo $vendor['status'] === 'active' ? 'block' : 'check_circle'; ?></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Vendor Modal -->
    <div id="vendor-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add Vendor</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="vendor-form">
                    <input type="hidden" id="vendor-id" name="id">
                    
                    <div class="form-group">
                        <label for="shop-name">Shop Name</label>
                        <input type="text" id="shop-name" name="shop_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="owner-name">Owner Name</label>
                        <input type="text" id="owner-name" name="owner_name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="logo">Logo URL</label>
                            <input type="text" id="logo" name="logo">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Shop Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-secondary" id="cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vendor Details Modal -->
    <div id="vendor-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Vendor Details</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="vendor-details">
                    <div class="vendor-profile">
                        <img id="detail-logo" src="" alt="Shop Logo" class="vendor-detail-logo">
                        <h2 id="detail-shop-name"></h2>
                        <p class="status-badge"><span id="detail-status" class="status"></span></p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Contact Information</h4>
                        <table class="detail-table">
                            <tr>
                                <td><strong>Owner:</strong></td>
                                <td id="detail-owner"></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td id="detail-email"></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td id="detail-phone"></td>
                            </tr>
                            <tr>
                                <td><strong>Address:</strong></td>
                                <td id="detail-address"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Shop Information</h4>
                        <p id="detail-description"></p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Statistics</h4>
                        <table class="detail-table">
                            <tr>
                                <td><strong>Total Products:</strong></td>
                                <td id="detail-products"></td>
                            </tr>
                            <tr>
                                <td><strong>Total Orders:</strong></td>
                                <td id="detail-orders"></td>
                            </tr>
                            <tr>
                                <td><strong>Joined Date:</strong></td>
                                <td id="detail-joined"></td>
                            </tr>
                            <tr>
                                <td><strong>Last Update:</strong></td>
                                <td id="detail-updated"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="form-group text-right">
                        <a id="view-products-link" href="#" class="btn btn-secondary">View Products</a>
                        <a id="view-orders-link" href="#" class="btn btn-secondary">View Orders</a>
                        <button type="button" class="btn btn-primary" id="detail-close-btn">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirm-title">Confirm Action</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirm-message">Are you sure you want to perform this action?</p>
                <div class="form-group text-right">
                    <button type="button" class="btn btn-secondary" id="cancel-action-btn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-action-btn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const vendorModal = document.getElementById('vendor-modal');
            const vendorDetailsModal = document.getElementById('vendor-details-modal');
            const confirmModal = document.getElementById('confirm-modal');
            const modalTitle = document.getElementById('modal-title');
            const confirmTitle = document.getElementById('confirm-title');
            const confirmMessage = document.getElementById('confirm-message');
            
            // Forms
            const vendorForm = document.getElementById('vendor-form');
            
            // Buttons
            const addVendorBtn = document.getElementById('add-vendor-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const detailCloseBtn = document.getElementById('detail-close-btn');
            const closeButtons = document.querySelectorAll('.close');
            const cancelActionBtn = document.getElementById('cancel-action-btn');
            const confirmActionBtn = document.getElementById('confirm-action-btn');
            
            // Table buttons
            const viewButtons = document.querySelectorAll('.view-btn');
            const editButtons = document.querySelectorAll('.edit-btn');
            const statusButtons = document.querySelectorAll('.status-btn');
            
            // Search field
            const vendorSearch = document.getElementById('vendor-search');
            
            // Variables
            let currentVendorId = null;
            let currentAction = null;
            
            // Show add vendor modal
            addVendorBtn.addEventListener('click', function() {
                modalTitle.textContent = 'Add Vendor';
                resetForm();
                vendorModal.style.display = 'block';
            });
            
            // Show edit vendor modal
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    modalTitle.textContent = 'Edit Vendor';
                    loadVendorData(id);
                    vendorModal.style.display = 'block';
                });
            });
            
            // Show vendor details modal
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    loadVendorDetails(id);
                    vendorDetailsModal.style.display = 'block';
                });
            });
            
            // Show status confirmation modal
            statusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentVendorId = this.getAttribute('data-id');
                    currentAction = this.getAttribute('data-action');
                    
                    if (currentAction === 'suspend') {
                        confirmTitle.textContent = 'Suspend Vendor';
                        confirmMessage.textContent = 'Are you sure you want to suspend this vendor? Their products will no longer be visible to customers.';
                        confirmActionBtn.textContent = 'Suspend';
                    } else {
                        confirmTitle.textContent = 'Activate Vendor';
                        confirmMessage.textContent = 'Are you sure you want to activate this vendor? Their products will be visible to customers.';
                        confirmActionBtn.textContent = 'Activate';
                    }
                    
                    confirmModal.style.display = 'block';
                });
            });
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    vendorModal.style.display = 'none';
                    vendorDetailsModal.style.display = 'none';
                    confirmModal.style.display = 'none';
                });
            });
            
            // Cancel buttons
            cancelBtn.addEventListener('click', function() {
                vendorModal.style.display = 'none';
            });
            
            detailCloseBtn.addEventListener('click', function() {
                vendorDetailsModal.style.display = 'none';
            });
            
            cancelActionBtn.addEventListener('click', function() {
                confirmModal.style.display = 'none';
            });
            
            // Confirm action button
            confirmActionBtn.addEventListener('click', function() {
                if (currentVendorId && currentAction) {
                    updateVendorStatus(currentVendorId, currentAction);
                }
            });
            
            // Submit vendor form
            vendorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                saveVendor(formData);
            });
            
            // Search vendors
            vendorSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#vendors-table tbody tr');
                
                rows.forEach(row => {
                    const shopName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const ownerName = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                    
                    if (shopName.includes(searchTerm) || ownerName.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Reset form
            function resetForm() {
                vendorForm.reset();
                document.getElementById('vendor-id').value = '';
                document.getElementById('status').value = 'pending';
            }
            
            // Load vendor data for editing
            function loadVendorData(id) {
                fetch(`../php/admin/get_vendor.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const vendor = data.vendor;
                            document.getElementById('vendor-id').value = vendor.id;
                            document.getElementById('shop-name').value = vendor.shop_name;
                            document.getElementById('owner-name').value = vendor.owner_name;
                            document.getElementById('email').value = vendor.email;
                            document.getElementById('phone').value = vendor.phone || '';
                            document.getElementById('address').value = vendor.address || '';
                            document.getElementById('logo').value = vendor.logo || '';
                            document.getElementById('status').value = vendor.status;
                            document.getElementById('description').value = vendor.description || '';
                        }
                    })
                    .catch(error => console.error('Error loading vendor data:', error));
            }
            
            // Load vendor details
            function loadVendorDetails(id) {
                fetch(`../php/admin/get_vendor_details.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const vendor = data.vendor;
                            document.getElementById('detail-logo').src = vendor.logo || '../assets/images/shop-placeholder.png';
                            document.getElementById('detail-shop-name').textContent = vendor.shop_name;
                            
                            const statusEl = document.getElementById('detail-status');
                            statusEl.textContent = vendor.status.charAt(0).toUpperCase() + vendor.status.slice(1);
                            statusEl.className = 'status ' + vendor.status.toLowerCase();
                            
                            document.getElementById('detail-owner').textContent = vendor.owner_name;
                            document.getElementById('detail-email').textContent = vendor.email;
                            document.getElementById('detail-phone').textContent = vendor.phone || 'Not provided';
                            document.getElementById('detail-address').textContent = vendor.address || 'Not provided';
                            document.getElementById('detail-description').textContent = vendor.description || 'No description available';
                            document.getElementById('detail-products').textContent = vendor.product_count;
                            document.getElementById('detail-orders').textContent = vendor.order_count;
                            document.getElementById('detail-joined').textContent = new Date(vendor.created_at).toLocaleDateString();
                            document.getElementById('detail-updated').textContent = new Date(vendor.updated_at).toLocaleDateString();
                            
                            document.getElementById('view-products-link').href = `vendor_products.php?vendor_id=${vendor.id}`;
                            document.getElementById('view-orders-link').href = `vendor_orders.php?vendor_id=${vendor.id}`;
                        }
                    })
                    .catch(error => console.error('Error loading vendor details:', error));
            }
            
            // Save vendor
            function saveVendor(formData) {
                const url = formData.get('id') ? '../php/admin/update_vendor.php' : '../php/admin/add_vendor.php';
                
                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        vendorModal.style.display = 'none';
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error saving vendor');
                    }
                })
                .catch(error => {
                    console.error('Error saving vendor:', error);
                    alert('An error occurred. Please try again.');
                });
            }
            
            // Update vendor status
            function updateVendorStatus(id, action) {
                fetch('../php/admin/update_vendor_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        confirmModal.style.display = 'none';
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error updating vendor status');
                    }
                })
                .catch(error => {
                    console.error('Error updating vendor status:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    </script>
</body>
</html>
