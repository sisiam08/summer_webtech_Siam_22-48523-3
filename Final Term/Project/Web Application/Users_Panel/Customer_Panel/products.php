<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Get category filter if provided
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$shopId = isset($_GET['shop']) ? (int)$_GET['shop'] : null;

// Include consolidated functions
require_once __DIR__ . '/../../Includes/functions.php';
require_once __DIR__ . '/../../Includes/multi_shop_notice.php';

// Ensure cart has the correct structure
normalizeCartStructure();

// Calculate cart count for UI (prevents undefined variable in the template)
$cartCount = calculateCartCount();

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
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="../../Includes/footer-partners.css">
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
        
        /* Search Bar Styles */
        .search-container {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
            max-width: 600px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .search-btn {
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .search-btn:hover {
            background-color: #45a049;
        }
        
        .clear-search {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .clear-search:hover {
            background-color: #da190b;
        }
        
        #search-results {
            margin-top: 20px;
        }
        
        #search-results .product-content {
            width: 100%;
        }
        
        #search-results .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .search-info {
            margin-bottom: 15px;
            color: #666;
            font-style: italic;
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
                    <li><a href="cart.php" class="cart-link">Cart</a></li>
                        
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
        <?php displayFlashMessage(); ?>
        
        <h2><?php echo $pageTitle; ?></h2>
        
        <!-- Search Bar -->
        <div class="search-container">
            <form class="search-form" id="search-form">
                <input type="text" 
                       class="search-input" 
                       id="search-input" 
                       placeholder="Search products by name or description..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn">Search</button>
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="products.php" class="clear-search">Clear Search</a>
                <?php endif; ?>
            </form>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <div class="search-info">
                    Showing search results for: "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Search Results Container -->
        <div id="search-results"></div>
        
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
                                <img src="../../Uploads/products/<?php echo !empty($product['image']) ? $product['image'] : 'no-image.jpg'; ?>" alt="<?php echo $product['name']; ?>">
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

    <script src="../../Includes/script.js"></script>
    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('search-form');
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            const productsContainer = document.querySelector('.products-container');
            
            // Handle search form submission
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            // Handle real-time search (optional - search as user types)
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        performSearch();
                    }, 500); // Wait 500ms after user stops typing
                } else if (query.length === 0) {
                    // Clear search results and show original products
                    searchResults.innerHTML = '';
                    productsContainer.style.display = 'flex';
                }
            });
            
            function performSearch() {
                const query = searchInput.value.trim();
                
                if (query.length < 1) {
                    // Clear search results and show original products
                    searchResults.innerHTML = '';
                    productsContainer.style.display = 'flex';
                    return;
                }
                
                // Show loading message
                searchResults.innerHTML = '<p>Searching...</p>';
                productsContainer.style.display = 'none';
                
                // Create AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'search_products.php?keyword=' + encodeURIComponent(query), true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onload = function() {
                    if (this.status === 200) {
                        searchResults.innerHTML = this.responseText;
                        
                        // Re-attach event listeners to new "Add to Cart" buttons
                        const newAddToCartButtons = searchResults.querySelectorAll('.add-to-cart-btn');
                        newAddToCartButtons.forEach(button => {
                            button.addEventListener('click', function(e) {
                                e.preventDefault();
                                const productId = this.getAttribute('data-product-id');
                                if (typeof addToCart === 'function') {
                                    addToCart(productId);
                                } else {
                                    // Fallback if addToCart function is not available
                                    console.log('Adding product to cart:', productId);
                                }
                            });
                        });
                    } else {
                        searchResults.innerHTML = '<p>Error performing search. Please try again.</p>';
                    }
                };
                
                xhr.onerror = function() {
                    searchResults.innerHTML = '<p>Network error. Please check your connection.</p>';
                };
                
                xhr.send();
            }
            
            // Check if there's a search query in URL on page load
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search');
            if (searchQuery) {
                searchInput.value = searchQuery;
                performSearch();
            }
        });
    </script>
</body>
</html>
