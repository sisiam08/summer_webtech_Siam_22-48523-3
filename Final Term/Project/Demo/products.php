<?php
// Initialize session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'helpers.php';

// Get category filter if provided
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$shopId = isset($_GET['shop']) ? (int)$_GET['shop'] : null;

// Include shop functions
require_once 'includes/shop_functions.php';
require_once 'includes/multi_shop_notice.php';
require_once 'includes/cart_utils.php';

// Ensure cart has the correct structure
normalizeCartStructure();

// Get products based on filters
if ($shopId) {
    $products = getProductsByShop($shopId);
    $shop = getShopById($shopId);
    $pageTitle = 'Products from ' . ($shop ? $shop['name'] : 'Shop not found');
} elseif ($categoryId) {
    $products = getProductsByCategoryWithShopInfo($categoryId);
    $category = getCategoryById($categoryId);
    $pageTitle = 'Products - ' . ($category ? $category['name'] : 'Category not found');
} else {
    $products = getAllProductsWithShopInfo();
    $pageTitle = 'All Products';
}

// Get all categories for the sidebar
$categories = getAllCategories();

// Get all shops for the sidebar
$shops = getAllShops();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php addMultiShopNoticeStyles(); ?>
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
            margin-top: 1.5rem;
        }
        
        .sidebar h3:first-child {
            margin-top: 0;
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
        
        .shop-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(76, 175, 80, 0.85);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .product-card {
            position: relative;
        }
        
        /* Cart Badge Styles */
        .cart-link {
            position: relative;
        }
        
        .cart-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #ff4500;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Floating Cart Styles */
        .floating-cart {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1000;
            background-color: #4CAF50;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .floating-cart:hover {
            transform: scale(1.1);
        }
        
        .floating-cart svg {
            width: 30px;
            height: 30px;
            fill: white;
        }
        
        .floating-cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4500;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* Add to Cart Notification */
        .add-to-cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 1001;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .add-to-cart-notification.show {
            opacity: 1;
            transform: translateY(0);
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
                    <li>
                        <a href="cart.php" class="cart-link">
                            Cart
                            <?php
                            // Get cart count using the utility function
                            $cartCount = calculateCartCount();
                            ?>
                            ?>
                            <span class="cart-badge" <?php echo $cartCount > 0 ? '' : 'style="display: none;"'; ?>>
                                <?php echo $cartCount; ?>
                            </span>
                        </a>
                    </li>
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
                
                <h3>Shops</h3>
                <ul>
                    <li><a href="products.php">All Shops</a></li>
                    <?php foreach ($shops as $shop): ?>
                        <li>
                            <a href="products.php?shop=<?php echo $shop['id']; ?>">
                                <?php echo $shop['name']; ?>
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
                                <img src="uploads/products/<?php echo !empty($product['image']) ? $product['image'] : 'no-image.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                                <div class="shop-badge"><?php echo $product['shop_name'] ?? 'Unknown Shop'; ?></div>
                                <h3><?php echo $product['name']; ?></h3>
                                <p class="price"><?php echo formatPrice($product['price']); ?></p>
                                <button type="button" class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
