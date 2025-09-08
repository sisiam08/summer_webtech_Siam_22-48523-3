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
    <title>Add New Product - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <style>
        .product-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            background-size: cover;
            background-position: center;
            color: #999;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .checkbox-group {
            margin-top: 15px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        #discount-fields {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #eee;
        }
    </style>
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
            <h2>Add New Product</h2>
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

        <div class="product-form">
            <form id="product-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category_id" required>
                            <option value="">Select Category</option>
                            <!-- Categories will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="product-price">Price ($)</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock Quantity</label>
                        <input type="number" id="product-stock" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-unit">Unit</label>
                        <select id="product-unit" name="unit" required>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="g">Gram (g)</option>
                            <option value="l">Liter (L)</option>
                            <option value="ml">Milliliter (ml)</option>
                            <option value="pcs">Piece (pcs)</option>
                            <option value="pack">Pack</option>
                            <option value="box">Box</option>
                            <option value="dozen">Dozen</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" name="description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="product-image">Product Image</label>
                        <input type="file" id="product-image" name="image" accept="image/*">
                        <div id="image-preview" class="image-preview">Image Preview</div>
                    </div>
                    <div class="form-group">
                        <label for="product-status">Status</label>
                        <select id="product-status" name="is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" id="product-featured" name="is_featured" value="1">
                                Feature this product on homepage
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" id="product-discount" name="has_discount" value="1">
                                Apply discount
                            </label>
                        </div>
                        
                        <div id="discount-fields" style="display: none;">
                            <label for="discount-percent">Discount Percentage (%)</label>
                            <input type="number" id="discount-percent" name="discount_percent" min="1" max="99" value="10">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="products.php" class="btn secondary">Cancel</a>
                    <button type="submit" class="btn primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load categories
            fetch('get_categories.php')
                .then(response => response.json())
                .then(categories => {
                    const categorySelect = document.getElementById('product-category');
                    
                    categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading categories:', error));
            
            // Image preview
            const imageInput = document.getElementById('product-image');
            const imagePreview = document.getElementById('image-preview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.style.backgroundImage = `url('${e.target.result}')`;
                        imagePreview.textContent = '';
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imagePreview.style.backgroundImage = '';
                    imagePreview.textContent = 'Image Preview';
                }
            });
            
            // Toggle discount fields
            const discountCheckbox = document.getElementById('product-discount');
            const discountFields = document.getElementById('discount-fields');
            
            discountCheckbox.addEventListener('change', function() {
                discountFields.style.display = this.checked ? 'block' : 'none';
            });
            
            // Form submission
            const productForm = document.getElementById('product-form');
            
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('save_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product added successfully!');
                        window.location.href = 'products.php';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to add product'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the product. Please try again.');
                });
            });
            
            // Logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        });
    </script>
</body>
</html>
