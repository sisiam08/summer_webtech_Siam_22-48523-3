<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Get category filter if provided
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Get products based on category filter
if ($categoryId) {
    $products = getProductsByCategory($categoryId);
    $category = getCategoryById($categoryId);
    $pageTitle = 'Products - ' . ($category ? $category['name'] : 'Category not found');
} else {
    $products = getAllProducts();
    $pageTitle = 'All Products';
}

// Get all categories for the sidebar
$categories = getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Online Grocery Store</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .products-container {
            display: flex;
            gap: 2rem;
        }
        
        .sidebar {
            width: 20%;
            background: #fff;
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .sidebar h3 {
            margin-bottom: 1rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.5rem;
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar a {
            color: #333;
            text-decoration: none;
        }
        
        .sidebar a:hover {
            color: #4CAF50;
        }
        
        .product-content {
            width: 80%;
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
        <?php displayFlashMessage(); ?>
        
        <h2><?php echo $pageTitle; ?></h2>
        
        <div class="products-container">
            <aside class="sidebar">
                <h3>Categories</h3>
                <ul>
                    <li><a href="products.php">All Products</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="products.php?category=<?php echo $cat['id']; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            
            <div class="product-content">
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <p>No products found.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                <h3><?php echo $product['name']; ?></h3>
                                <p class="price"><?php echo formatPrice($product['price']); ?></p>
                                <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
