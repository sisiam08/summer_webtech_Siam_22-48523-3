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
    <title>Products - Shop Owner Dashboard</title>
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
            <a href="products.php" class="menu-item active">
                <i class="material-icons">inventory</i> <span>Products</span>
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> <span>Orders</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="material-icons">bar_chart</i> <span>Reports</span>
            </a>
            <a href="profile.php" class="menu-item">
                <i class="material-icons">store</i> <span>Shop Profile</span>
            </a>
        </div>
    </div>

    <div class="shop-owner-content">
        <div class="shop-owner-header">
            <h2>Products</h2>
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

        <div class="action-bar">
            <button class="btn primary" id="add-product-btn">
                <i class="material-icons">add</i> Add New Product
            </button>
        </div>

        <div class="products-container">
            <div class="card">
                <div class="card-header">
                    <h3>Your Products</h3>
                    <div class="search-container">
                        <input type="text" id="product-search" placeholder="Search products...">
                        <i class="material-icons">search</i>
                    </div>
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

    <script>
        // Function to load products
        function loadProducts() {
            fetch('get_products.php')
                .then(response => response.json())
                .then(products => {
                    const tableBody = document.getElementById('products-table');
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
                        
                        // Determine status class
                        let statusClass = product.is_active ? 'success' : 'danger';
                        let statusText = product.is_active ? 'Active' : 'Inactive';
                        
                        row.innerHTML = `
                            <td>${product.id}</td>
                            <td><img src="../uploads/products/${product.image || 'no-image.jpg'}" alt="${product.name}" class="thumbnail"></td>
                            <td>${product.name}</td>
                            <td>${product.category_name}</td>
                            <td>$${parseFloat(product.price).toFixed(2)}</td>
                            <td><span class="badge ${stockClass}">${product.stock}</span></td>
                            <td><span class="badge ${statusClass}">${statusText}</span></td>
                            <td>
                                <a href="edit-product.php?id=${product.id}" class="btn icon primary">
                                    <i class="material-icons">edit</i>
                                </a>
                                <button class="btn icon danger delete-product" data-id="${product.id}">
                                    <i class="material-icons">delete</i>
                                </button>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                    
                    // Add event listeners to delete buttons
                    document.querySelectorAll('.delete-product').forEach(button => {
                        button.addEventListener('click', function() {
                            const productId = this.getAttribute('data-id');
                            if (confirm('Are you sure you want to delete this product?')) {
                                deleteProduct(productId);
                            }
                        });
                    });
                })
                .catch(error => console.error('Error loading products:', error));
        }
        
        // Function to delete a product
        function deleteProduct(productId) {
            fetch('delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload products after successful deletion
                    loadProducts();
                } else {
                    alert('Error deleting product: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        });
    </script>
</body>
</html>
