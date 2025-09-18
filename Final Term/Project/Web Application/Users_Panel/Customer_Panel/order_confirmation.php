<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../Authentication/login.html');
}

// Get order ID from URL
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    setFlashMessage('error', 'Invalid order ID.');
    redirect('account.php');
}

// Get order details
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('account.php');
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Online Grocery Store</title>
    <link rel="stylesheet" href="../../Includes/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .success-icon i {
            font-size: 4rem;
            color: #28a745;
        }
        
        .order-details {
            margin-bottom: 2rem;
        }
        
        .order-items {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .item-price {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your order. Your order has been successfully placed.</p>
            </div>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Order ID:</label>
                        <span>#<?= $orderId ?></span>
                    </div>
                    <div class="info-item">
                        <label>Order Date:</label>
                        <span><?= date('F j, Y g:i a', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Customer:</label>
                        <span><?= htmlspecialchars($order['customer_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?= htmlspecialchars($order['customer_email']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Shipping Address:</label>
                        <span><?= htmlspecialchars($order['shipping_address']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Payment Method:</label>
                        <span><?= htmlspecialchars($order['payment_method']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Total Amount:</label>
                        <span class="price">৳<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <span class="status status-<?= strtolower($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($orderItems)): ?>
            <div class="order-items">
                <h3>Order Items</h3>
                <?php foreach ($orderItems as $item): ?>
                <div class="order-item">
                    <img src="../../Uploads/<?= htmlspecialchars($item['image']) ?>" 
                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                         class="item-image"
                         onerror="this.src='../../Uploads/default-product.jpg'">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div>Quantity: <?= $item['quantity'] ?></div>
                        <div class="item-price">৳<?= number_format($item['price'], 2) ?> each</div>
                    </div>
                    <div class="item-total">
                        <strong>৳<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="actions" style="text-align: center; margin-top: 2rem;">
                <a href="account.php" class="btn btn-primary">View All Orders</a>
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</body>
</html>