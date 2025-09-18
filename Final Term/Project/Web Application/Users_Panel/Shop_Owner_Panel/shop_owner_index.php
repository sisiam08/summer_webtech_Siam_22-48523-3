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

// Get shop information for the current user
$shop_name = 'ShopHub'; // Default fallback name
try {
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

// User is authenticated, proceed with the dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owner Dashboard - Online Grocery Store</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
                    <a href="shop_owner_index.php" class="nav-link active">
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
                    <h1>Dashboard Overview</h1><br><br>
                    <p>Welcome back! Here's what's happening in your shop today.</p>
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

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Stats Cards Section -->
            <div class="stats-grid">
                <div class="stat-card primary">
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

                <div class="stat-card warning">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="pending-orders">0</h3>
                            <p>Pending Orders</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-products">0</h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-card-content">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-revenue">৳0.00</h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Section -->
            <div class="content-grid">
                <!-- Recent Orders -->
                <div class="content-card recent-orders">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Recent Orders</h3>
                            <p>Latest customer orders requiring attention</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-secondary">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="orders.php" class="btn-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
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

                <!-- Low Stock Alert -->
                <div class="content-card low-stock">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Low Stock Alert</h3>
                            <p>Products running low on inventory</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-secondary">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <a href="shop_products.php" class="btn-primary">Manage Stock</a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
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

                <!-- Quick Actions -->
                <div class="content-card quick-actions">
                    <div class="card-header">
                        <div class="header-info">
                            <h3>Quick Actions</h3>
                            <p>Frequently used shortcuts</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="actions-grid">
                            <a href="add-product.php" class="action-item">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="action-text">
                                    <h4>Add Product</h4>
                                    <p>Add new product to inventory</p>
                                </div>
                            </a>
                            <a href="orders.php" class="action-item">
                                <div class="action-icon">
                                    <i class="fas fa-list-alt"></i>
                                </div>
                                <div class="action-text">
                                    <h4>View Orders</h4>
                                    <p>Manage customer orders</p>
                                </div>
                            </a>
                            <a href="reports.php" class="action-item">
                                <div class="action-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="action-text">
                                    <h4>Sales Report</h4>
                                    <p>View detailed reports</p>
                                </div>
                            </a>
                            <a href="shop_profile.php" class="action-item">
                                <div class="action-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div class="action-text">
                                    <h4>Shop Settings</h4>
                                    <p>Update shop information</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modern Dashboard JavaScript
        class DashboardManager {
            constructor() {
                this.init();
            }

            init() {
                this.setupSidebar();
                this.loadDashboardData();
                this.loadRecentOrders();
                this.loadLowStockProducts();
                this.setupEventListeners();
            }

            setupSidebar() {
                const sidebarToggle = document.getElementById('sidebarToggle');
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                const sidebar = document.querySelector('.modern-sidebar');

                if (sidebarToggle) {
                    sidebarToggle.addEventListener('click', () => {
                        sidebar.classList.toggle('collapsed');
                    });
                }

                if (mobileMenuToggle) {
                    mobileMenuToggle.addEventListener('click', () => {
                        sidebar.classList.toggle('mobile-open');
                    });
                }

                // Close mobile menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (window.innerWidth <= 768 && 
                        !sidebar.contains(e.target) && 
                        !mobileMenuToggle.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                    }
                });
            }

            loadDashboardData() {
                fetch('get_dashboard_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        this.animateCounter('total-orders', data.totalOrders);
                        this.animateCounter('pending-orders', data.pendingOrders);
                        this.animateCounter('total-products', data.totalProducts);
                        this.animateRevenue('total-revenue', data.totalRevenue);
                    })
                    .catch(error => console.error('Error loading dashboard stats:', error));
            }

            animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return;

                let currentValue = 0;
                const increment = targetValue / 30;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        element.textContent = targetValue;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(currentValue);
                    }
                }, 50);
            }

            animateRevenue(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return;

                let currentValue = 0;
                const increment = targetValue / 30;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        element.textContent = '৳' + targetValue.toFixed(2);
                        clearInterval(timer);
                    } else {
                        element.textContent = '৳' + currentValue.toFixed(2);
                    }
                }, 50);
            }

            loadRecentOrders() {
                fetch('get_recent_orders.php')
                    .then(response => response.json())
                    .then(orders => {
                        const tableBody = document.getElementById('recent-orders-table');
                        if (!tableBody) return;

                        tableBody.innerHTML = '';
                        
                        orders.forEach(order => {
                            const row = document.createElement('tr');
                            row.className = 'table-row';
                            
                            const statusClass = this.getStatusClass(order.status);
                            
                            row.innerHTML = `
                                <td>
                                    <div class="order-id">#${order.id}</div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="customer-details">
                                            <div class="customer-name">${order.customer_name}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-date">${this.formatDate(order.order_date)}</div>
                                </td>
                                <td>
                                    <span class="status-badge ${statusClass}">${order.status}</span>
                                </td>
                                <td>
                                    <div class="amount">৳${parseFloat(order.total).toFixed(2)}</div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon view" onclick="viewOrder(${order.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon edit" onclick="editOrder(${order.id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            
                            tableBody.appendChild(row);
                        });
                    })
                    .catch(error => console.error('Error loading recent orders:', error));
            }

            loadLowStockProducts() {
                fetch('get_low_stock_products.php')
                    .then(response => response.json())
                    .then(products => {
                        const tableBody = document.getElementById('low-stock-table');
                        if (!tableBody) return;

                        tableBody.innerHTML = '';
                        
                        products.forEach(product => {
                            const row = document.createElement('tr');
                            row.className = 'table-row';
                            
                            const stockClass = this.getStockClass(product.stock);
                            
                            row.innerHTML = `
                                <td>
                                    <div class="product-info">
                                        <div class="product-image">
                                            <img src="../../Uploads/products/${product.image || 'no-image.jpg'}" alt="${product.name}">
                                        </div>
                                        <div class="product-details">
                                            <div class="product-name">${product.name}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-tag">${product.category_name}</span>
                                </td>
                                <td>
                                    <div class="price">৳${parseFloat(product.price).toFixed(2)}</div>
                                </td>
                                <td>
                                    <span class="stock-badge ${stockClass}">${product.stock} units</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon edit" onclick="editProduct(${product.id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon restock" onclick="restockProduct(${product.id})">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            
                            tableBody.appendChild(row);
                        });
                    })
                    .catch(error => console.error('Error loading low stock products:', error));
            }

            getStatusClass(status) {
                const statusMap = {
                    'Processing': 'warning',
                    'Delivered': 'success',
                    'Cancelled': 'danger',
                    'Pending': 'info'
                };
                return statusMap[status] || 'default';
            }

            getStockClass(stock) {
                if (stock <= 5) return 'danger';
                if (stock <= 10) return 'warning';
                return 'success';
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            setupEventListeners() {
                // Logout functionality
                const logoutBtn = document.querySelector('.logout-btn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = '../../Authentication/logout.php';
                    });
                }

                // Search functionality
                const searchInput = document.querySelector('.search-box input');
                if (searchInput) {
                    searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            // Implement search functionality
                            console.log('Search:', this.value);
                        }
                    });
                }

                // Dropdown toggles
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    toggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const dropdown = this.closest('.dropdown');
                        dropdown.classList.toggle('active');
                    });
                });

                // Close dropdowns when clicking outside
                document.addEventListener('click', function() {
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                });
            }
        }

        // Global functions for table actions
        function viewOrder(orderId) {
            window.location.href = `order-details.php?id=${orderId}`;
        }

        function editOrder(orderId) {
            window.location.href = `orders.php?edit=${orderId}`;
        }

        function editProduct(productId) {
            window.location.href = `edit-product.php?id=${productId}`;
        }

        function restockProduct(productId) {
            // Implement restock functionality
            console.log('Restock product:', productId);
        }

        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Modern dashboard initializing...');
            new DashboardManager();
        });
    </script>
</body>
</html>
