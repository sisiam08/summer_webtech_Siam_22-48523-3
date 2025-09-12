<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../php/db_connection.php';
require_once '../php/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.html');
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get order ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.html');
    exit;
}

$order_id = $_GET['id'];

// Get order details
$sql = "SELECT o.*, 
               u.name as customer_name, 
               u.email 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.html');
    exit;
}

// Get order items
$sql = "SELECT oi.*, p.name, p.image, s.name as shop_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN shops s ON p.shop_id = s.id
        WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order tracking events
$sql = "SELECT * FROM order_tracking 
        WHERE order_id = ? 
        ORDER BY created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$trackingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no tracking events, create initial "order placed" event
if (empty($trackingEvents)) 
{
    $trackingEvents = 
    [
        [
            'status' => 'order_placed',
            'description' => 'Order placed successfully',
            'created_at' => $order['created_at']
        ]
    ];
}

// Format order status for display
function getOrderStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'shipped':
            return 'Shipped';
        case 'out_for_delivery':
            return 'Out for Delivery';
        case 'delivered':
            return 'Delivered';
        case 'cancelled':
            return 'Cancelled';
        default:
            return ucfirst($status);
    }
}

// Format order status for class
function getOrderStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'shipped':
            return 'primary';
        case 'out_for_delivery':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Format tracking event for display
function getTrackingEventLabel($status) {
    switch ($status) {
        case 'order_placed':
            return 'Order Placed';
        case 'payment_confirmed':
            return 'Payment Confirmed';
        case 'processing':
            return 'Processing';
        case 'shipped':
            return 'Shipped';
        case 'out_for_delivery':
            return 'Out for Delivery';
        case 'delivered':
            return 'Delivered';
        case 'cancelled':
            return 'Cancelled';
        default:
            return ucfirst(str_replace('_', ' ', $status));
    }
}

