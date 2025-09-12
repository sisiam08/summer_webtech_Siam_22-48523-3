<?php
// Start session
session_start();

// Force browser to clear cache for this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include functions
require_once '../php/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Not logged in or not an admin, redirect to login page
    header('Location: ../login.php');
    exit;
}

// Get admin user data
$adminUser = getCurrentUser();
$adminName = $adminUser ? $adminUser['name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Grocery Store</title>
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
            <a href="index.php" class="menu-item active">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> Orders
            </a>
            <a href="users.php" class="menu-item">
                <i class="material-icons">people</i> Users
            </a>
            <a href="vendors.php" class="menu-item">
                <i class="material-icons">store</i> Vendors
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
                        <a href="profile.php">Profile</a>
                        <a href="../php/logout.php">Logout</a>
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

        <div class="dashboard-widgets">
            <div class="widget">
                <div class="widget-header">
                    <i class="material-icons">shopping_cart</i>
                    <h3>Orders</h3>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <h4 id="total-orders">0</h4>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat">
                        <h4 id="pending-orders">0</h4>
                        <p>Pending</p>
                    </div>
                </div>
            </div>

            <div class="widget">
                <div class="widget-header">
                    <i class="material-icons">people</i>
                    <h3>Users</h3>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <h4 id="total-users">0</h4>
                        <p>Total Users</p>
                    </div>
                    <div class="stat">
                        <h4 id="new-users">0</h4>
                        <p>New This Month</p>
                    </div>
                </div>
            </div>

            <div class="widget">
                <div class="widget-header">
                    <i class="material-icons">payments</i>
                    <h3>Revenue</h3>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <h4 id="total-revenue">$0.00</h4>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat">
                        <h4 id="monthly-revenue">$0.00</h4>
                        <p>This Month</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="section">
                <div class="section-header">
                    <h3>Recent Orders</h3>
                    <a href="orders.php" class="view-all">View All</a>
                </div>
                <div class="section-content">
                    <table class="data-table" id="recent-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to fetch dashboard data
        function fetchDashboardData() {
            fetch('../php/admin/get_dashboard_summary.php')
                .then(response => response.json())
                .then(data => {
                    // Update widgets
                    document.getElementById('total-orders').textContent = data.orders.total || 0;
                    document.getElementById('pending-orders').textContent = data.orders.pending || 0;
                    document.getElementById('total-users').textContent = data.users.total || 0;
                    document.getElementById('new-users').textContent = data.users.new || 0;
                    document.getElementById('total-revenue').textContent = '$' + (data.revenue.total || '0.00');
                    document.getElementById('monthly-revenue').textContent = '$' + (data.revenue.monthly || '0.00');
                    
                    // Populate recent orders table
                    const recentOrdersTable = document.getElementById('recent-orders-table').getElementsByTagName('tbody')[0];
                    recentOrdersTable.innerHTML = '';
                    
                    if (data.recentOrders && data.recentOrders.length > 0) {
                        data.recentOrders.forEach(order => {
                            const row = recentOrdersTable.insertRow();
                            row.innerHTML = `
                                <td>${order.id}</td>
                                <td>${order.customer}</td>
                                <td>${order.date}</td>
                                <td>$${order.amount}</td>
                                <td><span class="status ${order.status.toLowerCase()}">${order.status}</span></td>
                            `;
                        });
                    } else {
                        const row = recentOrdersTable.insertRow();
                        row.innerHTML = '<td colspan="5" class="no-data">No recent orders</td>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                });
        }
        
        // Fetch dashboard data on page load
        document.addEventListener('DOMContentLoaded', fetchDashboardData);
    </script>
</body>
</html>
