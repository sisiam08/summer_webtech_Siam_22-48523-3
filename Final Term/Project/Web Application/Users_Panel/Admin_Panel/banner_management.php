<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database for pending counts
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login page if not authenticated
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get admin user data
$adminUser = getCurrentUser();
$adminName = $adminUser ? $adminUser['name'] : 'Admin';

// Get pending counts for badges
$conn = connectDB();

// Get pending shop owners count
$sql = "SELECT COUNT(*) as count FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwnersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending delivery men count
$sql = "SELECT COUNT(*) as count FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $link_url = trim($_POST['link_url']);
                $button_text = trim($_POST['button_text']);
                $display_order = (int)$_POST['display_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Handle image upload
                $imagePath = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../Uploads/banners/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $imageName = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $imageExtension;
                    $imageDestination = $uploadDir . $imageName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $imageDestination)) {
                        $imagePath = 'Uploads/banners/' . $imageName;
                    }
                }
                
                if (!empty($title) && !empty($imagePath)) {
                    $stmt = $conn->prepare("INSERT INTO banners (title, subtitle, image_path, link_url, button_text, display_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$title, $subtitle, $imagePath, $link_url, $button_text, $display_order, $is_active]);
                    $_SESSION['success_message'] = 'Banner added successfully!';
                } else {
                    $_SESSION['error_message'] = 'Please fill all required fields and upload an image.';
                }
                break;
                
            case 'edit':
                $bannerId = $_POST['banner_id'];
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $link_url = trim($_POST['link_url']);
                $button_text = trim($_POST['button_text']);
                $display_order = (int)$_POST['display_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Handle image upload if new image is provided
                $imagePath = $_POST['current_image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../Uploads/banners/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $imageName = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $imageExtension;
                    $imageDestination = $uploadDir . $imageName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $imageDestination)) {
                        // Delete old image if it exists
                        if (!empty($_POST['current_image'])) {
                            $oldImagePath = __DIR__ . '/../../' . $_POST['current_image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $imagePath = 'Uploads/banners/' . $imageName;
                    }
                }
                
                if (!empty($title)) {
                    $stmt = $conn->prepare("UPDATE banners SET title = ?, subtitle = ?, image_path = ?, link_url = ?, button_text = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $subtitle, $imagePath, $link_url, $button_text, $display_order, $is_active, $bannerId]);
                    $_SESSION['success_message'] = 'Banner updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Please fill all required fields.';
                }
                break;
                
            case 'delete':
                $bannerId = $_POST['banner_id'];
                
                // Get banner image to delete file
                $stmt = $conn->prepare("SELECT image_path FROM banners WHERE id = ?");
                $stmt->execute([$bannerId]);
                $banner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($banner) {
                    // Delete image file
                    $imagePath = __DIR__ . '/../../' . $banner['image_path'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    // Delete banner from database
                    $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
                    $stmt->execute([$bannerId]);
                    $_SESSION['success_message'] = 'Banner deleted successfully!';
                } else {
                    $_SESSION['error_message'] = 'Banner not found.';
                }
                break;
        }
    }
    
    header("Location: banner_management.php");
    exit;
}

// Fetch all banners
$stmt = $conn->query("SELECT * FROM banners ORDER BY display_order ASC, created_at DESC");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create arrays for compatibility with existing code
$pendingShopOwners = array_fill(0, $pendingShopOwnersCount, null);
$pendingDeliveryMen = array_fill(0, $pendingDeliveryMenCount, null);

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin">
    <div class="admin-sidebar">
        <div class="brand">
            Grocery Admin
        </div>
        <div class="menu">
            <a href="admin_index.php" class="menu-item">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="banner_management.php" class="menu-item active">
                <i class="material-icons">view_carousel</i> Banner Management
            </a>
            <a href="shop_owners.php" class="menu-item">
                <i class="material-icons">store</i> Shop Owners
                <?php if ($pendingShopOwnersCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingShopOwnersCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="delivery_men.php" class="menu-item">
                <i class="material-icons">delivery_dining</i> Delivery Men
                <?php if ($pendingDeliveryMenCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingDeliveryMenCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="employees.php" class="menu-item">
                <i class="material-icons">people</i> Employees
            </a>
            <a href="customers.php" class="menu-item">
                <i class="material-icons">person</i> Customers
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Banner Management</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="admin_profile.php">Profile</a>
                        <a href="../../Authentication/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin: 0 0 5px 0; color: #333; font-size: 1.8em;">Banner Management</h1>
                <p style="margin: 0; color: #666;">Manage homepage banners and promotional content</p>
            </div>
            <button class="btn btn-primary" onclick="openAddBannerModal()" style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: linear-gradient(135deg, #4caf50, #45a049); color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="material-icons">add</i>
                Add New Banner
            </button>
        </div>

        <!-- Banners Grid -->
        <div class="banners-grid">
            <?php if (empty($banners)): ?>
                <div class="no-banners">
                    <i class="material-icons">view_carousel</i>
                    <h3>No Banners Found</h3>
                    <p>Add your first banner to get started with homepage promotions.</p>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $banner): ?>
                    <div class="banner-card">
                        <div class="banner-preview">
                            <img src="../../<?php echo htmlspecialchars($banner['image_path']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                            <div class="banner-status">
                                <span class="status-badge <?php echo $banner['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="banner-info">
                            <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <p><?php echo htmlspecialchars($banner['subtitle'] ?? ''); ?></p>
                            <div class="banner-meta">
                                <span>Order: <?php echo $banner['display_order']; ?></span>
                                <span>Created: <?php echo date('M d, Y', strtotime($banner['created_at'])); ?></span>
                                <?php if ($banner['updated_at']): ?>
                                <span>Updated: <?php echo date('M d, Y', strtotime($banner['updated_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="banner-actions">
                            <button class="btn btn-success" onclick="editBanner(<?php echo $banner['id']; ?>)">
                                <i class="material-icons">edit</i> Edit
                            </button>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="material-icons">delete</i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Banner Modal -->
    <div id="addBannerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Banner</h3>
                <span class="close" onclick="closeModal('addBannerModal')">&times;</span>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="title">Banner Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="subtitle">Subtitle</label>
                    <input type="text" id="subtitle" name="subtitle">
                </div>
                
                <div class="form-group">
                    <label for="link_url">Link URL</label>
                    <input type="text" id="link_url" name="link_url" placeholder="../Customer_Panel/products.php or https://example.com">
                    <small style="color: #666; font-size: 12px;">Enter relative URL (../Customer_Panel/products.php) or full URL (https://example.com)</small>
                </div>
                
                <div class="form-group">
                    <label for="button_text">Button Text</label>
                    <input type="text" id="button_text" name="button_text" placeholder="Shop Now">
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="1" min="1">
                </div>
                
                <div class="form-group">
                    <label for="image">Banner Image *</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <small>Recommended size: 1200x400 pixels</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addBannerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Banner</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Banner Modal -->
    <div id="editBannerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Banner</h3>
                <span class="close" onclick="closeModal('editBannerModal')">&times;</span>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="banner_id" id="editBannerId">
                <input type="hidden" name="current_image" id="editCurrentImage">
                
                <div class="form-group">
                    <label for="editTitle">Banner Title *</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="editSubtitle">Subtitle</label>
                    <input type="text" id="editSubtitle" name="subtitle">
                </div>
                
                <div class="form-group">
                    <label for="editLinkUrl">Link URL</label>
                    <input type="text" id="editLinkUrl" name="link_url" placeholder="../Customer_Panel/products.php or https://example.com">
                    <small style="color: #666; font-size: 12px;">Enter relative URL (../Customer_Panel/products.php) or full URL (https://example.com)</small>
                </div>
                
                <div class="form-group">
                    <label for="editButtonText">Button Text</label>
                    <input type="text" id="editButtonText" name="button_text" placeholder="Shop Now">
                </div>
                
                <div class="form-group">
                    <label for="editDisplayOrder">Display Order</label>
                    <input type="number" id="editDisplayOrder" name="display_order" min="1">
                </div>
                
                <div class="form-group">
                    <label for="editImage">Banner Image</label>
                    <input type="file" id="editImage" name="image" accept="image/*">
                    <small>Leave empty to keep current image. Recommended size: 1200x400 pixels</small>
                    <div id="currentImage"></div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editIsActive" name="is_active" value="1">
                        Active
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editBannerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Banner</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddBannerModal() {
            document.getElementById('addBannerModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editBanner(bannerId) {
            // Fetch banner data and populate edit modal
            const banners = <?php echo json_encode($banners); ?>;
            const banner = banners.find(b => b.id == bannerId);
            
            if (banner) {
                document.getElementById('editBannerId').value = banner.id;
                document.getElementById('editTitle').value = banner.title;
                document.getElementById('editSubtitle').value = banner.subtitle || '';
                document.getElementById('editLinkUrl').value = banner.link_url || '';
                document.getElementById('editButtonText').value = banner.button_text || '';
                document.getElementById('editDisplayOrder').value = banner.display_order || 1;
                document.getElementById('editIsActive').checked = banner.is_active == 1;
                document.getElementById('editCurrentImage').value = banner.image_path;
                
                // Show current image
                const currentImageDiv = document.getElementById('currentImage');
                currentImageDiv.innerHTML = `
                    <div style="margin-top: 10px;">
                        <strong>Current Image:</strong><br>
                        <img src="../../${banner.image_path}" alt="${banner.title}" style="max-width: 200px; height: auto; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                `;
                
                document.getElementById('editBannerModal').style.display = 'block';
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addBannerModal');
            const editModal = document.getElementById('editBannerModal');
            
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        };
    </script>
</body>
</html>