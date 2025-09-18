<?php
// Start the session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in as shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not logged in as shop owner, redirect to login
    header("Location: ../../Authentication/login.html");
    exit;
}

// User is authenticated, proceed with the dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owner Dashboard - Online Grocery Store</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="shop_owner.css">
</head>
<body class="shop-owner">
    <div class="shop-owner-sidebar">
        <div class="brand">
            Shop Dashboard
        </div>
        <div class="menu">
            <a href="shop_owner_index.php" class="menu-item active">
                <i class="material-icons">dashboard</i> <span>Dashboard</span>
            </a>
            <a href="shop_products.php" class="menu-item">
                <i class="material-icons">inventory</i> <span>Products</span>
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> <span>Orders</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="material-icons">bar_chart</i> <span>Reports</span>
            </a>
            <a href="shop_profile.php" class="menu-item">
                <i class="material-icons">store</i> <span>Shop Profile</span>
            </a>
        </div>
    </div>

    <div class="shop-owner-content">
        <div class="shop-owner-header">
            <h2>Dashboard</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="shop-owner-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Shop Owner'); ?></span>
                    <i class="material-icons">arrow_drop_down</i>
                    <div class="dropdown-content">
                        <a href="shop_profile.php">My Profile</a>
                        <a href="../../Authentication/logout.php" id="logout-btn">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="material-icons">shopping_bag</i>
                </div>
                <div class="stat-card-content">
                    <h3>Total Orders</h3>
                    <p id="total-orders">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="material-icons">hourglass_empty</i>
                </div>
                <div class="stat-card-content">
                    <h3>Pending Orders</h3>
                    <p id="pending-orders">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="material-icons">inventory_2</i>
                </div>
                <div class="stat-card-content">
                    <h3>Total Products</h3>
                    <p id="total-products">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="material-icons">payments</i>
                </div>
                <div class="stat-card-content">
                    <h3>Total Revenue</h3>
                    <p id="total-revenue">$0.00</p>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="recent-orders-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Orders</h3>
                        <a href="orders.php" class="view-all">View All</a>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recent-orders-table">
                                <!-- Orders will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="low-stock-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Low Stock Products</h3>
                        <a href="shop_products.php" class="view-all">View All Products</a>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-table">
                                <!-- Low stock products will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Functions to load dashboard data
        function loadDashboardData() {
            fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-orders').textContent = data.totalOrders;
                    document.getElementById('pending-orders').textContent = data.pendingOrders;
                    document.getElementById('total-products').textContent = data.totalProducts;
                    document.getElementById('total-revenue').textContent = '৳' + data.totalRevenue.toFixed(2);
                })
                .catch(error => console.error('Error loading dashboard stats:', error));
        }

        function loadRecentOrders() {
            fetch('get_recent_orders.php')
                .then(response => response.json())
                .then(orders => {
                    const tableBody = document.getElementById('recent-orders-table');
                    tableBody.innerHTML = '';
                    
                    orders.forEach(order => {
                        const row = document.createElement('tr');
                        
                        // Determine status class
                        let statusClass = '';
                        switch(order.status) {
                            case 'Processing':
                                statusClass = 'warning';
                                break;
                            case 'Delivered':
                                statusClass = 'success';
                                break;
                            case 'Cancelled':
                                statusClass = 'danger';
                                break;
                            default:
                                statusClass = '';
                        }
                        
                        row.innerHTML = `
                            <td>#${order.id}</td>
                            <td>${order.customer_name}</td>
                            <td>${order.order_date}</td>
                            <td><span class="badge ${statusClass}">${order.status}</span></td>
                            <td>$${parseFloat(order.total).toFixed(2)}</td>
                            <td>
                                <a href="order-details.php?id=${order.id}" class="btn icon primary">
                                    <i class="material-icons">visibility</i>
                                </a>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading recent orders:', error));
        }

        function loadLowStockProducts() {
            fetch('get_low_stock_products.php')
                .then(response => response.json())
                .then(products => {
                    const tableBody = document.getElementById('low-stock-table');
                    tableBody.innerHTML = '';
                    
                    products.forEach(product => {
                        const row = document.createElement('tr');
                        
                        // Determine stock class
                        let stockClass = 'success';
                        if (product.stock <= 5) {
                            stockClass = 'danger';
                        } else if (product.stock <= 10) {
                            stockClass = 'warning';
                        }
                        
                        row.innerHTML = `
                            <td>${product.id}</td>
                            <td><img src="../uploads/products/${product.image || 'no-image.jpg'}" alt="${product.name}" class="thumbnail"></td>
                            <td>${product.name}</td>
                            <td>${product.category_name}</td>
                            <td>$${parseFloat(product.price).toFixed(2)}</td>
                            <td><span class="badge ${stockClass}">${product.stock}</span></td>
                            <td>
                                <a href="edit-product.php?id=${product.id}" class="btn icon primary">
                                    <i class="material-icons">edit</i>
                                </a>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading low stock products:', error));
        }

        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard page loaded, initializing...');
            
            // Load dashboard data
            loadDashboardData();
            loadRecentOrders();
            loadLowStockProducts();
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '../../Authentication/logout.php';
            });
        });
    </script>
</body>
</html>