// Format tracking event for icon
function getTrackingEventIcon($status) {
    switch ($status) {
        case 'order_placed':
            return 'shopping_cart';
        case 'payment_confirmed':
            return 'payments';
        case 'processing':
            return 'inventory';
        case 'shipped':
            return 'local_shipping';
        case 'out_for_delivery':
            return 'delivery_dining';
        case 'delivered':
            return 'check_circle';
        case 'cancelled':
            return 'cancel';
        default:
            return 'info';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order['order_number']; ?> - Online Grocery Store</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/customer.css">
    <style>
        .tracking-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .tracking-title h2 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .tracking-timeline {
            margin: 30px 0;
            position: relative;
        }
        
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #e0e0e0;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 60px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-marker {
            position: absolute;
            left: 0;
            top: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .timeline-marker.completed {
            background-color: #4CAF50;
            color: white;
        }
        
        .timeline-marker.active {
            background-color: #2196F3;
            color: white;
        }
        
        .timeline-marker.pending {
            background-color: #e0e0e0;
            color: #757575;
        }
        
        .timeline-content {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        
        .timeline-content h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }
        
        .timeline-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .estimated-delivery {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        
        .estimated-delivery i {
            margin-right: 10px;
            color: #2196F3;
        }
        
        .map-container {
            height: 250px;
            background-color: #f1f1f1;
            margin-top: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #757575;
        }
        
        .delivery-person {
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .delivery-person-avatar {
            width: 50px;
            height: 50px;
            background-color: #e1e1e1;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .delivery-person-info h3 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .delivery-person-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .delivery-actions {
            margin-left: auto;
        }
        
        .contact-delivery {
            display: flex;
            align-items: center;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .contact-delivery i {
            margin-right: 5px;
        }
        
        .order-summary {
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .tracking-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .tracking-status {
                margin-top: 10px;
            }
            
            .delivery-person {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .delivery-person-avatar {
                margin-bottom: 10px;
            }
            
            .delivery-actions {
                margin: 15px 0 0 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="content-header">
                <h1>Track Order</h1>
                <nav class="breadcrumb">
                    <a href="index.html">Home</a>
                    <a href="orders.html">My Orders</a>
                    <span>Track Order</span>
                </nav>
            </div>
            
            <div class="tracking-container">
                <div class="tracking-header">
                    <div class="tracking-title">
                        <h2>Order #<?php echo $order['order_number']; ?></h2>
                        <p>Ordered on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="tracking-status">
                        <span class="badge <?php echo getOrderStatusClass($order['status']); ?>">
                            <?php echo getOrderStatusLabel($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="tracking-timeline">
                    <?php foreach ($trackingEvents as $index => $event): ?>
                        <?php
                        $statusClass = 'completed'; // Default for past events
                        if ($index === count($trackingEvents) - 1 && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled') {
                            $statusClass = 'active'; // Current event
                        }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $statusClass; ?>">
                                <i class="material-icons"><?php echo getTrackingEventIcon($event['status']); ?></i>
                            </div>
                            <div class="timeline-content">
                                <h3><?php echo getTrackingEventLabel($event['status']); ?></h3>
                                <p><?php echo date('M d, Y h:i A', strtotime($event['created_at'])); ?></p>
                                <?php if (isset($event['description']) && !empty($event['description'])): ?>
                                    <p><?php echo $event['description']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                        <!-- Add pending future events based on current status -->
                        <?php
                        $futureStatuses = [];
                        $currentStatus = $order['status'];
                        
                        if ($currentStatus === 'pending') {
                            $futureStatuses = ['processing', 'shipped', 'out_for_delivery', 'delivered'];
                        } elseif ($currentStatus === 'processing') {
                            $futureStatuses = ['shipped', 'out_for_delivery', 'delivered'];
                        } elseif ($currentStatus === 'shipped') {
                            $futureStatuses = ['out_for_delivery', 'delivered'];
                        } elseif ($currentStatus === 'out_for_delivery') {
                            $futureStatuses = ['delivered'];
                        }
                        
                        foreach ($futureStatuses as $status):
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-marker pending">
                                    <i class="material-icons"><?php echo getTrackingEventIcon($status); ?></i>
                                </div>
                                <div class="timeline-content">
                                    <h3><?php echo getTrackingEventLabel($status); ?></h3>
                                    <p>Pending</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                    <div class="estimated-delivery">
                        <i class="material-icons">schedule</i>
                        <div>
                            <h3>Estimated Delivery</h3>
                            <p>Your order is expected to be delivered by <?php echo date('l, M d', strtotime('+3 days', strtotime($order['created_at']))); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($order['status'] === 'out_for_delivery'): ?>
                    <div class="map-container">
                        <p><i class="material-icons">map</i> Live tracking map will be available soon</p>
                    </div>
                    
                    <?php
                    // Get delivery person information (if available)
                    $sql = "SELECT d.*, u.name, u.phone 
                            FROM deliveries d
                            JOIN users u ON d.delivery_person_id = u.id
                            WHERE d.order_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$order_id]);
                    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($delivery):
                    ?>
                        <div class="delivery-person">
                            <div class="delivery-person-avatar">
                                <i class="material-icons">person</i>
                            </div>
                            <div class="delivery-person-info">
                                <h3><?php echo $delivery['name']; ?></h3>
                                <p>Your Delivery Person</p>
                            </div>
                            <div class="delivery-actions">
                                <button class="contact-delivery">
                                    <i class="material-icons">phone</i> Contact
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <img src="../uploads/products/<?php echo $item['image'] ? $item['image'] : 'placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="order-item-details">
                                <h3><?php echo $item['name']; ?></h3>
                                <p class="order-item-shop"><?php echo $item['shop_name'] ? 'From: ' . $item['shop_name'] : ''; ?></p>
                                <p class="order-item-meta">
                                    Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                                </p>
                            </div>
                            <div class="order-item-price">
                                $<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($order['shipping_fee'], 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($order['tax'], 2); ?></span>
                    </div>
                    <div class="totals-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <div class="order-actions">
                    <a href="orders.html" class="btn secondary">Back to Orders</a>
                    
                    <?php if ($order['status'] === 'delivered'): ?>
                        <a href="review.php?order_id=<?php echo $order_id; ?>" class="btn primary">Leave Review</a>
                    <?php elseif ($order['status'] === 'pending'): ?>
                        <a href="cancel-order.php?id=<?php echo $order_id; ?>" class="btn danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is logged in
            fetch('php/customer/check_auth.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.isAuthenticated || data.role !== 'customer') {
                        window.location.href = 'login.html';
                    } else {
                        // Set user information
                        document.getElementById('customer-name').textContent = `Welcome, ${data.name}!`;
                        document.getElementById('customer-email').textContent = data.email;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = 'login.html';
                });
        });
    </script>
</body>
</html>
