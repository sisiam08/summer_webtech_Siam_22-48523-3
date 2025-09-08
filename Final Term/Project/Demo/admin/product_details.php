<?php
// Start session
session_start();

// Include necessary files
require_once '../php/config.php';
require_once '../php/functions.php';
require_once '../php/admin/admin_auth.php';
require_once '../php/admin/product_monitoring.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current admin information
$adminId = $_SESSION['admin_id'];
$admin = getCurrentUser() ?? ['name' => 'Admin'];

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = intval($_GET['id']);

// Get product details
$product = getProductDetailsForAdmin($productId);

// If product doesn't exist, redirect back to products page
if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle product actions (only flagging/unflagging inappropriate products)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'flag') {
        // Flag product as inappropriate
        $reason = $_POST['reason'] ?? 'Flagged by admin';
        $comments = $_POST['comments'] ?? '';
        flagProduct($productId, $reason, $comments);
        $successMessage = "Product has been flagged and vendor notified.";
    } elseif ($_POST['action'] === 'unflag') {
        // Remove flag from product
        unflagProduct($productId);
        $successMessage = "Product flag has been removed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin">
    <?php include('includes/sidebar.php'); ?>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Product Details</h2>
            <div class="admin-breadcrumb">
                <a href="index.php">Dashboard</a> &gt; 
                <a href="products.php">Products</a> &gt; 
                Product Details
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

        <div class="admin-actions">
            <a href="products.php" class="btn btn-secondary">
                <i class="material-icons">arrow_back</i> Back to Products
            </a>
            
            <a href="vendor_details.php?id=<?php echo $product['vendor_id']; ?>" class="btn btn-info">
                <i class="material-icons">store</i> View Vendor
            </a>
            
            <?php if ($product['status'] === 'flagged'): ?>
            <form action="product_details.php?id=<?php echo $productId; ?>" method="post" style="display: inline;">
                <input type="hidden" name="action" value="unflag">
                <button type="submit" class="btn btn-success">
                    <i class="material-icons">check_circle</i> Remove Flag
                </button>
            </form>
            <?php else: ?>
            <button type="button" class="btn btn-warning flag-product-btn">
                <i class="material-icons">flag</i> Flag Product
            </button>
            <?php endif; ?>
        </div>

        <div class="product-details-container">
            <div class="product-details-main">
                <div class="product-image-container">
                    <?php if (!empty($product['image'])): ?>
                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <?php else: ?>
                    <div class="product-image no-image">No Image Available</div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-meta">
                        <span class="status-badge status-<?php echo $product['status']; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                        
                        <?php if ($product['flag_count'] > 0): ?>
                        <span class="flag-badge">
                            <i class="material-icons">flag</i> Flagged
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price">
                        Price: $<?php echo number_format($product['price'], 2); ?>
                    </div>
                    
                    <div class="product-vendor">
                        Vendor: <a href="vendor_details.php?id=<?php echo $product['vendor_id']; ?>"><?php echo htmlspecialchars($product['vendor_name']); ?></a>
                    </div>
                    
                    <div class="product-category">
                        Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                    
                    <div class="product-stock">
                        Stock: <?php echo $product['stock']; ?> units
                    </div>
                    
                    <div class="product-dates">
                        <div>Created: <?php echo date('M d, Y h:i A', strtotime($product['created_at'])); ?></div>
                        <div>Last Updated: <?php echo date('M d, Y h:i A', strtotime($product['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="product-description">
                <h3>Description</h3>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            
            <?php if (!empty($product['flags'])): ?>
            <div class="product-flags">
                <h3>Flag History</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Flagged By</th>
                            <th>Reason</th>
                            <th>Comments</th>
                            <th>Status</th>
                            <th>Resolved By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product['flags'] as $flag): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($flag['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($flag['flagged_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($flag['reason']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($flag['comments'])); ?></td>
                            <td>
                                <?php if ($flag['resolved']): ?>
                                <span class="status-badge status-resolved">Resolved</span>
                                <?php else: ?>
                                <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($flag['resolved']): ?>
                                <?php echo htmlspecialchars($flag['resolved_by_name']); ?>
                                <div class="small"><?php echo date('M d, Y', strtotime($flag['resolved_at'])); ?></div>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="vendor-info-box">
                <h3>Vendor Information</h3>
                <div class="vendor-details">
                    <div class="vendor-name">
                        <strong>Shop Name:</strong> <?php echo htmlspecialchars($product['vendor_name']); ?>
                    </div>
                    <div class="vendor-owner">
                        <strong>Owner:</strong> <?php echo htmlspecialchars($product['vendor_owner_name']); ?>
                    </div>
                    <div class="vendor-email">
                        <strong>Contact:</strong> <?php echo htmlspecialchars($product['vendor_email']); ?>
                    </div>
                    <a href="vendor_details.php?id=<?php echo $product['vendor_id']; ?>" class="btn btn-sm btn-info">
                        View Full Vendor Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flag Product Modal -->
    <div id="flagProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Flag Product</h2>
            <p>You are about to flag "<?php echo htmlspecialchars($product['name']); ?>" as inappropriate. This will notify the vendor and temporarily mark the product for review.</p>
            
            <form action="product_details.php?id=<?php echo $productId; ?>" method="post">
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
            const flagBtn = document.querySelector('.flag-product-btn');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.querySelector('.cancel-flag');
            
            // Open modal when flag button is clicked
            if (flagBtn) {
                flagBtn.addEventListener('click', function() {
                    modal.style.display = 'block';
                });
            }
            
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
