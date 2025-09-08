<?php
// Start session
session_start();

// Include necessary files
require_once '../php/functions.php';
require_once '../php/admin/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect to dashboard with error message
$_SESSION['error_message'] = "Product management has been disabled in this version.";
header('Location: index.php');
exit;
?>

// Get current admin information
$adminId = $_SESSION['admin_id'];
$admin = getCurrentUser() ?? ['name' => 'Admin'];

// Handle product actions (only flagging/unflagging inappropriate products)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($_POST['action'] === 'flag' && $productId > 0) {
        // Flag product as inappropriate
        $reason = $_POST['reason'] ?? 'Flagged by admin';
        $comments = $_POST['comments'] ?? '';
        flagProduct($productId, $reason, $comments);
        $successMessage = "Product has been flagged and vendor notified.";
    } elseif ($_POST['action'] === 'unflag' && $productId > 0) {
        // Remove flag from product
        unflagProduct($productId);
        $successMessage = "Product flag has been removed.";
    }
}

// Pagination settings
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$vendorId = isset($_GET['vendor']) ? intval($_GET['vendor']) : 0;
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get products with vendor and category information
$products = getProductsForAdmin($vendorId, $categoryId, $status, $search, $limit, $offset);
$totalProducts = countProductsForAdmin($vendorId, $categoryId, $status, $search);
$totalPages = ceil($totalProducts / $limit);

// Get all vendors for filter dropdown
$vendors = getAllVendors();

