<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to checkout');
    redirect('login.php');
}

// Redirect if cart is empty
$cartItems = getCartItems();
if (empty($cartItems)) {
    setFlashMessage('error', 'Your cart is empty');
    redirect('cart.php');
}

$cartTotal = getCartTotal();
$user = getCurrentUser();

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip = $_POST['zip'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    // Validate form data
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($city)) {
        $errors[] = 'City is required';
    }
    
    if (empty($state)) {
        $errors[] = 'State is required';
    }
    
    if (empty($zip)) {
        $errors[] = 'ZIP code is required';
    }
    
    if (empty($paymentMethod)) {
        $errors[] = 'Payment method is required';
    }
    
    // Process order if no errors
    if (empty($errors)) {
        // In a real application, you would:
        // 1. Save the order to database
        // 2. Process payment
        // 3. Send confirmation email
        // 4. Clear cart
        
        // For this example, we'll just clear the cart and show success message
        clearCart();
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Grocery Store</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 2rem;
        }
        
        .order-summary {
            width: 40%;
            background: #fff;
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .checkout-form {
            width: 60%;
        }
        
        .order-items {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-total {
            font-weight: bold;
            margin-top: 1rem;
            text-align: right;
            font-size: 1.2rem;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        <h2>Checkout</h2>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h3>Thank you for your order!</h3>
                <p>Your order has been placed successfully.</p>
                <p>We'll process your order and deliver it as soon as possible.</p>
                <a href="products.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <form method="post" action="checkout.php">
                        <h3>Shipping Information</h3>
                        
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="zip">ZIP Code</label>
                            <input type="text" id="zip" name="zip" required>
                        </div>
                        
                        <h3>Payment Method</h3>
                        
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="credit_card" checked> Credit Card
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="paypal"> PayPal
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="cash_on_delivery"> Cash on Delivery
                            </label>
                        </div>
                        
                        <button type="submit" class="btn">Place Order</button>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="order-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <span><?php echo $item['quantity']; ?> x <?php echo $item['name']; ?></span>
                                <span><?php echo formatPrice($item['total']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-total">
                        Total: <?php echo formatPrice($cartTotal); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
