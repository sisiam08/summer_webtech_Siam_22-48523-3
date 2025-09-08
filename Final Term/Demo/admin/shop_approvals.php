<?php
// Start session if not already started
session_start();

// Include database connection and functions
require_once '../php/db_connection.php';
require_once '../php/functions.php';
require_once '../includes/session.php';

// Check if user is logged in and is an admin
if (!isAdmin()) {
    header('Location: login.html');
    exit;
}

// Get pending shop applications
$sql = "SELECT v.*, u.name as owner_name, u.email, u.phone 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.is_approved = 0 
        ORDER BY v.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved shop applications
$sql = "SELECT v.*, u.name as owner_name, u.email, u.phone 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.is_approved = 1 
        ORDER BY v.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$approvedShops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Approvals - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin-panel">
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Page Content -->
            <div class="admin-main">
                <div class="admin-page-header">
                    <h2>Shop Approvals</h2>
                    <p>Manage shop owner applications</p>
                </div>
                
                <!-- Pending Applications -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Pending Applications</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($pendingShops)): ?>
                            <p class="empty-state">No pending applications found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Shop Name</th>
                                            <th>Owner</th>
                                            <th>Contact</th>
                                            <th>Application Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingShops as $shop): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shop['owner_name']); ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($shop['email']); ?></div>
                                                    <div><?php echo htmlspecialchars($shop['phone'] ?? 'N/A'); ?></div>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($shop['created_at'])); ?></td>
                                                <td class="actions">
                                                    <button class="btn view-details" data-id="<?php echo $shop['id']; ?>">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                    <button class="btn approve-shop" data-id="<?php echo $shop['id']; ?>" data-user-id="<?php echo $shop['user_id']; ?>">
                                                        <i class="material-icons">check_circle</i>
                                                    </button>
                                                    <button class="btn reject-shop" data-id="<?php echo $shop['id']; ?>" data-user-id="<?php echo $shop['user_id']; ?>">
                                                        <i class="material-icons">cancel</i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Approved Applications -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Approved Shops</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($approvedShops)): ?>
                            <p class="empty-state">No approved shops found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Shop Name</th>
                                            <th>Owner</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approvedShops as $shop): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shop['owner_name']); ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($shop['email']); ?></div>
                                                    <div><?php echo htmlspecialchars($shop['phone'] ?? 'N/A'); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $shop['is_active'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $shop['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="actions">
                                                    <button class="btn view-details" data-id="<?php echo $shop['id']; ?>">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                    <?php if ($shop['is_active']): ?>
                                                        <button class="btn deactivate-shop" data-id="<?php echo $shop['id']; ?>" data-user-id="<?php echo $shop['user_id']; ?>">
                                                            <i class="material-icons">block</i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn activate-shop" data-id="<?php echo $shop['id']; ?>" data-user-id="<?php echo $shop['user_id']; ?>">
                                                            <i class="material-icons">check_circle</i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Shop Details Modal -->
    <div class="admin-modal" id="shop-details-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Shop Details</h3>
                <span class="admin-modal-close">&times;</span>
            </div>
            <div class="admin-modal-body" id="shop-details-content">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="admin-modal-footer">
                <button class="admin-btn admin-btn-secondary admin-modal-close-btn">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if admin is logged in
            fetch('../php/check_admin.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.is_admin) {
                        window.location.href = 'login.html';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = 'login.html';
                });
            
            // Modal elements
            const modal = document.getElementById('shop-details-modal');
            const closeButtons = document.querySelectorAll('.admin-modal-close, .admin-modal-close-btn');
            
            // Close modal
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // View shop details
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    const shopId = this.getAttribute('data-id');
                    fetchShopDetails(shopId);
                });
            });
            
            // Approve shop
            document.querySelectorAll('.approve-shop').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to approve this shop?')) {
                        const shopId = this.getAttribute('data-id');
                        const userId = this.getAttribute('data-user-id');
                        updateShopStatus(shopId, userId, 'approve');
                    }
                });
            });
            
            // Reject shop
            document.querySelectorAll('.reject-shop').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to reject this shop application?')) {
                        const shopId = this.getAttribute('data-id');
                        const userId = this.getAttribute('data-user-id');
                        updateShopStatus(shopId, userId, 'reject');
                    }
                });
            });
            
            // Activate shop
            document.querySelectorAll('.activate-shop').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to activate this shop?')) {
                        const shopId = this.getAttribute('data-id');
                        const userId = this.getAttribute('data-user-id');
                        updateShopStatus(shopId, userId, 'activate');
                    }
                });
            });
            
            // Deactivate shop
            document.querySelectorAll('.deactivate-shop').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to deactivate this shop?')) {
                        const shopId = this.getAttribute('data-id');
                        const userId = this.getAttribute('data-user-id');
                        updateShopStatus(shopId, userId, 'deactivate');
                    }
                });
            });
            
            // Function to fetch shop details
            function fetchShopDetails(shopId) {
                fetch(`../php/admin/get_shop_details.php?id=${shopId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert('Error: ' + data.error);
                            return;
                        }
                        
                        // Format shop details
                        const shop = data.shop;
                        const created = new Date(shop.created_at).toLocaleString();
                        const updated = new Date(shop.updated_at).toLocaleString();
                        
                        let statusBadge = '';
                        if (shop.is_approved) {
                            statusBadge = shop.is_active ? 
                                '<span class="badge success">Active</span>' : 
                                '<span class="badge danger">Inactive</span>';
                        } else {
                            statusBadge = '<span class="badge warning">Pending Approval</span>';
                        }
                        
                        // Populate modal content
                        document.getElementById('shop-details-content').innerHTML = `
                            <div class="shop-details">
                                <div class="shop-info-grid">
                                    <div class="shop-info-section">
                                        <h4>Shop Information</h4>
                                        <p><strong>Name:</strong> ${shop.shop_name}</p>
                                        <p><strong>Description:</strong> ${shop.description || 'N/A'}</p>
                                        <p><strong>Commission Rate:</strong> ${shop.commission_rate}%</p>
                                        <p><strong>Status:</strong> ${statusBadge}</p>
                                        <p><strong>Created:</strong> ${created}</p>
                                        <p><strong>Last Updated:</strong> ${updated}</p>
                                    </div>
                                    <div class="shop-info-section">
                                        <h4>Owner Information</h4>
                                        <p><strong>Name:</strong> ${shop.owner_name}</p>
                                        <p><strong>Email:</strong> ${shop.email}</p>
                                        <p><strong>Phone:</strong> ${shop.phone || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Show modal
                        modal.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching shop details:', error);
                        alert('An error occurred while fetching shop details. Please try again.');
                    });
            }
            
            // Function to update shop status
            function updateShopStatus(shopId, userId, action) {
                fetch('../php/admin/update_shop_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${shopId}&user_id=${userId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error updating shop status:', error);
                    alert('An error occurred while updating shop status. Please try again.');
                });
            }
        });
    </script>
</body>
</html>
