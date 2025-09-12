<?php
// Initialize session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user = getCurrentUser();
$userName = $user['name'] ?? 'Customer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Online Grocery Store</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .customer-dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .welcome-message h2 {
            margin: 0;
            color: #333;
        }
        
        .welcome-message p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .stat-card p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .action-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
            color: #4CAF50;
        }
        
        .user-menu {
            position: relative;
            display: inline-block;
        }
        
        .user-menu button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .user-menu-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 4px;
            z-index: 1;
        }
        
        .user-menu-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .user-menu-content a:hover {
            background-color: #f1f1f1;
        }
        
        .user-menu.active .user-menu-content {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">Online Grocery Store</a></h1>
            <nav>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../products.php">Products</a></li>
                    <li><a href="../cart.php">Cart</a></li>
                    <li><a href="orders.html">My Orders</a></li>
                    <li class="user-menu">
                        <button onclick="toggleUserMenu()"><?php echo htmlspecialchars($userName); ?> ▼</button>
                        <div class="user-menu-content">
                            <a href="profile.html">My Profile</a>
                            <a href="addresses.html">Addresses</a>
                            <a href="wishlist.html">Wishlist</a>
                            <a href="../logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="customer-dashboard">
        <div class="dashboard-header">
            <div class="welcome-message">
                <h2>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
                <p>Manage your orders, track deliveries, and shop for fresh groceries</p>
            </div>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="icon" style="color: #4CAF50;">🛒</div>
                <h3 id="total-orders">-</h3>
                <p>Total Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon" style="color: #FF9800;">📦</div>
                <h3 id="pending-orders">-</h3>
                <p>Pending Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon" style="color: #2196F3;">❤️</div>
                <h3 id="wishlist-items">-</h3>
                <p>Wishlist Items</p>
            </div>
            
            <div class="stat-card">
                <div class="icon" style="color: #9C27B0;">💰</div>
                <h3 id="total-spent">৳0</h3>
                <p>Total Spent</p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="../products.php" class="action-card">
                <div class="icon">🛍️</div>
                <h4>Shop Now</h4>
                <p>Browse our fresh products</p>
            </a>
            
            <a href="orders.html" class="action-card">
                <div class="icon">📋</div>
                <h4>My Orders</h4>
                <p>View order history</p>
            </a>
            
            <a href="track-order.php" class="action-card">
                <div class="icon">📍</div>
                <h4>Track Order</h4>
                <p>Track your deliveries</p>
            </a>
            
            <a href="addresses.html" class="action-card">
                <div class="icon">🏠</div>
                <h4>Addresses</h4>
                <p>Manage delivery addresses</p>
            </a>
            
            <a href="profile.html" class="action-card">
                <div class="icon">👤</div>
                <h4>My Profile</h4>
                <p>Update account details</p>
            </a>
            
            <a href="wishlist.html" class="action-card">
                <div class="icon">💝</div>
                <h4>Wishlist</h4>
                <p>Saved for later</p>
            </a>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleUserMenu() {
            document.querySelector('.user-menu').classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Load dashboard statistics
        document.addEventListener('DOMContentLoaded', function() {
            // You can implement API calls here to load real statistics
            // For now, using placeholder values
            
            // Example API calls (implement these APIs as needed):
            // loadOrderStats();
            // loadWishlistCount();
            // loadTotalSpent();
        });
    </script>
</body>
</html>
