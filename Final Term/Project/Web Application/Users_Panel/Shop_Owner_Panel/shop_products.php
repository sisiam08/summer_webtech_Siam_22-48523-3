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
    <title>Products - Shop Owner Dashboard</title>
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
                    <a href="shop_owner_index.php" class="nav-link">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shop_products.php" class="nav-link active">
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
                    <h1>Products Management</h1><br><br>
                    <p>Manage your shop's product inventory and pricing</p>
                </div>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" id="product-search" placeholder="Search products...">
                    <i class="fas fa-search"></i>
                </div>
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
            <div class="content-card products-main">
                <div class="card-header">
                    <div class="header-info">
                        <h3>Your Products</h3>
                        <p>Manage your product inventory and stock levels</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-secondary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <button class="btn-primary" id="add-product-btn">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-table">
                        <!-- Products will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load products
        function loadProducts() {
            fetch('get_products_by_shop.php')
                .then(response => response.json())
                .then(products => {
                    const tableBody = document.getElementById('products-table');
                    tableBody.innerHTML = '';
                    
                    if (products.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                    No products found. <a href="add-product.php" style="color: var(--primary-color);">Add your first product</a>
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    products.forEach(product => {
                        const row = document.createElement('tr');
                        
                        // Determine stock class
                        let stockClass = 'success';
                        if (product.stock <= 2) {
                            stockClass = 'danger';
                        } else if (product.stock <= 5) {
                            stockClass = 'warning';
                        }
                        
                        // Determine status class
                        let statusClass = product.is_active ? 'success' : 'danger';
                        let statusText = product.is_active ? 'Active' : 'Inactive';
                        
                        row.innerHTML = `
                            <td>
                                <div class="product-id">#${product.id}</div>
                            </td>
                            <td>
                                <div class="product-info">
                                    <div class="product-image">
                                        <img src="../../Uploads/products/${product.image || 'no-image.jpg'}" alt="${product.name}">
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="product-details">
                                    <div class="product-name">${product.name}</div>
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
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon edit" onclick="editProduct(${product.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteProduct(${product.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    showNotification('Failed to load products. Please refresh the page.', 'error');
                });
        }
        
        // Global functions for table actions
        function editProduct(productId) {
            window.location.href = `edit-product.php?id=${productId}`;
        }

        function deleteProduct(productId) {
            showConfirm('Are you sure you want to delete this product? This action cannot be undone.', {
                title: 'Delete Product',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                type: 'warning'
            }).then(confirmed => {
                if (confirmed) {
                    const loadingNotification = showLoading('Deleting product...');
                    
                    fetch('delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: productId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        notificationSystem.hideNotification(loadingNotification);
                        
                        if (data.success) {
                            showNotification('Product deleted successfully!', 'success');
                            // Reload products after successful deletion
                            loadProducts();
                        } else {
                            showNotification('Error deleting product: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        notificationSystem.hideNotification(loadingNotification);
                        console.error('Error:', error);
                        showNotification('An error occurred while deleting the product', 'error');
                    });
                }
            });
        }
        
        // Initialize products on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load products
            loadProducts();
            
            // Add new product button event
            document.getElementById('add-product-btn').addEventListener('click', function() {
                window.location.href = 'add-product.php';
            });
            
            // Search functionality
            document.getElementById('product-search').addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                const rows = document.querySelectorAll('#products-table tr');
                
                rows.forEach(row => {
                    const productName = row.children[2].textContent.toLowerCase();
                    const category = row.children[3].textContent.toLowerCase();
                    
                    if (productName.includes(searchText) || category.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }); 
            });

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
        });
    </script>
</body>
</html>