// Get all categories for filter dropdown
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Oversight - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin">
    <?php include('includes/sidebar.php'); ?>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Product Oversight</h2>
            <div class="admin-breadcrumb">
                <a href="index.php">Dashboard</a> &gt; Product Oversight
            </div>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($admin['name']); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <?php endif; ?>

        <div class="admin-controls">
            <div class="admin-search">
                <form action="products.php" method="get">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="vendor">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php echo $vendorId == $vendor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendor['shop_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="flagged" <?php echo $status === 'flagged' ? 'selected' : ''; ?>>Flagged</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="products.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>

        <div class="product-stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p><?php echo number_format($totalProducts); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Active Products</h3>
                <p><?php echo number_format(countProducts('', 0, 0, 'active')); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Flagged Products</h3>
                <p><?php echo number_format(countProducts('', 0, 0, 'flagged')); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Product Categories</h3>
                <p><?php echo count($categories); ?></p>
            </div>
        </div>

        <div class="admin-explanation">
            <h3>Product Oversight</h3>
            <p>As an administrator, you can monitor products across all vendors, but cannot directly add or edit products. 
            Product management is the responsibility of individual vendors. You can flag inappropriate products or review 
            products that have been reported by users.</p>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No products found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                <?php else: ?>
                                <div class="product-thumbnail no-image">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <a href="vendor_details.php?id=<?php echo $product['vendor_id']; ?>">
                                    <?php echo htmlspecialchars($product['vendor_name'] ?? 'Unknown'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                        <i class="material-icons">visibility</i>
                                    </a>
                                    
                                    <?php if ($product['status'] === 'flagged'): ?>
                                    <form action="products.php" method="post" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="unflag">
                                        <button type="submit" class="btn btn-sm btn-success" title="Remove Flag">
                                            <i class="material-icons">check_circle</i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-warning flag-product-btn" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            title="Flag Product">
                                        <i class="material-icons">flag</i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="vendor_products.php?vendor_id=<?php echo $product['vendor_id']; ?>" class="btn btn-sm btn-secondary" title="View All Products from this Vendor">
                                        <i class="material-icons">store</i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&vendor=<?php echo $vendorId; ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" class="page-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&vendor=<?php echo $vendorId; ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&vendor=<?php echo $vendorId; ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flag Product Modal -->
    <div id="flagProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Flag Product</h2>
            <p>You are about to flag <span id="flagProductName"></span> as inappropriate. This will notify the vendor and temporarily mark the product for review.</p>
            
            <form action="products.php" method="post">
                <input type="hidden" name="product_id" id="flagProductId">
                <input type="hidden" name="action" value="flag">
                
                <div class="form-group">
                    <label for="flagReason">Reason for flagging:</label>
                    <select name="reason" id="flagReason" required>
                        <option value="">Select a reason</option>
                        <option value="Inappropriate content">Inappropriate content</option>
                        <option value="Misleading information">Misleading information</option>
                        <option value="Price gouging">Price gouging</option>
                        <option value="Prohibited item">Prohibited item</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="flagComments">Additional comments:</label>
                    <textarea name="comments" id="flagComments" rows="3"></textarea>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary cancel-flag">Cancel</button>
                    <button type="submit" class="btn btn-warning">Flag Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Flag product modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('flagProductModal');
            const flagBtns = document.querySelectorAll('.flag-product-btn');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.querySelector('.cancel-flag');
            const productNameElem = document.getElementById('flagProductName');
            const productIdInput = document.getElementById('flagProductId');
            
            // Open modal when flag button is clicked
            flagBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    
                    productNameElem.textContent = productName;
                    productIdInput.value = productId;
                    
                    modal.style.display = 'block';
                });
            });
            
            // Close modal when X is clicked
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when Cancel is clicked
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
                <i class="material-icons">store</i> Vendors
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Products</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="../php/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-actions">
            <button class="btn btn-primary" id="add-product-btn">
                <i class="material-icons">add</i> Add Product
            </button>
            <div class="search-bar">
                <input type="text" id="product-search" placeholder="Search products...">
                <i class="material-icons">search</i>
            </div>
        </div>

        <div class="content-wrapper">
            <table class="data-table" id="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="no-data">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="status <?php echo $product['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($product['status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="icon-btn edit-btn" data-id="<?php echo $product['id']; ?>">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="icon-btn delete-btn" data-id="<?php echo $product['id']; ?>">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add Product</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="product-form">
                    <input type="hidden" id="product-id" name="id">
                    
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product-category">Category</label>
                            <select id="product-category" name="category_id" required>
                                <option value="">Select Category</option>
                                <!-- Categories will be loaded dynamically -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product-price">Price ($)</label>
                            <input type="number" id="product-price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product-stock">Stock</label>
                            <input type="number" id="product-stock" name="stock" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="product-status">Status</label>
                            <select id="product-status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="product-image">Image URL</label>
                        <input type="text" id="product-image" name="image">
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-secondary" id="cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                <div class="form-group text-right">
                    <button type="button" class="btn btn-secondary" id="cancel-delete-btn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Product management JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const productModal = document.getElementById('product-modal');
            const confirmModal = document.getElementById('confirm-modal');
            const modalTitle = document.getElementById('modal-title');
            const productForm = document.getElementById('product-form');
            const cancelBtn = document.getElementById('cancel-btn');
            const closeButtons = document.querySelectorAll('.close');
            
            // Buttons
            const addProductBtn = document.getElementById('add-product-btn');
            const editButtons = document.querySelectorAll('.edit-btn');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            
            // Form fields
            const productId = document.getElementById('product-id');
            const productName = document.getElementById('product-name');
            const productCategory = document.getElementById('product-category');
            const productPrice = document.getElementById('product-price');
            const productStock = document.getElementById('product-stock');
            const productStatus = document.getElementById('product-status');
            const productDescription = document.getElementById('product-description');
            const productImage = document.getElementById('product-image');
            
            // Search field
            const productSearch = document.getElementById('product-search');
            
            // Variables
            let deleteProductId = null;
            
            // Load categories
            function loadCategories() {
                fetch('../php/admin/get_categories.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            productCategory.innerHTML = '<option value="">Select Category</option>';
                            data.categories.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category.id;
                                option.textContent = category.name;
                                productCategory.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading categories:', error));
            }
            
            // Show add product modal
            addProductBtn.addEventListener('click', function() {
                modalTitle.textContent = 'Add Product';
                resetForm();
                productModal.style.display = 'block';
                loadCategories();
            });
            
            // Show edit product modal
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    modalTitle.textContent = 'Edit Product';
                    loadProductData(id);
                    productModal.style.display = 'block';
                });
            });
            
            // Show delete confirmation modal
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteProductId = this.getAttribute('data-id');
                    confirmModal.style.display = 'block';
                });
            });
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    productModal.style.display = 'none';
                    confirmModal.style.display = 'none';
                });
            });
            
            // Cancel buttons
            cancelBtn.addEventListener('click', function() {
                productModal.style.display = 'none';
            });
            
            cancelDeleteBtn.addEventListener('click', function() {
                confirmModal.style.display = 'none';
            });
            
            // Confirm delete
            confirmDeleteBtn.addEventListener('click', function() {
                if (deleteProductId) {
                    deleteProduct(deleteProductId);
                }
            });
            
            // Submit form
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                saveProduct(formData);
            });
            
            // Search products
            productSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#products-table tbody tr');
                
                rows.forEach(row => {
                    const name = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const category = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || category.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Reset form
            function resetForm() {
                productForm.reset();
                productId.value = '';
            }
            
            // Load product data for editing
            function loadProductData(id) {
                fetch(`../php/admin/get_product.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.product;
                            productId.value = product.id;
                            productName.value = product.name;
                            productPrice.value = product.price;
                            productStock.value = product.stock || '';
                            productStatus.value = product.status || 'active';
                            productDescription.value = product.description || '';
                            productImage.value = product.image || '';
                            
                            // Load categories and then set selected
                            loadCategories().then(() => {
                                if (product.category_id) {
                                    productCategory.value = product.category_id;
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Error loading product data:', error));
            }
            
            // Save product (add or update)
            function saveProduct(formData) {
                const url = formData.get('id') ? '../php/admin/update_product.php' : '../php/admin/add_product.php';
                
                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and reload page to show updated data
                        productModal.style.display = 'none';
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error saving product');
                    }
                })
                .catch(error => {
                    console.error('Error saving product:', error);
                    alert('An error occurred. Please try again.');
                });
            }
            
            // Delete product
            function deleteProduct(id) {
                fetch('../php/admin/delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and reload page to show updated data
                        confirmModal.style.display = 'none';
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error deleting product');
                    }
                })
                .catch(error => {
                    console.error('Error deleting product:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    </script>
</body>
</html>
