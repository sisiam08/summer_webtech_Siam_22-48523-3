<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Redirect to login page if not authenticated
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get shop information for the current user
$shop_name = 'ShopHub'; // Default fallback name
try {
    require_once __DIR__ . '/../../Database/database.php';
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT name FROM shops WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop_data && !empty($shop_data['name'])) {
        $shop_name = htmlspecialchars($shop_data['name']);
    }
} catch (Exception $e) {
    // If there's an error, just use the default name
    error_log('Error fetching shop name: ' . $e->getMessage());
}

// User is authenticated, proceed with the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Profile - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="shop_owner_modern.css">
    <link rel="stylesheet" href="notification-system.css">
    <script src="notification-system.js"></script>
</head>
<body class="shop-owner">
    <!-- Modern Navigation Sidebar -->
    <div class="modern-sidebar">
        <div class="sidebar-header">
            <div class="brand-logo">
                <i class="fas fa-store"></i>
                <span><?php echo $shop_name; ?></span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="shop_owner_index.php" class="nav-link">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shop_products.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shop_profile.php" class="nav-link active">
                        <i class="fas fa-store-alt"></i>
                        <span>Shop Profile</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-logout">
                <a href="../../Authentication/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="breadcrumb">
                    <h1>Shop Profile</h1><br><br>
                    <p>Manage your shop information and account settings</p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="action-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Profile Grid -->
            <div class="profile-grid single-column">
                <!-- Shop Information Card -->
                <div class="content-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Shop Information</h3>
                            <p>Update your shop details and contact information</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-secondary" onclick="resetShopForm()">Reset</button>
                        </div>
                    </div>
                    <div class="card-content">
                        <form id="shop-profile-form" class="modern-form">
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="shop-name">Shop Name</label>
                                    <input type="text" id="shop-name" name="shop-name" required class="form-input">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="shop-description">Description</label>
                                    <textarea id="shop-description" name="shop-description" rows="4" class="form-textarea" placeholder="Tell customers about your shop..."></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="shop-address">Address</label>
                                    <textarea id="shop-address" name="shop-address" rows="3" class="form-textarea" placeholder="Full shop address..."></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="shop-phone">Contact Phone</label>
                                    <input type="tel" id="shop-phone" name="shop-phone" class="form-input" placeholder="+880 1XXX-XXXXXX">
                                </div>
                                <div class="form-group half-width">
                                    <label for="shop-email">Contact Email</label>
                                    <input type="email" id="shop-email" name="shop-email" class="form-input" placeholder="shop@example.com">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="save-shop-btn">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Information Card -->
                <div class="content-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Account Information</h3>
                            <p>Manage your account credentials and security</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <form id="account-info-form" class="modern-form">
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="account-name">Full Name</label>
                                    <input type="text" id="account-name" name="account-name" required class="form-input">
                                </div>
                                <div class="form-group half-width">
                                    <label for="account-email">Email</label>
                                    <input type="email" id="account-email" name="account-email" required class="form-input">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="save-account-btn">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="content-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Change Password</h3>
                            <p>Update your account password for security</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <form id="change-password-form" class="modern-form">
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="current-password">Current Password</label>
                                    <input type="password" id="current-password" name="current-password" required class="form-input">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password" name="new-password" required class="form-input">
                                </div>
                                <div class="form-group half-width">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password" name="confirm-password" required class="form-input">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-secondary" id="change-password-btn">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load shop profile data
        function loadShopProfile() {
            fetch('get_shop_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Load shop information
                        document.getElementById('shop-name').value = data.shop.name || '';
                        document.getElementById('shop-description').value = data.shop.description || '';
                        document.getElementById('shop-address').value = data.shop.address || '';
                        document.getElementById('shop-phone').value = data.shop.phone || '';
                        document.getElementById('shop-email').value = data.shop.email || '';
                        
                        // Load account information
                        document.getElementById('account-name').value = data.user.name || '';
                        document.getElementById('account-email').value = data.user.email || '';
                                        
                    } else {
                        showNotification('Error loading profile: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading shop profile:', error);
                    showNotification('Error loading shop profile', 'error');
                });
        }

        // Function to reset shop form
        function resetShopForm() {
            showConfirm('Reset Changes', 'Are you sure you want to reset all changes?').then(confirmed => {
                if (confirmed) {
                    loadShopProfile();
                }
            });
        }
        
        // Function to save shop profile
        function saveShopProfile(event) {
            event.preventDefault();
            console.log('saveShopProfile called');
            
            const shopData = {
                name: document.getElementById('shop-name').value,
                description: document.getElementById('shop-description').value,
                address: document.getElementById('shop-address').value,
                phone: document.getElementById('shop-phone').value,
                email: document.getElementById('shop-email').value
            };
            
            console.log('Shop data:', shopData);
            
            // Show loading notification
            showLoading('Updating shop profile...');
            
            fetch('update_shop_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(shopData)
            })
            .then(response => response.json())
            .then(data => {
                hideLoading(); // Hide loading state
                console.log('Response:', data);
                if (data.success) {
                    showNotification('Shop profile updated successfully!', 'success');
                } else {
                    showNotification('Error updating shop profile: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading(); // Hide loading state
                console.error('Error:', error);
                showNotification('Error updating shop profile', 'error');
            });
        }
        
        // Function to save account information
        function saveAccountInfo(event) {
            event.preventDefault();
            
            const userData = {
                name: document.getElementById('account-name').value,
                email: document.getElementById('account-email').value
            };
            
            fetch('update_user_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Account information updated successfully!');
                    // Update the displayed name in the header
                    document.getElementById('shop-owner-name').textContent = userData.name;
                } else {
                    showNotification('Error updating account information: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating account information', 'error');
            });
        }
        
        // Function to change password
        function changePassword(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Validate passwords
            if (newPassword !== confirmPassword) {
                showNotification('New passwords do not match', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                showNotification('New password must be at least 6 characters long', 'error');
                return;
            }
            
            const passwordData = {
                currentPassword: currentPassword,
                newPassword: newPassword
            };
            
            fetch('change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(passwordData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Password changed successfully!');
                    // Clear the form
                    document.getElementById('change-password-form').reset();
                } else {
                    showNotification('Error changing password: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error changing password', 'error');
            });
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            // Initialize notification system
            if (typeof NotificationSystem !== 'undefined') {
                window.notificationSystem = new NotificationSystem();
                console.log('Notification system initialized');
            } else {
                console.error('NotificationSystem not found');
            }
            
            // Load shop profile data
            loadShopProfile();
            
            // Add event listeners for forms
            const shopForm = document.getElementById('shop-profile-form');
            const accountForm = document.getElementById('account-info-form');
            const passwordForm = document.getElementById('change-password-form');
            
            if (shopForm) {
                shopForm.addEventListener('submit', saveShopProfile);
                console.log('Shop form listener added');
            } else {
                console.error('Shop form not found');
            }
            
            if (accountForm) {
                accountForm.addEventListener('submit', saveAccountInfo);
                console.log('Account form listener added');
            }
            
            if (passwordForm) {
                passwordForm.addEventListener('submit', changePassword);
                console.log('Password form listener added');
            }
        });
    </script>
</body>
</html>
