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
    header("Location: login.html");
    exit;
}

// User is authenticated, proceed with the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Profile - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
</head>
<body class="shop-owner">
    <div class="shop-owner-sidebar">
        <div class="brand">
            Shop Dashboard
        </div>
        <div class="menu">
            <a href="index.php" class="menu-item">
                <i class="material-icons">dashboard</i> <span>Dashboard</span>
            </a>
            <a href="products.php" class="menu-item">
                <i class="material-icons">inventory</i> <span>Products</span>
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> <span>Orders</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="material-icons">bar_chart</i> <span>Reports</span>
            </a>
            <a href="profile.php" class="menu-item active">
                <i class="material-icons">store</i> <span>Shop Profile</span>
            </a>
        </div>
    </div>

    <div class="shop-owner-content">
        <div class="shop-owner-header">
            <h2>Shop Profile</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="shop-owner-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Shop Owner'); ?></span>
                    <i class="material-icons">arrow_drop_down</i>
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="#" id="logout-btn">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-container">
            <div class="row">
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Shop Information</h3>
                        </div>
                        <div class="card-body">
                            <form id="shop-profile-form">
                                <div class="form-group">
                                    <label for="shop-name">Shop Name</label>
                                    <input type="text" id="shop-name" name="shop-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="shop-description">Description</label>
                                    <textarea id="shop-description" name="shop-description" rows="4"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="shop-address">Address</label>
                                    <textarea id="shop-address" name="shop-address" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="shop-phone">Contact Phone</label>
                                    <input type="tel" id="shop-phone" name="shop-phone">
                                </div>
                                <div class="form-group">
                                    <label for="shop-email">Contact Email</label>
                                    <input type="email" id="shop-email" name="shop-email">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn primary" id="save-shop-btn">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Account Information</h3>
                        </div>
                        <div class="card-body">
                            <form id="account-info-form">
                                <div class="form-group">
                                    <label for="account-name">Full Name</label>
                                    <input type="text" id="account-name" name="account-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="account-email">Email</label>
                                    <input type="email" id="account-email" name="account-email" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn primary" id="save-account-btn">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3>Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form id="change-password-form">
                                <div class="form-group">
                                    <label for="current-password">Current Password</label>
                                    <input type="password" id="current-password" name="current-password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password" name="new-password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password" name="confirm-password" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn primary" id="change-password-btn">Change Password</button>
                                </div>
                            </form>
                        </div>
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
                        document.getElementById('shop-name').value = data.shop.name || '';
                        document.getElementById('shop-description').value = data.shop.description || '';
                        document.getElementById('shop-address').value = data.shop.address || '';
                        document.getElementById('shop-phone').value = data.shop.phone || '';
                        document.getElementById('shop-email').value = data.shop.email || '';
                        
                        document.getElementById('account-name').value = data.user.name || '';
                        document.getElementById('account-email').value = data.user.email || '';
                    } else {
                        alert('Error loading profile: ' + data.message);
                    }
                })
                .catch(error => console.error('Error loading shop profile:', error));
        }
        
        // Function to save shop profile
        function saveShopProfile(event) {
            event.preventDefault();
            
            const shopData = {
                name: document.getElementById('shop-name').value,
                description: document.getElementById('shop-description').value,
                address: document.getElementById('shop-address').value,
                phone: document.getElementById('shop-phone').value,
                email: document.getElementById('shop-email').value
            };
            
            fetch('update_shop_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(shopData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Shop profile updated successfully!');
                } else {
                    alert('Error updating shop profile: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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
                    alert('Account information updated successfully!');
                    // Update the displayed name in the header
                    document.getElementById('shop-owner-name').textContent = userData.name;
                } else {
                    alert('Error updating account information: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Function to change password
        function changePassword(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            const passwordData = {
                current_password: currentPassword,
                new_password: newPassword
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
                    alert('Password changed successfully!');
                    // Clear the password fields
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('confirm-password').value = '';
                } else {
                    alert('Error changing password: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Initialize profile on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load shop profile
            loadShopProfile();
            
            // Add event listeners for form submissions
            document.getElementById('shop-profile-form').addEventListener('submit', saveShopProfile);
            document.getElementById('account-info-form').addEventListener('submit', saveAccountInfo);
            document.getElementById('change-password-form').addEventListener('submit', changePassword);
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        });
    </script>
</body>
</html>
