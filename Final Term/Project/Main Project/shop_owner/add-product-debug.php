<?php
// Start session
session_start();

// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Include required files
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Add Product - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <style>
        .product-form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .checkbox-group {
            margin-top: 10px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .discount-fields {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }
        
        .image-preview {
            margin-top: 10px;
            border: 1px dashed #ddd;
            padding: 20px;
            text-align: center;
            color: #888;
            border-radius: 4px;
        }
        
        .debug-panel {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .debug-panel h3 {
            margin-top: 0;
            color: #333;
        }
        
        .debug-panel pre {
            background: #f1f1f1;
            padding: 10px;
            overflow: auto;
            max-height: 300px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Shop Owner</h2>
            </div>
            <nav class="menu">
                <a href="index.php"><i class="material-icons">dashboard</i> Dashboard</a>
                <a href="products.php" class="active"><i class="material-icons">inventory_2</i> Products</a>
                <a href="orders.php"><i class="material-icons">shopping_cart</i> Orders</a>
                <a href="profile.php"><i class="material-icons">person</i> Profile</a>
                <a href="#" id="logout-btn"><i class="material-icons">exit_to_app</i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h1>Add New Product</h1>
                    <p>Create a new product to sell in your shop</p>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                </div>
            </div>
            
            <div class="content">
                <div class="debug-panel">
                    <h3>Debug Information</h3>
                    <h4>Session Data:</h4>
                    <pre><?php print_r($_SESSION); ?></pre>
                    
                    <h4>Database Connection:</h4>
                    <pre><?php 
                        echo "Connected: " . ($conn ? "Yes" : "No") . "\n";
                        echo "Error: " . ($conn->connect_error ?? "None");
                    ?></pre>
                </div>
                
                <form id="product-form" class="product-form">
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category_id" required>
                            <?php
                            // Get categories from database
                            $query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
                            $result = $conn->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($category = $result->fetch_assoc()) {
                                    echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                }
                            } else {
                                echo "<option value=''>No categories found</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Price</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock</label>
                        <input type="number" id="product-stock" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-unit">Unit</label>
                        <select id="product-unit" name="unit" required>
                            <option value="piece">Piece</option>
                            <option value="kg">Kilogram</option>
                            <option value="g">Gram</option>
                            <option value="lb">Pound</option>
                            <option value="oz">Ounce</option>
                            <option value="l">Liter</option>
                            <option value="ml">Milliliter</option>
                            <option value="dozen">Dozen</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="bunch">Bunch</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" name="description" required></textarea>
                    </div>
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
                        
                        <div id="discount-fields" class="discount-fields">
                            <div class="form-group">
                                <label for="discount-percent">Discount Percentage (%)</label>
                                <input type="number" id="discount-percent" name="discount_percent" min="1" max="99" value="10">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
                
                <div class="debug-panel">
                    <h3>AJAX Response</h3>
                    <pre id="ajax-response">No response yet</pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview
            const imageInput = document.getElementById('product-image');
            const imagePreview = document.getElementById('image-preview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 200px;">`;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imagePreview.innerHTML = 'Image Preview';
                }
            });
            
            // Discount fields toggle
            const discountCheckbox = document.getElementById('product-discount');
            const discountFields = document.getElementById('discount-fields');
            
            discountCheckbox.addEventListener('change', function() {
                discountFields.style.display = this.checked ? 'block' : 'none';
            });
            
            // Form submission with detailed debugging
            const productForm = document.getElementById('product-form');
            const ajaxResponse = document.getElementById('ajax-response');
            
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Display formData contents for debugging
                ajaxResponse.textContent = 'Sending form data...\n';
                for (let [key, value] of formData.entries()) {
                    ajaxResponse.textContent += `${key}: ${value}\n`;
                }
                
                fetch('save_product_debug.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    ajaxResponse.textContent += `\nResponse status: ${response.status}\n`;
                    return response.text();
                })
                .then(text => {
                    ajaxResponse.textContent += `\nRaw response:\n${text}\n\n`;
                    
                    try {
                        const data = JSON.parse(text);
                        ajaxResponse.textContent += `Parsed JSON response:\n${JSON.stringify(data, null, 2)}`;
                        
                        if (data.success) {
                            alert('Product added successfully!');
                            // Don't redirect for debugging purposes
                            // window.location.href = 'products.php';
                        } else {
                            alert('Error: ' + (data.message || 'Failed to add product'));
                        }
                    } catch (e) {
                        ajaxResponse.textContent += `Error parsing JSON: ${e.message}`;
                        alert('Error parsing server response. See debug panel for details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    ajaxResponse.textContent += `\nFetch error: ${error.message}`;
                    alert('An error occurred while adding the product. Please check the debug panel.');
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
