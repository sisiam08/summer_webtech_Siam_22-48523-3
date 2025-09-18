<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in and is a delivery person
if (!isLoggedIn() || !isDelivery()) {
    redirect('../../Authentication/login.php');
}

$user = getCurrentUser();
$userName = $user['name'] ?? 'Delivery Partner';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)$_POST['order_id'];
    $action = $_POST['action'];
    
    try {
        $conn = connectDB();
        
        if ($action === 'complete') {
            // Update order status to "completed" - this should now work after adding to ENUM
            try {
                $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND delivery_person_id = ?");
                $stmt->execute([$orderId, $user['id']]);
                
                if ($stmt->rowCount() > 0) {
                    setFlashMessage('success', 'Order marked as completed successfully!');
                } else {
                    setFlashMessage('error', 'Order not found or not assigned to you.');
                }
            } catch (Exception $statusError) {
                setFlashMessage('error', 'Error updating order status: ' . $statusError->getMessage());
            }
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Error updating order status: ' . $e->getMessage());
    }
    
    // Redirect to prevent form resubmission
    header('Location: assignments.php');
    exit;
}

// Get assigned orders for this delivery person
try {
    $conn = connectDB();
    
    // Get assigned orders (orders marked as "delivered" and assigned to this delivery person)
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.address as customer_address,
               s.name as shop_name, s.address as shop_address,
               (SELECT SUM(oi.quantity * p.price) 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = o.id) as total_amount
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN shops s ON o.shop_id = s.id
        WHERE o.delivery_person_id = ? AND o.status = 'delivered'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $assignments = [];
    setFlashMessage('error', 'Error loading assignments: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Delivery Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="delivery.css">
</head>
<body class="delivery-dashboard">
    <div class="container dashboard-container">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="material-icons">directions_bike</i>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($userName); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <ul>
                    <li>
                        <a href="delivery_index.php">
                            <i class="material-icons">dashboard</i>
                            Dashboard
                        </a>
                    </li>
                    <li class="active">
                        <a href="assignments.php">
                            <i class="material-icons">assignment</i>
                            My Assignments
                        </a>
                    </li>
                    <li>
                        <a href="completed.php">
                            <i class="material-icons">check_circle</i>
                            Completed
                        </a>
                    </li>
                    <li>
                        <a href="delivery_profile.php">
                            <i class="material-icons">person</i>
                            My Profile
                        </a>
                    </li>
                    <li>
                        <a href="../../Authentication/logout.php" class="logout-btn">
                            <i class="material-icons">logout</i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="dashboard-main">
            <div class="page-header">
                <h2>My Delivery Assignments</h2>
                <p>Orders assigned to you for delivery - Mark as complete when delivered</p>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="assignments-container">
                <?php if (empty($assignments)): ?>
                    <div class="no-assignments">
                        <i class="material-icons">assignment_turned_in</i>
                        <h3>No Delivery Assignments</h3>
                        <p>You don't have any delivery assignments at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $order): ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                                    <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </div>
                                <div class="order-amount">
                                    ৳<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="assignment-details">
                                <div class="customer-info">
                                    <h4><i class="material-icons">person</i> Customer Details</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                                </div>
                                
                                <div class="shop-info">
                                    <h4><i class="material-icons">store</i> Shop Details</h4>
                                    <p><strong>Shop:</strong> <?php echo htmlspecialchars($order['shop_name']); ?></p>
                                    <p><strong>Shop Address:</strong> <?php echo htmlspecialchars($order['shop_address']); ?></p>
                                </div>
                                
                                <div class="order-timeline">
                                    <h4><i class="material-icons">schedule</i> Timeline</h4>
                                    <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Status:</strong> <span class="status-delivered">Ready for Delivery</span></p>
                                </div>
                            </div>
                            
                            <div class="assignment-actions">
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-success">
                                        <i class="material-icons">check_circle</i>
                                        Mark as Complete
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-secondary" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="material-icons">info</i>
                                    View Order Items
                                </button>
                            </div>
                            
                            <!-- Order Details (initially hidden) -->
                            <div id="order-details-<?php echo $order['id']; ?>" class="order-details" style="display: none;">
                                <h4>Order Items</h4>
                                <?php
                                try {
                                    $itemStmt = $conn->prepare("
                                        SELECT oi.quantity, p.name, p.price 
                                        FROM order_items oi 
                                        JOIN products p ON oi.product_id = p.id 
                                        WHERE oi.order_id = ?
                                    ");
                                    $itemStmt->execute([$order['id']]);
                                    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($items): ?>
                                        <div class="items-list">
                                            <?php foreach ($items as $item): ?>
                                                <div class="item">
                                                    <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                                    <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                                    <span class="item-price">৳<?php echo number_format($item['price'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p>No items found for this order.</p>
                                    <?php endif;
                                } catch (Exception $e) {
                                    echo "<p>Error loading order items.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleOrderDetails(orderId) {
            const details = document.getElementById('order-details-' + orderId);
            if (details.style.display === 'none' || details.style.display === '') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }
        
        // Confirm before marking as complete
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                if (action === 'complete') {
                    if (!confirm('Are you sure you want to mark this order as completed? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>