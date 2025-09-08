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
    <title>Edit Product - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .product-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .form-group input, .form-group select, .form-group textarea {
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
            min-height: 200px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .image-preview.empty {
            background-image: none;
        }
        .image-preview.empty::after {
            content: 'Image Preview';
            color: #aaa;
        }
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        #success-toast {
            background-color: #4CAF50;
        }
        
        #error-toast {
            background-color: #f44336;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-buttons button {
            padding: 10px 20px;
        }
    </style>
</head>
<body class="shop-owner">
    <!-- Toast notifications -->
    <div id="success-toast" class="toast"></div>
    <div id="error-toast" class="toast"></div>
    
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
            <h2>Edit Product</h2>
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

        <div class="product-form-container">
            <form id="product-form" class="product-form">
                <input type="hidden" id="product-id" name="id">
                
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
                        <label for="product-price">Price</label>
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
                        <div id="image-preview" class="image-preview empty"></div>
                        <input type="hidden" id="current-image" name="current_image">
                    </div>
                    <div class="form-group">
                        <label for="product-status">Status</label>
                        <select id="product-status" name="is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label>
                                <input type="checkbox" id="product-featured" name="is_featured" value="1">
                                Feature this product on homepage
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="product-discount" name="has_discount" value="1">
                                Apply discount
                            </label>
                        </div>
                        
                        <div id="discount-fields" style="margin-top: 10px;">
                            <label for="discount-percent">Discount Percentage</label>
                            <input type="number" id="discount-percent" name="discount_percent" min="1" max="99" step="1" value="10">
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn primary">Update Product</button>
                    <a href="products.php" class="btn secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get product ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('id');
            
            if (!productId) {
                alert('Product ID is missing');
                window.location.href = 'products.php';
                return;
            }
            
            // Set product ID in form
            document.getElementById('product-id').value = productId;
            
            // Load categories
            loadCategories();
            
            // Load product data
            loadProduct(productId);
            
            // Add event listener for image preview
            const imageInput = document.getElementById('product-image');
            const imagePreview = document.getElementById('image-preview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.style.backgroundImage = `url('${e.target.result}')`;
                        imagePreview.classList.remove('empty');
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Toggle discount fields
            const discountCheckbox = document.getElementById('product-discount');
            const discountFields = document.getElementById('discount-fields');
            
            discountCheckbox.addEventListener('change', function() {
                discountFields.style.display = this.checked ? 'block' : 'none';
            });
            
            // Handle form submission
            const productForm = document.getElementById('product-form');
            
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Create FormData object for file upload
                const formData = new FormData(this);
                
                // Send request to update product
                fetch('update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        console.error(`Server responded with status ${response.status}`);
                    }
                    return response.json().catch(error => {
                        // Handle JSON parse errors
                        console.error('JSON parse error:', error);
                        throw new Error('Invalid JSON response');
                    });
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        $('#success-toast').text('Product updated successfully!');
                        $('#success-toast').slideDown();
                        
                        setTimeout(function() {
                            $('#success-toast').slideUp();
                            window.location.href = 'products.php';
                        }, 2000);
                    } else {
                        $('#error-toast').text('Error: ' + data.message);
                        $('#error-toast').slideDown();
                        
                        setTimeout(function() {
                            $('#error-toast').slideUp();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Log form data for debugging (excluding image file for brevity)
                    const formDataDebug = {};
                    for (const [key, value] of formData.entries()) {
                        if (key !== 'image' || !value.name) {
                            formDataDebug[key] = value;
                        } else {
                            formDataDebug[key] = value.name;
                        }
                    }
                    console.log('Form data that caused error:', formDataDebug);
                    
                    $('#error-toast').text('An error occurred while updating the product. Please try again.');
                    $('#error-toast').slideDown();
                    
                    setTimeout(function() {
                        $('#error-toast').slideUp();
                    }, 3000);
                });
            });
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'logout.php';
            });
        });
        
        // Function to load categories
        function loadCategories() {
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
        }
        
        // Function to load product data
        function loadProduct(productId) {
            fetch(`get_product.php?id=${productId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server responded with status ${response.status}`);
                    }
                    return response.json();
                })
                .then(product => {
                    if (!product || !product.id) {
                        $('#error-toast').text('Product not found or could not be loaded.');
                        $('#error-toast').slideDown();
                        
                        setTimeout(function() {
                            $('#error-toast').slideUp();
                            window.location.href = 'products.php';
                        }, 3000);
                        return;
                    }
                    
                    // Fill form with product data
                    document.getElementById('product-name').value = product.name;
                    document.getElementById('product-category').value = product.category_id;
                    document.getElementById('product-price').value = product.price;
                    document.getElementById('product-stock').value = product.stock;
                    
                    // Debug the unit value
                    console.log('Unit value from database:', product.unit);
                    
                    // Select the unit option
                    const unitSelect = document.getElementById('product-unit');
                    const unitValue = product.unit ? product.unit.toLowerCase() : '';
                    
                    // First try exact match
                    unitSelect.value = unitValue;
                    
                    // If no option was selected, try to find a partial match
                    if (unitSelect.selectedIndex === -1 && unitValue) {
                        // Loop through options to find one that contains the unit value
                        for (let i = 0; i < unitSelect.options.length; i++) {
                            const option = unitSelect.options[i];
                            if (option.value.includes(unitValue) || unitValue.includes(option.value)) {
                                unitSelect.selectedIndex = i;
                                console.log('Found matching unit:', option.value);
                                break;
                            }
                        }
                    }
                    
                    // If still no match, add a new option with the unit value
                    if (unitSelect.selectedIndex === -1 && unitValue) {
                        const newOption = document.createElement('option');
                        newOption.value = unitValue;
                        newOption.textContent = unitValue.charAt(0).toUpperCase() + unitValue.slice(1) + ' (' + unitValue + ')';
                        unitSelect.appendChild(newOption);
                        unitSelect.value = unitValue;
                        console.log('Added new unit option:', unitValue);
                    }
                    
                    document.getElementById('product-description').value = product.description;
                    document.getElementById('product-status').value = product.is_active;
                    document.getElementById('product-featured').checked = product.is_featured == 1;
                    
                    // Handle discount
                    const hasDiscount = product.discounted_price !== null;
                    document.getElementById('product-discount').checked = hasDiscount;
                    document.getElementById('discount-fields').style.display = hasDiscount ? 'block' : 'none';
                    
                    if (hasDiscount) {
                        // Calculate discount percentage
                        const discountPercent = Math.round((1 - product.discounted_price / product.price) * 100);
                        document.getElementById('discount-percent').value = discountPercent;
                    }
                    
                    // Handle image
                    if (product.image) {
                        document.getElementById('current-image').value = product.image;
                        document.getElementById('image-preview').style.backgroundImage = `url('../uploads/products/${product.image}')`;
                        document.getElementById('image-preview').classList.remove('empty');
                    }
                })
                .catch(error => {
                    console.error('Error loading product:', error);
                    $('#error-toast').text('Error loading product details. Please try again.');
                    $('#error-toast').slideDown();
                    
                    setTimeout(function() {
                        $('#error-toast').slideUp();
                    }, 3000);
                });
        }
    </script>
</body>
</html>
