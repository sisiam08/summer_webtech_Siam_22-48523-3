<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Get featured products
$featuredProducts = getFeaturedProducts(4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Grocery Store</title>
    <link rel="stylesheet" href="styles.css">
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
        <?php displayFlashMessage(); ?>
        
        <section class="hero">
            <h2>Welcome to our Online Grocery Store</h2>
            <p>Find fresh products at the best prices!</p>
            <a href="products.php" class="btn">Shop Now</a>
        </section>

        <section class="featured-products">
            <h2>Featured Products</h2>
            <div class="product-grid">
                <?php if (empty($featuredProducts)): ?>
                    <p>No featured products available.</p>
                <?php else: ?>
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <h3><?php echo $product['name']; ?></h3>
                            <p class="price"><?php echo formatPrice($product['price']); ?></p>
                            <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
