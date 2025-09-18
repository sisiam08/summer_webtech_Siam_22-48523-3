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
    <title>Reports - Shop Owner Dashboard</title>
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
                    <a href="reports.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shop_profile.php" class="nav-link">
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
                    <h1>Reports & Analytics</h1><br><br>
                    <p>Track your shop's performance and sales data</p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <select class="btn-secondary" id="time-period">
                        <option value="week">Last 7 Days</option>
                        <option value="month" selected>Last 30 Days</option>
                        <option value="quarter">Last 3 Months</option>
                        <option value="year">Last Year</option>
                    </select>
                    <button class="action-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Analytics Cards -->
            <div class="analytics-grid">
                <div class="analytics-card primary">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-content">
                        <h3 id="total-sales">৳0</h3>
                        <p>Total Sales</p>
                    </div>
                </div>
                
                <div class="analytics-card secondary">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-content">
                        <h3 id="total-orders">0</h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="analytics-card tertiary">
                    <div class="card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="card-content">
                        <h3 id="products-sold">0</h3>
                        <p>Products Sold</p>
                    </div>
                </div>
                
                <div class="analytics-card quaternary">
                    <div class="card-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="card-content">
                        <h3 id="avg-order">৳0</h3>
                        <p>Avg Order Value</p>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <div class="content-card chart-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Sales Overview</h3>
                            <p>Daily sales performance over selected period</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-icon" onclick="refreshSalesChart()">
                                <i class="fas fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-content chart-container">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>

                <div class="content-card chart-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Order Status Distribution</h3>
                            <p>Breakdown of order statuses</p>
                        </div>
                    </div>
                    <div class="card-content chart-container">
                        <canvas id="order-status-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Products Performance -->
            <div class="content-card">
                <div class="card-header">
                    <div class="header-info">
                        <h3>Top Performing Products</h3>
                        <p>Best selling products in selected period</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-secondary">Export Data</button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Growth</th>
                                </tr>
                            </thead>
                            <tbody id="top-products-table">
                                <!-- Top products will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Revenue by Category -->
            <div class="content-card">
                <div class="card-header">
                    <div class="header-info">
                        <h3>Revenue by Category</h3>
                        <p>Performance breakdown by product categories</p>
                    </div>
                </div>
                <div class="card-content chart-container">
                    <canvas id="category-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load analytics data
        function loadAnalyticsData() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_analytics_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    // Update analytics cards
                    document.getElementById('total-sales').textContent = `৳${data.totalSales.toLocaleString()}`;
                    document.getElementById('total-orders').textContent = data.totalOrders.toLocaleString();
                    document.getElementById('products-sold').textContent = data.productsSold.toLocaleString();
                    document.getElementById('avg-order').textContent = `৳${data.avgOrderValue.toFixed(2)}`;
                })
                .catch(error => console.error('Error loading analytics data:', error));
        }

        // Function to refresh sales chart
        function refreshSalesChart() {
            loadSalesChart();
        }

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
                                label: 'Sales Amount (৳)',
                                data: data.salesData,
                                backgroundColor: 'rgba(87, 96, 111, 0.1)',
                                borderColor: '#576071',
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#576071',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: '#e5e7eb',
                                        borderColor: '#e5e7eb'
                                    },
                                    ticks: {
                                        color: '#6b7280'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#e5e7eb',
                                        borderColor: '#e5e7eb'
                                    },
                                    ticks: {
                                        color: '#6b7280',
                                        callback: function(value) {
                                            return '৳' + value.toLocaleString();
                                        }
                                    }
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
                                    '#fbbf24',  // Pending - amber
                                    '#3b82f6',  // Processing - blue
                                    '#10b981',  // Shipped - emerald
                                    '#8b5cf6',  // Delivered - violet
                                    '#ef4444'   // Cancelled - red
                                ],
                                borderWidth: 0,
                                hoverBorderWidth: 2,
                                hoverBorderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
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
                        const growthClass = product.growth >= 0 ? 'positive' : 'negative';
                        const growthIcon = product.growth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                        
                        row.innerHTML = `
                            <td>
                                <div class="product-info">
                                    <strong>${product.name}</strong>
                                    <small>SKU: ${product.sku || 'N/A'}</small>
                                </div>
                            </td>
                            <td>${product.category_name}</td>
                            <td>${product.quantity_sold} units</td>
                            <td class="amount-cell">৳${parseFloat(product.revenue).toLocaleString()}</td>

                        `;
                        tableBody.appendChild(row);
                    });
                    
                    if (products.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="5" style="text-align: center; color: #777;">No product data available for selected period</td>`;
                        tableBody.appendChild(row);
                    }
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
                    
                    // Generate modern color palette for categories
                    const colors = [
                        '#576071', '#6366f1', '#8b5cf6', '#06b6d4', 
                        '#10b981', '#f59e0b', '#ef4444', '#84cc16'
                    ];
                    
                    window.categoryChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.revenue,
                                backgroundColor: colors.slice(0, data.labels.length),
                                borderWidth: 0,
                                hoverBorderWidth: 2,
                                hoverBorderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ৳${value.toLocaleString()} (${percentage}%)`;
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
            // Load initial reports and analytics
            loadAnalyticsData();
            loadSalesChart();
            loadOrderStatusChart();
            loadTopProducts();
            loadCategoryChart();
            
            // Add event listener for time period changes
            document.getElementById('time-period').addEventListener('change', function() {
                loadAnalyticsData();
                loadSalesChart();
                loadOrderStatusChart();
                loadTopProducts();
                loadCategoryChart();
            });
        });
    </script>
</body>
</html>
