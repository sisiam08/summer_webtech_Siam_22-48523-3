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
    <title>Add New Product - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="shop_owner_modern.css">
    <link rel="stylesheet" href="notification-system.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <h1>Add New Product</h1><br><br>
                    <p>Create a new product listing for your shop</p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Form
                    </button>
                    <button class="action-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="content-card">
                <div class="card-header">
                    <div class="header-info">
                        <h3>Product Information</h3><br><br>
                        <p>Fill in the details below to add a new product to your shop</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-secondary" onclick="previewProduct()">
                            <i class="fas fa-eye"></i>
                            Preview
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <form id="product-form" class="modern-form" enctype="multipart/form-data">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h4>Basic Information</h4>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="product-name">Product Name *</label>
                                    <input type="text" id="product-name" name="product_name" class="form-input" required placeholder="Enter product name">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="category">Category *</label>
                                    <select id="category" name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <!-- Categories will be loaded dynamically -->
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" rows="4" class="form-textarea" placeholder="Describe your product..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="form-section">
                            <h4>Pricing & Inventory</h4>
                            
                            <div class="form-row">
                                <div class="form-group third-width">
                                    <label for="price">Price (৳) *</label>
                                    <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" required placeholder="0.00">
                                </div>
                                <div class="form-group third-width">
                                    <label for="stock">Stock Quantity *</label>
                                    <input type="number" id="stock" name="stock" class="form-input" min="0" required placeholder="0">
                                </div>
                                <div class="form-group third-width">
                                    <label for="unit">Unit *</label>
                                    <select id="unit" name="unit" class="form-select" required>
                                        <option value="">Select Unit</option>
                                        <option value="piece">Piece</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="gram">Gram (g)</option>
                                        <option value="liter">Liter (L)</option>
                                        <option value="ml">Milliliter (ml)</option>
                                        <option value="pack">Pack</option>
                                        <option value="bottle">Bottle</option>
                                        <option value="box">Box</option>
                                        <option value="dozen">Dozen</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="cost">Cost Price (৳)</label>
                                    <input type="number" id="cost" name="cost" class="form-input" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="form-group half-width">
                                    <label for="discount">Discount (%)</label>
                                    <input type="number" id="discount" name="discount" class="form-input" min="0" max="100" step="0.01" placeholder="0">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="min-stock">Minimum Stock Alert</label>
                                    <input type="number" id="min-stock" name="min_stock" class="form-input" min="0" placeholder="5">
                                </div>
                            </div>
                        </div>

                        <!-- Product Images Section -->
                        <div class="form-section">
                            <h4>Product Images</h4>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="product-image">Product Image</label>
                                    <input type="file" id="product-image" name="image" class="form-file" accept="image/*" onchange="previewImage(this)">
                                    <small class="form-help">Recommended size: 800x600px. Max size: 2MB. Formats: JPG, PNG, WebP</small>
                                    <div id="image-preview" class="image-preview-container">
                                        <div class="image-placeholder">
                                            <i class="fas fa-image"></i>
                                            <p>No image selected</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Features Section -->
                        <div class="form-section">
                            <h4>Product Features</h4>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="featured" name="is_featured" value="1">
                                            <span class="checkmark"></span>
                                            Featured Product
                                        </label>
                                        <small>Feature this product on homepage and category pages</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="active" name="is_active" value="1" checked>
                                            <span class="checkmark"></span>
                                            Active Product
                                        </label>
                                        <small>Product will be visible to customers</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="resetForm()">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to preview uploaded image
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">`;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = `
                    <div class="image-placeholder">
                        <i class="fas fa-image"></i>
                        <p>No image selected</p>
                    </div>
                `;
            }
        }

        // Function to reset form
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
                document.getElementById('product-form').reset();
                previewImage(document.getElementById('product-image'));
            }
        }

        // Function to preview product
        function previewProduct() {
            const formData = new FormData(document.getElementById('product-form'));
            const productData = {
                name: formData.get('product_name'),
                category: formData.get('category'),
                price: formData.get('price'),
                description: formData.get('description')
            };
            
            if (!productData.name || !productData.price) {
                showNotification('Please fill in required fields (Name and Price)', 'error');
                return;
            }
            
            // Show enhanced product preview
            showModal(
                `<strong>Name:</strong> ${productData.name}<br>
                 <strong>Category:</strong> ${productData.category}<br>
                 <strong>Price:</strong> ৳${productData.price}<br>
                 <strong>Description:</strong> ${productData.description || 'No description'}`, 
                'info', 
                {
                    title: 'Product Preview',
                    confirmText: 'Close'
                }
            );
        }

        // Function to validate form
        function validateForm() {
            const name = document.getElementById('product-name').value.trim();
            const category = document.getElementById('category').value;
            const price = document.getElementById('price').value;
            const stock = document.getElementById('stock').value;
            const unit = document.getElementById('unit').value;
            
            if (!name) {
                showNotification('Product name is required', 'error');
                return false;
            }
            
            if (!category) {
                showNotification('Please select a category', 'error');
                return false;
            }
            
            if (!price || price <= 0) {
                showNotification('Valid price is required', 'error');
                return false;
            }
            
            if (!stock || stock < 0) {
                showNotification('Valid stock quantity is required', 'error');
                return false;
            }
            
            if (!unit) {
                showNotification('Please select a unit', 'error');
                return false;
            }
            
            return true;
        }

        // Form submission handler
        document.addEventListener('DOMContentLoaded', function() {
            // Load categories dynamically
            const categorySelect = document.getElementById('category');
            if (categorySelect) {
                fetch('get_categories.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.categories) {
                            data.categories.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category.id;
                                option.textContent = category.name;
                                categorySelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading categories:', error));
            }
            
            // Image preview functionality
            const imageInput = document.getElementById('product-image');
            const imagePreview = document.getElementById('image-preview');
            
            if (imageInput && imagePreview) {
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
            }
            
            // Toggle discount fields
            const discountCheckbox = document.getElementById('product-discount');
            const discountFields = document.getElementById('discount-fields');
            
            if (discountCheckbox && discountFields) {
                discountCheckbox.addEventListener('change', function() {
                    discountFields.style.display = this.checked ? 'block' : 'none';
                });
            }
            
            const form = document.getElementById('product-form');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return;
                }
                
                const formData = new FormData(form);
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Product...';
                submitBtn.disabled = true;
                
                fetch('save_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Product added successfully!');
                        setTimeout(() => {
                            window.location.href = 'shop_products.php';
                        }, 2000);
                    } else {
                        showNotification('Error adding product: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error adding product. Please try again.', 'error');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>

