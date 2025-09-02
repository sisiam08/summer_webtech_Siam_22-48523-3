<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Get cart items
$cartItems = getCartItems();
$cartTotal = getCartTotal();

// Handle cart updates if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update cart quantities
        foreach ($_POST['quantity'] as $productId => $quantity) {
            updateCartItem($productId, (int)$quantity);
        }
        
        // Refresh cart data
        $cartItems = getCartItems();
        $cartTotal = getCartTotal();
        
        setFlashMessage('success', 'Cart updated successfully.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Online Grocery Store</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .cart-table th, .cart-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .cart-table th {
            background-color: #f4f4f4;
        }
        
        .cart-table .product-img {
            width: 80px;
            height: auto;
        }
        
        .cart-table .quantity-input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
        }
        
        .cart-total {
            text-align: right;
            font-size: 1.2rem;
            margin: 1rem 0;
        }
        
        .cart-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        
        .empty-cart {
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
        <h2>Shopping Cart</h2>
        
        <?php displayFlashMessage(); ?>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="products.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="product-img">
                                    <?php echo $item['name']; ?>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                                </td>
                                <td><?php echo formatPrice($item['total']); ?></td>
                                <td>
                                    <a href="remove_from_cart.php?id=<?php echo $item['id']; ?>" class="btn" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-total">
                    <strong>Total: <?php echo formatPrice($cartTotal); ?></strong>
                </div>
                
                <div class="cart-buttons">
                    <a href="products.php" class="btn">Continue Shopping</a>
                    <button type="submit" name="update_cart" class="btn">Update Cart</button>
                    <a href="checkout.php" class="btn">Checkout</a>
                </div>
            </form>
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
