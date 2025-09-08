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
    <title>Reports - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <!-- Include Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="reports.php" class="menu-item active">
                <i class="material-icons">bar_chart</i> <span>Reports</span>
            </a>
            <a href="profile.php" class="menu-item">
                <i class="material-icons">store</i> <span>Shop Profile</span>
            </a>
        </div>
    </div>

    <div class="shop-owner-content">
        <div class="shop-owner-header">
            <h2>Reports</h2>
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

        <div class="report-filters">
            <div class="filter-group">
                <label for="time-period">Time Period:</label>
                <select id="time-period">
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>Last 30 Days</option>
                    <option value="quarter">Last 3 Months</option>
                    <option value="year">Last Year</option>
                </select>
            </div>
        </div>

        <div class="reports-container">
            <div class="row">
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Sales Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="sales-chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Order Status</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="order-status-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Top Selling Products</h3>
                        </div>
                        <div class="card-body">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="top-products-table">
                                    <!-- Top products will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="card">
                        <div class="card-header">
                            <h3>Revenue by Category</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="category-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load sales data and create chart
        function loadSalesChart() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_sales_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('sales-chart').getContext('2d');
                    
                    // Destroy previous chart if it exists
                    if (window.salesChart) {
                        window.salesChart.destroy();
                    }
                    
                    window.salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Sales Amount ($)',
                                data: data.salesData,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 2,
                                tension: 0.1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value;
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading sales data:', error));
        }
        
        // Function to load order status data and create chart
        function loadOrderStatusChart() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_order_status_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('order-status-chart').getContext('2d');
                    
                    // Destroy previous chart if it exists
                    if (window.orderStatusChart) {
                        window.orderStatusChart.destroy();
                    }
                    
                    window.orderStatusChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.counts,
                                backgroundColor: [
                                    'rgba(255, 206, 86, 0.7)',   // Pending
                                    'rgba(54, 162, 235, 0.7)',   // Processing
                                    'rgba(75, 192, 192, 0.7)',   // Shipped
                                    'rgba(153, 102, 255, 0.7)',  // Delivered
                                    'rgba(255, 99, 132, 0.7)'    // Cancelled
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading order status data:', error));
        }
        
        // Function to load top selling products
        function loadTopProducts() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_top_products.php?period=${period}`)
                .then(response => response.json())
                .then(products => {
                    const tableBody = document.getElementById('top-products-table');
                    tableBody.innerHTML = '';
                    
                    products.forEach(product => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${product.name}</td>
                            <td>${product.category_name}</td>
                            <td>${product.quantity_sold}</td>
                            <td>$${parseFloat(product.revenue).toFixed(2)}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading top products:', error));
        }
        
        // Function to load category revenue data and create chart
        function loadCategoryChart() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_category_revenue.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('category-chart').getContext('2d');
                    
                    // Destroy previous chart if it exists
                    if (window.categoryChart) {
                        window.categoryChart.destroy();
                    }
                    
                    // Generate colors for categories
                    const colors = [];
                    for (let i = 0; i < data.labels.length; i++) {
                        const hue = (i * 137) % 360; // Use golden ratio to generate distinct colors
                        colors.push(`hsla(${hue}, 70%, 60%, 0.7)`);
                    }
                    
                    window.categoryChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.revenue,
                                backgroundColor: colors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading category revenue data:', error));
        }
        
        // Initialize reports on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial reports
            loadSalesChart();
            loadOrderStatusChart();
            loadTopProducts();
            loadCategoryChart();
            
            // Add event listener for time period changes
            document.getElementById('time-period').addEventListener('change', function() {
                loadSalesChart();
                loadOrderStatusChart();
                loadTopProducts();
                loadCategoryChart();
            });
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        });
    </script>
</body>
</html>
