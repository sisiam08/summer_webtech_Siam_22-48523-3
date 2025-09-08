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

// Get all pending delivery requests
$sql = "SELECT dr.*, o.order_number, s.name as shop_name, 
               o.shipping_name, o.shipping_address, o.shipping_city, 
               o.shipping_state, o.shipping_postal_code, o.total_amount,
               o.created_at as order_date 
        FROM delivery_requests dr
        JOIN orders o ON dr.order_id = o.id
        JOIN shops s ON dr.shop_id = s.id
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all assigned deliveries
$sql = "SELECT d.*, o.order_number, o.shipping_name, o.shipping_address, 
               o.shipping_city, o.shipping_state, o.shipping_postal_code,
               o.total_amount, u.name as delivery_person_name, u.phone as delivery_person_phone
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users u ON d.delivery_person_id = u.id
        WHERE d.status IN ('assigned', 'picked_up')
        ORDER BY d.assigned_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$activeDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all delivery personnel
$sql = "SELECT u.id, u.name, u.phone, u.email,
               (SELECT COUNT(*) FROM deliveries WHERE delivery_person_id = u.id AND status IN ('assigned', 'picked_up')) as active_deliveries
        FROM users u
        WHERE u.role = 'delivery' AND u.is_active = 1
        ORDER BY active_deliveries ASC, u.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$deliveryPersonnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management - Admin Dashboard</title>
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
                    <h2>Delivery Management</h2>
                    <p>Assign and track deliveries</p>
                </div>
                
                <!-- Pending Delivery Requests -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Pending Delivery Requests</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($pendingRequests)): ?>
                            <p class="empty-state">No pending delivery requests found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Shop</th>
                                            <th>Customer</th>
                                            <th>Address</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($request['shop_name']); ?></td>
                                                <td><?php echo htmlspecialchars($request['shipping_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    echo htmlspecialchars($request['shipping_city'] . ', ' . 
                                                                           $request['shipping_state'] . ' ' . 
                                                                           $request['shipping_postal_code']); 
                                                    ?>
                                                </td>
                                                <td>$<?php echo number_format($request['total_amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['order_date'])); ?></td>
                                                <td class="actions">
                                                    <button class="btn assign-delivery" 
                                                            data-id="<?php echo $request['id']; ?>"
                                                            data-order-id="<?php echo $request['order_id']; ?>"
                                                            data-order-number="<?php echo $request['order_number']; ?>">
                                                        <i class="material-icons">person_add</i>
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
                
                <!-- Active Deliveries -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Active Deliveries</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($activeDeliveries)): ?>
                            <p class="empty-state">No active deliveries found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Address</th>
                                            <th>Delivery Person</th>
                                            <th>Status</th>
                                            <th>Assigned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeDeliveries as $delivery): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($delivery['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($delivery['shipping_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    echo htmlspecialchars($delivery['shipping_city'] . ', ' . 
                                                                           $delivery['shipping_state'] . ' ' . 
                                                                           $delivery['shipping_postal_code']); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($delivery['delivery_person_name']); ?></div>
                                                    <div class="text-muted"><?php echo htmlspecialchars($delivery['delivery_person_phone']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $delivery['status'] === 'picked_up' ? 'primary' : 'warning'; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($delivery['assigned_at'])); ?></td>
                                                <td class="actions">
                                                    <button class="btn view-delivery" data-id="<?php echo $delivery['id']; ?>">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                    <button class="btn reassign-delivery" 
                                                            data-id="<?php echo $delivery['id']; ?>"
                                                            data-order-id="<?php echo $delivery['order_id']; ?>"
                                                            data-order-number="<?php echo $delivery['order_number']; ?>">
                                                        <i class="material-icons">swap_horiz</i>
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
            </div>
        </div>
    </div>
    
    <!-- Assign Delivery Modal -->
    <div class="admin-modal" id="assign-delivery-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Assign Delivery Person</h3>
                <span class="admin-modal-close">&times;</span>
            </div>
            <div class="admin-modal-body">
                <p>Select a delivery person to assign Order #<span id="order-number-display"></span></p>
                
                <div class="form-group">
                    <label for="delivery-person">Delivery Person</label>
                    <select id="delivery-person" class="admin-form-control" required>
                        <option value="">Select Delivery Person</option>
                        <?php foreach ($deliveryPersonnel as $person): ?>
                            <option value="<?php echo $person['id']; ?>" data-active="<?php echo $person['active_deliveries']; ?>">
                                <?php echo htmlspecialchars($person['name']); ?> 
                                (<?php echo $person['active_deliveries']; ?> active)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estimated-delivery">Estimated Delivery Time</label>
                    <input type="datetime-local" id="estimated-delivery" class="admin-form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="delivery-notes">Notes (Optional)</label>
                    <textarea id="delivery-notes" class="admin-form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button class="admin-btn admin-btn-secondary admin-modal-close-btn">Cancel</button>
                <button class="admin-btn admin-btn-primary" id="confirm-assign">Assign Delivery</button>
            </div>
        </div>
    </div>
    
    <!-- Delivery Details Modal -->
    <div class="admin-modal" id="delivery-details-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Delivery Details</h3>
                <span class="admin-modal-close">&times;</span>
            </div>
            <div class="admin-modal-body" id="delivery-details-content">
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
            const assignModal = document.getElementById('assign-delivery-modal');
            const detailsModal = document.getElementById('delivery-details-modal');
            const closeButtons = document.querySelectorAll('.admin-modal-close, .admin-modal-close-btn');
            
            // Variables to store selected delivery request/order
            let selectedRequestId = null;
            let selectedOrderId = null;
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    assignModal.style.display = 'none';
                    detailsModal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === assignModal) {
                    assignModal.style.display = 'none';
                }
                if (event.target === detailsModal) {
                    detailsModal.style.display = 'none';
                }
            });
            
            // Set default estimated delivery time to 2 hours from now
            const estimatedDelivery = document.getElementById('estimated-delivery');
            const defaultDate = new Date();
            defaultDate.setHours(defaultDate.getHours() + 2);
            estimatedDelivery.value = formatDatetimeLocal(defaultDate);
            
            // Handle assign delivery buttons
            document.querySelectorAll('.assign-delivery').forEach(button => {
                button.addEventListener('click', function() {
                    selectedRequestId = this.getAttribute('data-id');
                    selectedOrderId = this.getAttribute('data-order-id');
                    const orderNumber = this.getAttribute('data-order-number');
                    
                    document.getElementById('order-number-display').textContent = orderNumber;
                    assignModal.style.display = 'block';
                });
            });
            
            // Handle reassign delivery buttons
            document.querySelectorAll('.reassign-delivery').forEach(button => {
                button.addEventListener('click', function() {
                    selectedRequestId = null; // No request ID for reassignments
                    selectedOrderId = this.getAttribute('data-order-id');
                    const orderNumber = this.getAttribute('data-order-number');
                    
                    document.getElementById('order-number-display').textContent = orderNumber;
                    assignModal.style.display = 'block';
                });
            });
            
            // Handle view delivery buttons
            document.querySelectorAll('.view-delivery').forEach(button => {
                button.addEventListener('click', function() {
                    const deliveryId = this.getAttribute('data-id');
                    fetchDeliveryDetails(deliveryId);
                });
            });
            
            // Handle confirm assign button
            document.getElementById('confirm-assign').addEventListener('click', function() {
                const deliveryPersonId = document.getElementById('delivery-person').value;
                const estimatedDelivery = document.getElementById('estimated-delivery').value;
                const notes = document.getElementById('delivery-notes').value;
                
                if (!deliveryPersonId) {
                    alert('Please select a delivery person');
                    return;
                }
                
                if (!estimatedDelivery) {
                    alert('Please set an estimated delivery time');
                    return;
                }
                
                assignDelivery(selectedRequestId, selectedOrderId, deliveryPersonId, estimatedDelivery, notes);
            });
            
            // Function to assign delivery
            function assignDelivery(requestId, orderId, deliveryPersonId, estimatedDelivery, notes) {
                fetch('../php/admin/assign_delivery.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId || ''}&order_id=${orderId}&delivery_person_id=${deliveryPersonId}&estimated_delivery=${estimatedDelivery}&notes=${notes}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        assignModal.style.display = 'none';
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error assigning delivery:', error);
                    alert('An error occurred while assigning the delivery. Please try again.');
                });
            }
            
            // Function to fetch delivery details
            function fetchDeliveryDetails(deliveryId) {
                fetch(`../php/admin/get_delivery_details.php?id=${deliveryId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert('Error: ' + data.error);
                            return;
                        }
                        
                        const delivery = data.delivery;
                        const order = data.order;
                        const items = data.items;
                        
                        // Format times
                        const assignedAt = new Date(delivery.assigned_at).toLocaleString();
                        const pickedUpAt = delivery.picked_up_at ? new Date(delivery.picked_up_at).toLocaleString() : 'Not yet';
                        const deliveredAt = delivery.delivered_at ? new Date(delivery.delivered_at).toLocaleString() : 'Not yet';
                        const estimatedDelivery = delivery.estimated_delivery ? new Date(delivery.estimated_delivery).toLocaleString() : 'Not set';
                        
                        // Create status badge
                        let statusBadge = '';
                        switch (delivery.status) {
                            case 'assigned':
                                statusBadge = '<span class="badge warning">Assigned</span>';
                                break;
                            case 'picked_up':
                                statusBadge = '<span class="badge primary">Picked Up</span>';
                                break;
                            case 'delivered':
                                statusBadge = '<span class="badge success">Delivered</span>';
                                break;
                            case 'failed':
                                statusBadge = '<span class="badge danger">Failed</span>';
                                break;
                            case 'cancelled':
                                statusBadge = '<span class="badge danger">Cancelled</span>';
                                break;
                        }
                        
                        // Format items list
                        let itemsList = '<ul class="delivery-items-list">';
                        items.forEach(item => {
                            itemsList += `
                                <li>
                                    <div class="item-name">${item.product_name}</div>
                                    <div class="item-qty">x${item.quantity}</div>
                                </li>
                            `;
                        });
                        itemsList += '</ul>';
                        
                        // Create delivery proof section if available
                        let proofSection = '';
                        if (delivery.delivery_proof) {
                            proofSection = `
                                <div class="delivery-proof">
                                    <h4>Delivery Proof</h4>
                                    <img src="../uploads/delivery_proof/${delivery.delivery_proof}" alt="Delivery Proof" class="proof-image">
                                </div>
                            `;
                        }
                        
                        // Create rating section if available
                        let ratingSection = '';
                        if (delivery.customer_rating) {
                            const stars = '★'.repeat(delivery.customer_rating) + '☆'.repeat(5 - delivery.customer_rating);
                            ratingSection = `
                                <div class="customer-rating">
                                    <h4>Customer Rating</h4>
                                    <div class="stars">${stars} (${delivery.customer_rating}/5)</div>
                                    ${delivery.customer_feedback ? `<p class="feedback">"${delivery.customer_feedback}"</p>` : ''}
                                </div>
                            `;
                        }
                        
                        // Populate modal content
                        document.getElementById('delivery-details-content').innerHTML = `
                            <div class="delivery-details">
                                <div class="delivery-header">
                                    <h4>Order #${order.order_number}</h4>
                                    <div>${statusBadge}</div>
                                </div>
                                
                                <div class="delivery-timeline">
                                    <div class="timeline-item">
                                        <span class="timeline-label">Assigned:</span>
                                        <span class="timeline-value">${assignedAt}</span>
                                    </div>
                                    <div class="timeline-item">
                                        <span class="timeline-label">Picked Up:</span>
                                        <span class="timeline-value">${pickedUpAt}</span>
                                    </div>
                                    <div class="timeline-item">
                                        <span class="timeline-label">Delivered:</span>
                                        <span class="timeline-value">${deliveredAt}</span>
                                    </div>
                                    <div class="timeline-item">
                                        <span class="timeline-label">Estimated:</span>
                                        <span class="timeline-value">${estimatedDelivery}</span>
                                    </div>
                                </div>
                                
                                <div class="delivery-sections">
                                    <div class="delivery-section">
                                        <h4>Delivery Person</h4>
                                        <p><strong>Name:</strong> ${data.delivery_person.name}</p>
                                        <p><strong>Phone:</strong> ${data.delivery_person.phone}</p>
                                        <p><strong>Email:</strong> ${data.delivery_person.email}</p>
                                    </div>
                                    
                                    <div class="delivery-section">
                                        <h4>Customer</h4>
                                        <p><strong>Name:</strong> ${order.shipping_name}</p>
                                        <p><strong>Address:</strong> ${order.shipping_address}</p>
                                        <p><strong>City:</strong> ${order.shipping_city}, ${order.shipping_state} ${order.shipping_postal_code}</p>
                                    </div>
                                </div>
                                
                                <div class="delivery-items">
                                    <h4>Order Items</h4>
                                    ${itemsList}
                                </div>
                                
                                ${delivery.delivery_notes ? `
                                    <div class="delivery-notes">
                                        <h4>Delivery Notes</h4>
                                        <p>${delivery.delivery_notes}</p>
                                    </div>
                                ` : ''}
                                
                                ${proofSection}
                                ${ratingSection}
                            </div>
                        `;
                        
                        // Show modal
                        detailsModal.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching delivery details:', error);
                        alert('An error occurred while fetching delivery details. Please try again.');
                    });
            }
            
            // Helper function to format date for datetime-local input
            function formatDatetimeLocal(date) {
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                const hours = date.getHours().toString().padStart(2, '0');
                const minutes = date.getMinutes().toString().padStart(2, '0');
                
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        });
    </script>
</body>
</html>
