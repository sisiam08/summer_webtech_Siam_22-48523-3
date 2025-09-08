<?php
// Initialize session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/functions.php';
require_once 'includes/shop_functions.php';

// Redirect if no order ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('index.php');
}

$orderId = (int)$_GET['id'];

// Get order details
$order = getOrderDetails($orderId);
if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('index.php');
}

// Get shop orders for this order
$shopOrders = getShopOrdersByOrderId($orderId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .confirmation-container {
            background: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .confirmation-header h3 {
            color: #4CAF50;
            margin-bottom: 0.5rem;
        }
        
        .order-info {
            border: 1px solid #eee;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }
        
        .order-info-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .order-info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .shop-orders {
            margin-bottom: 2rem;
        }
        
        .shop-order {
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .shop-order-header {
            background-color: #f9f9f9;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .shop-order-items {
            padding: 1rem;
        }
        
        .shop-order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #eee;
        }
        
        .shop-order-item:last-child {
            border-bottom: none;
        }
        
        .shop-order-summary {
            background-color: #f9f9f9;
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-row.total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .order-total {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            text-align: right;
            margin-top: 1rem;
        }
        
        .thank-you {
            text-align: center;
            margin-top: 2rem;
        }
        
        .next-steps {
            margin-top: 2rem;
            text-align: center;
        }
        
        .next-steps a {
            margin: 0 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <h3>Order Confirmed!</h3>
                <p>Thank you for your order. Your order has been received and is being processed.</p>
            </div>
            
            <div class="order-info">
                <h3>Order Information</h3>
                <div class="order-info-row">
                    <div class="order-info-label">Order ID:</div>
                    <div><?php echo $order['id']; ?></div>
                </div>
                <div class="order-info-row">
                    <div class="order-info-label">Order Date:</div>
                    <div><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="order-info-row">
                    <div class="order-info-label">Shipping Address:</div>
                    <div><?php echo $order['shipping_address']; ?></div>
                </div>
                <div class="order-info-row">
                    <div class="order-info-label">Payment Method:</div>
                    <div><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></div>
                </div>
            </div>
            
            <div class="shop-orders">
                <h3>Order Details</h3>
                
                <?php foreach ($shopOrders as $shopOrder): ?>
                    <div class="shop-order">
                        <div class="shop-order-header">
                            <h4><?php echo $shopOrder['shop_name']; ?></h4>
                            <p>Status: <strong><?php echo ucfirst($shopOrder['status']); ?></strong></p>
                        </div>
                        
                        <div class="shop-order-items">
                            <?php foreach ($shopOrder['items'] as $item): ?>
                                <div class="shop-order-item">
                                    <div>
                                        <strong><?php echo $item['name']; ?></strong> x <?php echo $item['quantity']; ?>
                                    </div>
                                    <div><?php echo formatPrice($item['total']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="shop-order-summary">
                            <div class="summary-row">
                                <div>Subtotal:</div>
                                <div><?php echo formatPrice($shopOrder['subtotal']); ?></div>
                            </div>
                            <div class="summary-row">
                                <div>Delivery Charge:</div>
                                <div><?php echo formatPrice($shopOrder['delivery_charge']); ?></div>
                            </div>
                            <div class="summary-row total">
                                <div>Shop Total:</div>
                                <div><?php echo formatPrice($shopOrder['subtotal'] + $shopOrder['delivery_charge']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    <div class="summary-row total">
                        <div>Order Total:</div>
                        <div><?php echo formatPrice($order['total_amount'] + $order['total_delivery_charge']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="thank-you">
                <h3>Thank You for Shopping with Us!</h3>
                <p>You will receive an email confirmation shortly.</p>
            </div>
            
            <div class="next-steps">
                <a href="index.php" class="btn">Continue Shopping</a>
                <a href="account.php" class="btn">View Your Orders</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
