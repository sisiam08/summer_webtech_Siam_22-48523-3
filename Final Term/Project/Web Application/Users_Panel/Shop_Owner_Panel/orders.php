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
    <title>Orders - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="shop_owner_modern.css">
    <link rel="stylesheet" href="notification-system.css">
    <script src="notification-system.js"></script>
    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
        }
        
        .close-modal {
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            padding: 0 8px;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: #dc3545;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .delivery-man-list {
            max-height: 300px;
            overflow-y: auto;
            margin: 16px 0;
        }
        
        .delivery-man-item {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .delivery-man-item:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .delivery-man-item.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .delivery-man-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .delivery-man-contact {
            font-size: 12px;
            color: #6c757d;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #0056b3;
        }
        
        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }

        /* Status Update Modal Styles */
        .status-update-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .status-update-info p {
            margin: 8px 0;
            font-size: 14px;
        }

        .status-badge {
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-selection {
            margin-bottom: 20px;
        }

        .status-selection label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .status-dropdown {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
        }

        .status-dropdown:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .status-dropdown option {
            padding: 8px;
        }
    </style>
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
                    <a href="orders.php" class="nav-link active">
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
                    <h1>Order Management</h1><br><br>
                    <p>Track and manage your shop's customer orders</p>
                </div>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" id="order-search" placeholder="Search orders...">
                    <i class="fas fa-search"></i>
                </div>
                <div class="header-actions">
                    <button class="action-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="content-card orders-main">
                <div class="card-header">
                    <div class="header-info">
                        <h3>Customer Orders</h3>
                        <p>View and manage orders for your products</p>
                    </div>
                    <div class="header-actions">
                        <select class="btn-secondary" id="status-filter">
                            <option value="all">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orders-table-body">
                                <!-- Orders will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Man Selection Modal -->
    <div id="deliveryManModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Delivery Man</h3>
                <span class="close-modal" onclick="closeDeliveryManModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Select a delivery man to complete the order delivery:</p>
                <div id="deliveryManList" class="delivery-man-list">
                    <!-- Delivery men will be loaded here -->
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeDeliveryManModal()" class="btn-secondary">Cancel</button>
                    <button type="button" onclick="confirmDeliveryAssignment()" class="btn-primary" id="confirmDeliveryBtn" disabled>Assign & Mark Delivered</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusUpdateModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Order Status</h3>
                <span class="close-modal" onclick="closeStatusUpdateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="status-update-info">
                    <p><strong>Order ID:</strong> #<span id="orderIdDisplay"></span></p>
                    <p><strong>Current Status:</strong> <span id="currentOrderStatus" class="status-badge"></span></p>
                </div>
                <div class="status-selection">
                    <label for="newStatusSelect"><strong>Select New Status:</strong></label>
                    <select id="newStatusSelect" class="status-dropdown">
                        <option value="">Select new status...</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeStatusUpdateModal()" class="btn-secondary">Cancel</button>
                    <button type="button" onclick="confirmStatusUpdate()" class="btn-primary" id="confirmStatusBtn">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load orders
        function loadOrders() {
            const statusFilter = document.getElementById('status-filter').value;
            const searchText = document.getElementById('order-search').value;
            
            fetch(`get_orders.php?status=${statusFilter}&search=${searchText}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.error) {
                        throw new Error(data.error);
                    }
                    if (!Array.isArray(data)) {
                        throw new Error('Invalid response format: Expected array');
                    }
                    
                    console.log('Orders data received:', data); // Debug log
                    
                    const tableBody = document.getElementById('orders-table-body');
                    tableBody.innerHTML = '';
                    
                    // Check if there's an error in the response
                    if (data.error) {
                        console.error('Server error:', data.error);
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="7" style="text-align: center; color: #e74c3c; padding: 32px;">Error: ${data.error}</td>`;
                        tableBody.appendChild(row);
                        return;
                    }
                    
                    // Check if data is an array
                    const orders = Array.isArray(data) ? data : [];
                    
                    if (orders.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="7" style="text-align: center; color: #777; padding: 32px;">No orders found</td>`;
                        tableBody.appendChild(row);
                        return;
                    }
                    
                    orders.forEach(order => {
                        const row = document.createElement('tr');
                        
                        // Determine status class
                        let statusClass = '';
                        switch(order.status.toLowerCase()) {
                            case 'pending':
                                statusClass = 'status-badge pending';
                                break;
                            case 'processing':
                                statusClass = 'status-badge processing';
                                break;
                            case 'shipped':
                                statusClass = 'status-badge shipped';
                                break;
                            case 'delivered':
                                statusClass = 'status-badge delivered';
                                break;
                            case 'cancelled':
                                statusClass = 'status-badge cancelled';
                                break;
                            default:
                                statusClass = 'status-badge';
                        }
                        
                        // Process order items to create products display
                        let productsHtml = '';
                        if (order.items && order.items.length > 0) {
                            productsHtml = order.items.map(item => `
                                <div class="product-item" style="margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #007bff;">
                                    <div style="font-weight: 600; color: #333; font-size: 14px;">${item.product_name}</div>
                                    <div style="font-size: 12px; color: #666; margin-top: 2px;">
                                        <span style="background: #e3f2fd; padding: 2px 6px; border-radius: 12px; margin-right: 8px;">Qty: ${item.quantity}</span>
                                        <span style="background: #e8f5e8; padding: 2px 6px; border-radius: 12px;">৳${parseFloat(item.price).toFixed(2)} each</span>
                                    </div>
                                    <div style="font-size: 11px; color: #888; margin-top: 4px;">Total: ৳${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</div>
                                </div>
                            `).join('');
                        } else {
                            productsHtml = '<div style="color: #666; font-style: italic;">No items found</div>';
                        }
                        
                        row.innerHTML = `
                            <td>
                                <div class="order-info">
                                    <strong>#${order.id}</strong>
                                    <small>${order.items ? order.items.length : 0} items</small>
                                </div>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <strong>${order.customer_name}</strong>
                                    <small>${order.customer_email || 'N/A'}</small>
                                </div>
                            </td>
                            <td>
                                <div class="products-list" style="max-height: 200px; overflow-y: auto;">
                                    ${productsHtml}
                                </div>
                            </td>
                            <td>${order.order_date}</td>
                            <td><span class="${statusClass}">${order.status}</span></td>
                            <td class="amount-cell">৳${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon primary" onclick="viewOrder(${order.id})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon secondary" onclick="updateOrderStatus(${order.id})" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon danger" onclick="deleteOrder(${order.id})" title="Delete Order">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error(error);
                    const tableBody = document.getElementById('orders-table-body');
                    tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #e74c3c;">Error loading orders: ${error.message || error}</td></tr>`;
                });
        }

        // Function to view order details
        function viewOrder(orderId) {
            window.location.href = `order-details.php?id=${orderId}`;
        }

        // Function to update order status
        function updateOrderStatus(orderId) {
            // Get the current status first
            fetch(`get_order_status.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error getting order status: ' + data.message);
                        return;
                    }
                    
                    // Show manual status selection modal
                    showStatusUpdateModal(orderId, data.status);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error getting order status');
                });
        }

        // Function to show manual status update modal
        function showStatusUpdateModal(orderId, currentStatus) {
            window.currentOrderId = orderId;
            window.currentOrderStatus = currentStatus;
            
            // Set current status in modal
            document.getElementById('currentOrderStatus').textContent = currentStatus;
            document.getElementById('orderIdDisplay').textContent = orderId;
            
            // Populate status dropdown
            const statusSelect = document.getElementById('newStatusSelect');
            statusSelect.innerHTML = '';
            
            // Define available statuses based on current status
            let availableStatuses = [];
            const currentStatusLower = currentStatus.toLowerCase();
            
            switch(currentStatusLower) {
                case 'pending':
                    availableStatuses = ['Processing', 'Cancelled'];
                    break;
                case 'processing':
                    availableStatuses = ['Shipped', 'Cancelled'];
                    break;
                case 'shipped':
                    availableStatuses = ['Delivered'];
                    break;
                case 'delivered':
                    availableStatuses = []; // Cannot change from delivered
                    break;
                case 'cancelled':
                    availableStatuses = []; // Cannot change from cancelled
                    break;
                default:
                    availableStatuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
            }
            
            if (availableStatuses.length === 0) {
                statusSelect.innerHTML = '<option value="">No status changes available</option>';
                document.getElementById('confirmStatusBtn').disabled = true;
            } else {
                statusSelect.innerHTML = '<option value="">Select new status...</option>';
                availableStatuses.forEach(status => {
                    const option = document.createElement('option');
                    option.value = status;
                    option.textContent = status;
                    statusSelect.appendChild(option);
                });
                document.getElementById('confirmStatusBtn').disabled = false;
            }
            
            // Show modal
            document.getElementById('statusUpdateModal').style.display = 'flex';
        }

        // Function to close status update modal
        function closeStatusUpdateModal() {
            document.getElementById('statusUpdateModal').style.display = 'none';
            document.getElementById('newStatusSelect').value = '';
            window.currentOrderId = null;
            window.currentOrderStatus = null;
        }

        // Function to confirm status update
        function confirmStatusUpdate() {
            const selectedStatus = document.getElementById('newStatusSelect').value;
            
            if (!selectedStatus) {
                alert('Please select a new status');
                return;
            }
            
            if (selectedStatus.toLowerCase() === 'delivered') {
                // Save values before closing modal
                const orderId = window.currentOrderId;
                const currentStatus = window.currentOrderStatus;
                
                // Close current modal and show delivery man selection
                closeStatusUpdateModal();
                showDeliveryManModal(orderId, currentStatus, selectedStatus);
            } else {
                // Direct status update
                if (confirm(`Update order #${window.currentOrderId} status from ${window.currentOrderStatus} to ${selectedStatus}?`)) {
                    updateOrderStatusDirect(window.currentOrderId, selectedStatus);
                    closeStatusUpdateModal();
                }
            }
        }

        // Function to show delivery man selection modal
        function showDeliveryManModal(orderId, currentStatus, nextStatus) {
            window.currentOrderId = orderId;
            window.currentOrderStatus = currentStatus;
            window.nextOrderStatus = nextStatus;
            
            // Fetch delivery men
            fetch('get_delivery_men.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error loading delivery men: ' + data.error);
                        return;
                    }
                    
                    if (!data.success || !data.delivery_men || data.delivery_men.length === 0) {
                        alert('No delivery men available in your city (' + (data.city || 'Unknown') + ')');
                        return;
                    }
                    
                    // Populate delivery men list
                    const deliveryManList = document.getElementById('deliveryManList');
                    deliveryManList.innerHTML = '';
                    
                    data.delivery_men.forEach(dm => {
                        const item = document.createElement('div');
                        item.className = 'delivery-man-item';
                        item.dataset.deliveryManId = dm.id;
                        item.innerHTML = `
                            <div class="delivery-man-name">${dm.name}</div>
                            <div class="delivery-man-contact">${dm.email} • ${dm.phone}</div>
                        `;
                        
                        item.addEventListener('click', function() {
                            // Remove selection from other items
                            document.querySelectorAll('.delivery-man-item').forEach(i => i.classList.remove('selected'));
                            // Select this item
                            this.classList.add('selected');
                            // Enable confirm button
                            document.getElementById('confirmDeliveryBtn').disabled = false;
                            window.selectedDeliveryManId = dm.id;
                        });
                        
                        deliveryManList.appendChild(item);
                    });
                    
                    // Show modal
                    document.getElementById('deliveryManModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading delivery men');
                });
        }

        // Function to close delivery man modal
        function closeDeliveryManModal() {
            document.getElementById('deliveryManModal').style.display = 'none';
            document.getElementById('confirmDeliveryBtn').disabled = true;
            window.selectedDeliveryManId = null;
        }

        // Function to confirm delivery assignment
        function confirmDeliveryAssignment() {
            if (!window.selectedDeliveryManId) {
                alert('Please select a delivery man');
                return;
            }
            
            if (confirm(`Assign delivery man and mark order #${window.currentOrderId} as delivered?`)) {
                updateOrderStatusWithDelivery(window.currentOrderId, window.nextOrderStatus, window.selectedDeliveryManId);
            }
        }

        // Function to update order status with delivery assignment
        function updateOrderStatusWithDelivery(orderId, status, deliveryPersonId) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    id: orderId, 
                    status: status,
                    delivery_person_id: deliveryPersonId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeliveryManModal();
                    loadOrders(); // Reload orders after successful update
                    alert('Order marked as delivered and delivery man assigned successfully!');
                } else {
                    alert('Error updating order status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
        }

        // Function to update order status directly (for non-delivered statuses)
        function updateOrderStatusDirect(orderId, status) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: orderId, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadOrders(); // Reload orders after successful update
                } else {
                    alert('Error updating order status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
        }

        // Function to delete order
        function deleteOrder(orderId) {
            if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                fetch('delete_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadOrders(); // Reload orders after successful deletion
                    } else {
                        alert('Error deleting order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting order');
                });
            }
        }
        
        // Initialize orders on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load orders
            loadOrders();
            
            // Add event listeners for filters
            document.getElementById('status-filter').addEventListener('change', loadOrders);
            
            // Add event listener for search with real-time search
            document.getElementById('order-search').addEventListener('input', function() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    loadOrders();
                }, 300); // Wait 300ms after user stops typing
            });
            
            // Add logout functionality
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '../../Authentication/logout.php';
            });
        });
    </script>
</body>
</html>
