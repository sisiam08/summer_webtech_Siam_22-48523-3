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

// Get date filter from query parameters
$dateFilter = $_GET['date_filter'] ?? 'all';
$customDate = $_GET['custom_date'] ?? '';

// Build date condition for SQL query
$dateCondition = '';
$params = [$user['id']];

switch ($dateFilter) {
    case 'today':
        $dateCondition = "AND DATE(o.created_at) = CURDATE()";
        break;
    case 'week':
        $dateCondition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $dateCondition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'custom':
        if ($customDate) {
            $dateCondition = "AND DATE(o.created_at) = ?";
            $params[] = $customDate;
        }
        break;
}

// Get completed deliveries for this delivery person
try {
    $conn = connectDB();
    
    // Get completed orders with statistics
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
        WHERE o.delivery_person_id = ? AND o.status = 'completed' $dateCondition
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_deliveries,
            SUM((SELECT SUM(oi.quantity * p.price) * 0.02
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = o.id)) as total_revenue
        FROM orders o
        WHERE o.delivery_person_id = ? AND o.status = 'completed' $dateCondition
    ");
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $completedOrders = [];
    $stats = ['total_deliveries' => 0, 'total_revenue' => 0];
    setFlashMessage('error', 'Error loading completed deliveries: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Deliveries - Delivery Dashboard</title>
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
                    <li>
                        <a href="assignments.php">
                            <i class="material-icons">assignment</i>
                            My Assignments
                        </a>
                    </li>
                    <li class="active">
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
                <h2>Completed Deliveries</h2>
                <p>Your delivery history and performance</p>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">check_circle</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_deliveries']); ?></h3>
                        <p>Total Deliveries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">monetization_on</i>
                    </div>
                    <div class="stat-info">
                        <h3>৳<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">check_circle</i>
                    </div>
                    <div class="stat-info">
                        <h3>৳<?php echo number_format(($stats['total_deliveries'] > 0 ? $stats['total_revenue'] / $stats['total_deliveries'] : 0), 2); ?></h3>
                        <p>Avg. Commission per Order</p>
                    </div>
                </div>
            </div>

            <!-- Filter Options -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="date_filter">Filter by Date:</label>
                        <select name="date_filter" id="date_filter" onchange="toggleCustomDate()">
                            <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="custom" <?php echo $dateFilter === 'custom' ? 'selected' : ''; ?>>Custom Date</option>
                        </select>
                    </div>
                    <div class="filter-group" id="custom-date-group" style="display: <?php echo $dateFilter === 'custom' ? 'block' : 'none'; ?>;">
                        <label for="custom_date">Select Date:</label>
                        <input type="date" name="custom_date" id="custom_date" value="<?php echo htmlspecialchars($customDate); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <!-- Completed Orders List -->
            <div class="completed-orders-container">
                <?php if (empty($completedOrders)): ?>
                    <div class="no-orders">
                        <i class="material-icons">assignment_turned_in</i>
                        <h3>No Completed Deliveries</h3>
                        <p>You haven't completed any deliveries for the selected period.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($completedOrders as $order): ?>
                            <div class="order-card completed">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                                        <span class="status-badge status-delivered">Delivered</span>
                                    </div>
                                    <div class="order-amount">
                                        ৳<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                </div>
                                
                                <div class="order-details">
                                    <div class="customer-info">
                                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    </div>
                                    
                                    <div class="delivery-info">
                                        <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                        <p><strong>Status:</strong> <span class="status-completed">Completed</span></p>
                                    </div>
                                </div>
                                
                                <div class="order-actions">
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
                                    
                                    <div class="order-timeline">
                                        <h4>Delivery Timeline</h4>
                                        <p><strong>Order Placed:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                        <?php if ($order['pickup_time']): ?>
                                            <p><strong>Picked Up:</strong> <?php echo date('M j, Y g:i A', strtotime($order['pickup_time'])); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Delivered:</strong> <?php echo date('M j, Y g:i A', strtotime($order['delivery_time'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
        
        function toggleCustomDate() {
            const select = document.getElementById('date_filter');
            const customGroup = document.getElementById('custom-date-group');
            
            if (select.value === 'custom') {
                customGroup.style.display = 'block';
            } else {
                customGroup.style.display = 'none';
            }
        }
    </script>
</body>
</html>