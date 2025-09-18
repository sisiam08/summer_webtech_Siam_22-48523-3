<?php
// Initialize session
session_start();

// Include required files first
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ensure cart has the correct structure
normalizeCartStructure();

// Get cart items grouped by shop
$shopCartItems = getCartItemsByShop();

$cartTotal = getCartTotal();
$totalDeliveryCharge = getTotalDeliveryCharge();
$grandTotal = $cartTotal + $totalDeliveryCharge;

// Handle cart updates if form submitted
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update cart quantities
        foreach ($_POST['quantity'] as $productId => $quantity) {
            updateCartItem($productId, (int)$quantity);
        }
        
        // Refresh cart data
        $shopCartItems = getCartItemsByShop();
        $cartTotal = getCartTotal();
        $totalDeliveryCharge = getTotalDeliveryCharge();
        $grandTotal = $cartTotal + $totalDeliveryCharge;
        
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
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="../../Includes/footer-partners.css">
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
        
        .shop-cart-section {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .shop-header h3 {
            margin: 0;
            color: #333;
        }
        
        .delivery-badge {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .cart-summary {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 2px solid #ddd;
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        
        .text-right {
            text-align: right;
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
        <h2>Shopping Cart</h2>
        
        <?php displayFlashMessage(); ?>
        
        <?php if (empty($shopCartItems)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                
                <a href="products.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <?php foreach ($shopCartItems as $shopId => $shopCart): ?>
                    <div class="shop-cart-section">
                        <div class="shop-header">
                            <h3><?php echo $shopCart['shop_name']; ?></h3>
                            <span class="delivery-badge">Delivery: <?php echo formatPrice($shopCart['delivery_charge']); ?></span>
                        </div>
                        
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
                                <?php foreach ($shopCart['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="../../Uploads/products/<?php echo !empty($item['image']) ? $item['image'] : 'no-image.jpg'; ?>" alt="<?php echo $item['name']; ?>" class="product-img">
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
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Shop Subtotal:</strong></td>
                                    <td><?php echo formatPrice($shopCart['subtotal']); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach; ?>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Charges:</span>
                        <span><?php echo formatPrice($totalDeliveryCharge); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span><?php echo formatPrice($grandTotal); ?></span>
                    </div>
                </div>
                
                <div class="cart-buttons">
                    <a href="products.php" class="btn">Continue Shopping</a>
                    <button type="submit" name="update_cart" class="btn">Update Cart</button>
                    <a href="multi_shop_checkout.php" class="btn">Checkout</a>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-main">
                    <p>&copy; 2025 Nitto Proyojon. All rights reserved.</p>
                </div>
                <div class="partnership-opportunities">
                    <h4>Interested in partnering with us?</h4>
                    <div class="partner-options">
                        <a href="../../Authentication/shop_owner_register.html" class="partner-link">
                            <span class="partner-icon">🏪</span>
                            <span>Become a Shop Owner</span>
                        </a>
                        <a href="../../Authentication/delivery_register.html" class="partner-link">
                            <span class="partner-icon">🚚</span>
                            <span>Join as Delivery Personnel</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
