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
    <!-- Include Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <p>Monitor your shop's performance and insights</p>
                </div>
            </div>
            <div class="header-right">
                <div class="time-period-selector">
                    <label for="time-period">Time Period:</label>
                    <select id="time-period" class="period-select">
                        <option value="week">Last 7 Days</option>
                        <option value="month" selected>Last 30 Days</option>
                        <option value="quarter">Last 3 Months</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-container">

            <!-- Analytics Overview Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-sales">৳0</h3>
                            <p>Total Sales</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card secondary">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-orders">0</h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card tertiary">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="products-sold">0</h3>
                            <p>Products Sold</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card quaternary">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-percent"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="avg-order">৳0</h3>
                            <p>Avg Order Value</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Data Section -->
            <div class="content-grid">
                <div class="content-card chart-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Sales Overview</h3>
                            <p>Daily sales performance over selected period</p>
                        </div>
                        <div class="header-actions">
                            <button class="action-btn" onclick="refreshSalesChart()">
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

            <div class="content-grid">
                <div class="content-card table-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Top Selling Products</h3>
                            <p>Best performing products in selected period</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="table-container">
                            <table class="data-table">
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

                <div class="content-card chart-card">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Revenue by Category</h3>
                            <p>Category performance breakdown</p>
                        </div>
                    </div>
                    <div class="card-content chart-container">
                        <canvas id="category-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load analytics data
        function loadAnalyticsData() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_analytics_data.php?period=${period}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Server error:', data.error);
                        return;
                    }
                    // Update analytics cards
                    document.getElementById('total-sales').textContent = `৳${Math.ceil(data.totalSales).toLocaleString()}`;
                    document.getElementById('total-orders').textContent = data.totalOrders.toLocaleString();
                    document.getElementById('products-sold').textContent = data.productsSold.toLocaleString();
                    document.getElementById('avg-order').textContent = `৳${Math.ceil(data.avgOrderValue)}`;
                })
                .catch(error => {
                    console.error('Error loading analytics data:', error);
                    // Set fallback values
                    document.getElementById('total-sales').textContent = '৳0';
                    document.getElementById('total-orders').textContent = '0';
                    document.getElementById('products-sold').textContent = '0';
                    document.getElementById('avg-order').textContent = '৳0';
                });
        }

        // Function to refresh sales chart
        function refreshSalesChart() {
            loadSalesChart();
        }

        // Function to load sales data and create chart
        function loadSalesChart() {
            const period = document.getElementById('time-period').value;
            
            fetch(`get_sales_data.php?period=${period}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Server error in sales data:', data.error);
                        return;
                    }
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
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: '#576071',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return 'Sales: ৳' + context.parsed.y.toLocaleString();
                                        }
                                    }
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
                .catch(error => {
                    console.error('Error loading sales chart:', error);
                });
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
                                    '#ff6b6b',   // Pending
                                    '#4ecdc4',   // Processing
                                    '#45b7d1',   // Shipped
                                    '#96ceb4',   // Delivered
                                    '#feca57'    // Cancelled
                                ],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: '#576071',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    displayColors: true,
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
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
                    
                    if (products.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" class="no-data">No products found for this period</td></tr>';
                        return;
                    }
                    
                    products.forEach(product => {
                        const row = document.createElement('tr');
                        row.className = 'table-row';
                        row.innerHTML = `
                            <td>
                                <div class="product-info">
                                    <div class="product-name">${product.name}</div>
                                </div>
                            </td>
                            <td>
                                <span class="category-tag">${product.category_name}</span>
                            </td>
                            <td>
                                <span class="quantity-badge">${product.quantity_sold}</span>
                            </td>
                            <td>
                                <div class="revenue-amount">৳${Math.ceil(parseFloat(product.revenue))}</div>
                            </td>
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
                    const colors = [
                        '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57',
                        '#ff9ff3', '#54a0ff', '#5f27cd', '#00d2d3', '#ff9f43'
                    ];
                    
                    window.categoryChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.revenue,
                                backgroundColor: colors.slice(0, data.labels.length),
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: '#576071',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    displayColors: true,
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ৳${Math.ceil(value)} (${percentage}%)`;
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
            
            // Mobile sidebar functionality (if needed)
            const sidebar = document.querySelector('.modern-sidebar');
            
            // Close mobile menu when clicking outside (for mobile responsiveness)
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            });
        });
    </script>
</body>
</html>
