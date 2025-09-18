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
        
        if ($action === 'pickup') {
            // Update order status to "picked_up"
            $stmt = $conn->prepare("UPDATE orders SET status = 'picked_up', pickup_time = NOW() WHERE id = ? AND delivery_person_id = ?");
            $stmt->execute([$orderId, $user['id']]);
            setFlashMessage('success', 'Order marked as picked up successfully!');
        } elseif ($action === 'deliver') {
            // Update order status to "delivered"
            $stmt = $conn->prepare("UPDATE orders SET status = 'delivered', delivery_time = NOW() WHERE id = ? AND delivery_person_id = ?");
            $stmt->execute([$orderId, $user['id']]);
            setFlashMessage('success', 'Order marked as delivered successfully!');
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
    
    // Get pending assignments (orders assigned but not yet picked up or delivered)
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.address as customer_address,
               (SELECT SUM(oi.quantity * p.price) 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = o.id) as total_amount
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.delivery_person_id = ? AND o.status IN ('assigned', 'picked_up')
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
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="delivery_index.php">Dashboard</a></li>
                    <li><a href="assignments.php" class="active">My Assignments</a></li>
                    <li><a href="completed.php">Completed Deliveries</a></li>
                    <li><a href="delivery_profile.php">My Profile</a></li>
                    <li><a href="../../Authentication/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

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
                </ul>
            </nav>
        </div>

        <div class="dashboard-main">
            <div class="page-header">
                <h2>My Assignments</h2>
                <p>Manage your assigned deliveries</p>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="assignments-container">
                <?php if (empty($assignments)): ?>
                    <div class="no-assignments">
                        <i class="material-icons">assignment_turned_in</i>
                        <h3>No Active Assignments</h3>
                        <p>You don't have any active delivery assignments at the moment.</p>
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
                                
                                <div class="order-timeline">
                                    <h4><i class="material-icons">schedule</i> Timeline</h4>
                                    <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    <?php if ($order['pickup_time']): ?>
                                        <p><strong>Picked Up:</strong> <?php echo date('M j, Y g:i A', strtotime($order['pickup_time'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="assignment-actions">
                                <?php if ($order['status'] === 'assigned'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="action" value="pickup">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="material-icons">shopping_cart</i>
                                            Mark as Picked Up
                                        </button>
                                    </form>
                                <?php elseif ($order['status'] === 'picked_up'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="action" value="deliver">
                                        <button type="submit" class="btn btn-success">
                                            <i class="material-icons">check_circle</i>
                                            Mark as Delivered
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-secondary" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="material-icons">info</i>
                                    View Details
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
        
        // Confirm before marking as delivered
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                if (action === 'deliver') {
                    if (!confirm('Are you sure you want to mark this order as delivered?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>