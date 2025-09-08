<?php
// Initialize session
require_once 'includes/session.php';

// Include required files
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/functions.php';
require_once 'includes/cart_utils.php';

// Ensure cart has the correct structure
normalizeCartStructure();

// Get featured products
$featuredProducts = getFeaturedProducts(4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/footer-partners.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li>
                        <a href="cart.php" class="cart-link">
                            Cart
                            <?php
                            // Get cart count using the utility function
                            $cartCount = calculateCartCount();
                            ?>
                            ?>
                            ?>
                            <span class="cart-badge" <?php echo $cartCount > 0 ? '' : 'style="display: none;"'; ?>>
                                <?php echo $cartCount; ?>
                            </span>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.html">Login</a></li>
                        <li><a href="register.html">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h2>Fresh Groceries Delivered to Your Doorstep</h2>
                <p>Shop from our wide range of fresh fruits, vegetables, dairy products, and more.</p>
                <a href="products.php" class="btn">Shop Now</a>
            </div>
        </section>

        <section class="featured-products">
            <div class="container">
                <h2>Featured Products</h2>
                <div class="product-grid">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <h3><?php echo $product['name']; ?></h3>
                            <p class="price"><?php echo formatPrice($product['price']); ?></p>
                            <button class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Floating Cart -->
    <a href="cart.php" class="floating-cart">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        <span class="floating-cart-count" <?php echo $cartCount > 0 ? '' : 'style="display: none;"'; ?>>
            <?php echo $cartCount; ?>
        </span>
    </a>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-main">
                    <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
                </div>
                <div class="partnership-opportunities">
                    <h4>Interested in partnering with us?</h4>
                    <div class="partner-options">
                        <a href="shop_owner/register.html" class="partner-link">
                            <span class="partner-icon">🏪</span>
                            <span>Become a Shop Owner</span>
                        </a>
                        <a href="delivery/apply.html" class="partner-link">
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