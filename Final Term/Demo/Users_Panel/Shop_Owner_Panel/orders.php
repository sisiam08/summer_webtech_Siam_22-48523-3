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

// User is authenticated, proceed with the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Shop Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="shop_owner.css">
</head>
<body class="shop-owner">
    <div class="shop-owner-sidebar">
        <div class="brand">
            Shop Dashboard
        </div>
        <div class="menu">
            <a href="shop_owner_index.php" class="menu-item active">
                <i class="material-icons">dashboard</i> <span>Dashboard</span>
            </a>
            <a href="shop_products.php" class="menu-item">
                <i class="material-icons">inventory</i> <span>Products</span>
            </a>
            <a href="orders.php" class="menu-item">
                <i class="material-icons">shopping_cart</i> <span>Orders</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="material-icons">bar_chart</i> <span>Reports</span>
            </a>
            <a href="shop_profile.php" class="menu-item">
                <i class="material-icons">store</i> <span>Shop Profile</span>
            </a>
        </div>
    </div>

    <div class="shop-owner-content">
        <div class="shop-owner-header">
            <h2>Orders</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="shop-owner-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Shop Owner'); ?></span>
                    <i class="material-icons">arrow_drop_down</i>
                    <div class="dropdown-content">
                        <a href="shop_profile.php">My Profile</a>
                        <a href="../../Authentication/logout.php" id="logout-btn">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="order-filters">
            <div class="filter-group">
                <label for="status-filter">Status:</label>
                <select id="status-filter">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="date-filter">Date:</label>
                <select id="date-filter">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            <div class="search-container">
                <input type="text" id="order-search" placeholder="Search orders...">
                <i class="material-icons">search</i>
            </div>
        </div>

        <div class="orders-container">
            <div class="card">
                <div class="card-header">
                    <h3>Orders</h3>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table">
                            <!-- Orders will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to load orders
        function loadOrders() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const searchText = document.getElementById('order-search').value;
            
            fetch(`get_orders.php?status=${statusFilter}&date=${dateFilter}&search=${searchText}`)
                .then(response => response.json())
                .then(orders => {
                    const tableBody = document.getElementById('orders-table');
                    tableBody.innerHTML = '';
                    
                    if (orders.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="7" style="text-align: center;">No orders found</td>`;
                        tableBody.appendChild(row);
                        return;
                    }
                    
                    orders.forEach(order => {
                        const row = document.createElement('tr');
                        
                        // Determine status class
                        let statusClass = '';
                        switch(order.status) {
                            case 'Pending':
                            case 'Processing':
                                statusClass = 'warning';
                                break;
                            case 'Shipped':
                            case 'Delivered':
                                statusClass = 'success';
                                break;
                            case 'Cancelled':
                                statusClass = 'danger';
                                break;
                            default:
                                statusClass = '';
                        }
                        
                        row.innerHTML = `
                            <td>#${order.id}</td>
                            <td>${order.customer_name}</td>
                            <td>${order.order_date}</td>
                            <td><span class="badge ${statusClass}">${order.status}</span></td>
                            <td>${order.item_count} items</td>
                            <td>$${parseFloat(order.total).toFixed(2)}</td>
                            <td>
                                <a href="order-details.php?id=${order.id}" class="btn icon primary">
                                    <i class="material-icons">visibility</i>
                                </a>
                                <button class="btn icon ${order.status === 'Delivered' || order.status === 'Cancelled' ? 'disabled' : 'success'} update-status" data-id="${order.id}" ${order.status === 'Delivered' || order.status === 'Cancelled' ? 'disabled' : ''}>
                                    <i class="material-icons">update</i>
                                </button>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                    
                    // Add event listeners to update status buttons
                    document.querySelectorAll('.update-status:not(.disabled)').forEach(button => {
                        button.addEventListener('click', function() {
                            const orderId = this.getAttribute('data-id');
                            updateOrderStatus(orderId);
                        });
                    });
                })
                .catch(error => console.error('Error loading orders:', error));
        }
        
        // Function to update order status
        function updateOrderStatus(orderId) {
            // Get the current status to determine the next status
            fetch(`get_order_status.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error getting order status: ' + data.message);
                        return;
                    }
                    
                    let nextStatus = '';
                    switch(data.status) {
                        case 'Pending':
                            nextStatus = 'Processing';
                            break;
                        case 'Processing':
                            nextStatus = 'Shipped';
                            break;
                        case 'Shipped':
                            nextStatus = 'Delivered';
                            break;
                        default:
                            alert('Cannot update order status from ' + data.status);
                            return;
                    }
                    
                    if (confirm(`Update order status from ${data.status} to ${nextStatus}?`)) {
                        fetch('update_order_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: orderId, status: nextStatus })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload orders after successful update
                                loadOrders();
                            } else {
                                alert('Error updating order status: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Initialize orders on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load orders
            loadOrders();
            
            // Add event listeners for filters
            document.getElementById('status-filter').addEventListener('change', loadOrders);
            document.getElementById('date-filter').addEventListener('change', loadOrders);
            
            // Add event listener for search
            document.getElementById('order-search').addEventListener('keyup', function(e) {
                // Only search when Enter key is pressed
                if (e.key === 'Enter') {
                    loadOrders();
                }
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
