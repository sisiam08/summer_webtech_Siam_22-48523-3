<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';
// All functions are now consolidated in functions.php

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'You need to login to checkout.');
    redirect('../../Authentication/login.html');
}

// Get user info
$user = getCurrentUser();

// Get cart items grouped by shop
$shopCartItems = getCartItemsByShop();
$cartTotal = getCartTotal();
$totalDeliveryCharge = getTotalDeliveryCharge();
$grandTotal = $cartTotal + $totalDeliveryCharge;

// Check if cart is empty
if (empty($shopCartItems)) {
    setFlashMessage('error', 'Your cart is empty.');
    redirect('cart.php');
}

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    
    $errors = [];
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($city)) {
        $errors[] = 'City is required';
    }
    
    if (empty($paymentMethod)) {
        $errors[] = 'Payment method is required';
    }
    
    if (empty($errors)) {
        // Format complete shipping address
        $shippingAddress = "$address, $city";
        
        // Debug: Check if user has items in cart
        if (empty($shopCartItems)) {
            $errors[] = 'Your cart is empty. Please add items to your cart before checkout.';
        } else {
            try {
                // Create multi-shop order with all required parameters
                $orderId = createMultiShopOrder(
                    $user['id'], 
                    $user['name'], 
                    $user['email'], 
                    $user['phone'] ?? '', 
                    $shippingAddress, 
                    $paymentMethod
                );
                
                if ($orderId) {
                    setFlashMessage('success', 'Your order has been placed successfully! Order ID: ' . $orderId);
                    redirect('order_confirmation.php?id=' . $orderId);
                } else {
                    $errors[] = 'There was a problem placing your order. Please try again.';
                    error_log("Checkout failed for user {$user['id']}: Order creation returned false");
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                $errors[] = 'Order creation failed: ' . $errorMessage;
                error_log("Checkout failed for user {$user['id']}: $errorMessage");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Grocery Store</title>
    <link rel="stylesheet" href="../../Includes/style.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .checkout-form {
            flex: 6;
            min-width: 300px;
            background: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            flex: 4;
            min-width: 300px;
            background: #fff;
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            align-self: flex-start;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .shop-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .shop-section:last-child {
            border-bottom: none;
        }
        
        .shop-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .shop-header h3 {
            margin: 0;
        }
        
        .shop-items {
            margin-left: 1rem;
        }
        
        .shop-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .item-name {
            flex: 3;
        }
        
        .item-price, .item-quantity, .item-total {
            flex: 1;
            text-align: right;
        }
        
        .shop-subtotal {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-top: 0.5rem;
        }
        
        .order-summary-title {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .grand-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #eee;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .error-messages {
            background-color: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .error-messages ul {
            margin: 0;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Nitto Proyojon</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="../../Authentication/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="../../Authentication/login.html">Login</a></li>
                        <li><a href="../../Authentication/register.html">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Checkout</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <h3>Shipping Information</h3>
                <form method="post" action="multi_shop_checkout.php">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    
                    <h3>Payment Method</h3>
                    <div class="form-group">
                        <label for="payment_method">Select Payment Method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="upay">Upay</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h3 class="order-summary-title">Order Summary</h3>
                
                <?php foreach ($shopCartItems as $shopId => $shopCart): ?>
                    <div class="shop-section">
                        <div class="shop-header">
                            <h3><?php echo $shopCart['shop_name']; ?></h3>
                        </div>
                        
                        <div class="shop-items">
                            <?php foreach ($shopCart['items'] as $item): ?>
                                <div class="shop-item">
                                    <div class="item-name"><?php echo $item['name']; ?> x <?php echo $item['quantity']; ?></div>
                                    <div class="item-total"><?php echo formatPrice($item['total']); ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="shop-subtotal">
                                <span>Shop Subtotal:</span>
                                <span><?php echo formatPrice($shopCart['subtotal']); ?></span>
                            </div>
                            
                            <div class="summary-item">
                                <span>Delivery Charge:</span>
                                <span><?php echo formatPrice($shopCart['delivery_charge']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-item">
                    <span>Items Subtotal:</span>
                    <span><?php echo formatPrice($cartTotal); ?></span>
                </div>
                
                <div class="summary-item">
                    <span>Total Delivery Charges:</span>
                    <span><?php echo formatPrice($totalDeliveryCharge); ?></span>
                </div>
                
                <div class="summary-item grand-total">
                    <span>Grand Total:</span>
                    <span><?php echo formatPrice($grandTotal); ?></span>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Nitto Proyojon. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
