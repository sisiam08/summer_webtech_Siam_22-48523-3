<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$isCustomer = $isLoggedIn && isCustomer();

// Only redirect admin and delivery_man to their panels
// Allow shop_owners to browse as customers
if ($isLoggedIn && !$isCustomer) {
    $user = getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            redirect('../Admin_Panel/admin_index.php');
            break;
        case 'delivery_man':
            redirect('../Delivery_Panel/delivery_index.php');
            break;
        // Remove shop_owner case to allow them to browse as customers
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nitto Proyojon</title>
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="../../Includes/footer-partners.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Cart notification styles */
        .add-to-cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            font-weight: 500;
        }
        
        .add-to-cart-notification.show {
            transform: translateX(0);
        }
        
        /* Cart badge styles */
        .cart-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            display: none;
        }
        
        /* Enhanced product card styles for featured products */
        .featured-products .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .featured-products .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .featured-products .product-card img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
            background-color: #f5f5f5;
            border: none;
        }
        
        /* Fallback for broken images */
        .featured-products .product-card img[src=""], 
        .featured-products .product-card img:not([src]) {
            background: #f5f5f5 url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMiAxNkgyOFYyNEgxMlYxNloiIGZpbGw9IiNEREREREQiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIyMCIgcj0iMiIgZmlsbD0iI0RERERERCIvPgo8L3N2Zz4K') no-repeat center center;
            background-size: 40px 40px;
        }
        
        .featured-products .product-card h3 {
            margin: 8px 0;
            font-size: 15px;
            color: #333;
        }
        
        .featured-products .product-card .price {
            font-size: 16px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 8px;
        }
        
        .featured-products .product-card .add-to-cart {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s ease;
        }
        
        .featured-products .product-card .add-to-cart:hover {
            background: #45a049;
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
                    <li>
                        <a href="cart.php">Cart <span class="cart-badge">0</span></a>
                    </li>
                    
                    <?php if ($isLoggedIn): ?>
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
        <!-- Slide Banner Section -->
        <section class="banner-slider">
            <div class="slider-container">
                <div class="slides" id="banner-slides">
                    <!-- Banners will be loaded dynamically -->
                    <div class="slide loading-slide">
                        <div class="slide-content">
                            <h2>Loading...</h2>
                            <p>Please wait while we load the latest offers</p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation arrows -->
                <button class="nav-arrow prev-arrow" onclick="changeSlide(-1)">
                    <span>‹</span>
                </button>
                <button class="nav-arrow next-arrow" onclick="changeSlide(1)">
                    <span>›</span>
                </button>
                
                <!-- Slide indicators -->
                <div class="slide-indicators" id="slide-indicators">
                    <!-- Indicators will be added dynamically -->
                </div>
            </div>
        </section>

        <section class="featured-products">
            <h2>Featured Products</h2>
            <div class="product-grid" id="featured-products">
                <!-- Products will be loaded dynamically -->
                <p>Loading featured products...</p>
            </div>
        </section>

        <!-- Customer Dashboard Content (shown only when logged in) -->
        <?php if ($isLoggedIn && $isCustomer): ?>
        <div class="customer-dashboard">
            <div class="dashboard-header">
                <div class="welcome-message">
                    <h2>Welcome back, Customer!</h2>
                    <p>Manage your orders, track deliveries, and shop for fresh groceries</p>
                </div>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="icon" style="color: #4CAF50;">🛒</div>
                    <h3 id="total-orders">-</h3>
                    <p>Total Orders</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon" style="color: #FF9800;">📦</div>
                    <h3 id="pending-orders">-</h3>
                    <p>Pending Orders</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon" style="color: #2196F3;">💰</div>
                    <h3 id="total-spent">-</h3>
                    <p>Total Spent</p>
                </div>
                
                <div class="stat-card">
                    <div class="icon" style="color: #9C27B0;">❤️</div>
                    <h3 id="wishlist-count">-</h3>
                    <p>Wishlist Items</p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="products.php" class="action-card">
                    <div class="icon" style="color: #4CAF50;">🛒</div>
                    <h3>Shop Now</h3>
                    <p>Browse our fresh products</p>
                </a>
                
                <a href="orders.php" class="action-card">
                    <div class="icon" style="color: #FF9800;">📦</div>
                    <h3>My Orders</h3>
                    <p>Track your orders</p>
                </a>
                
                <a href="account.php" class="action-card">
                    <div class="icon" style="color: #2196F3;">👤</div>
                    <h3>My Account</h3>
                    <p>Manage your profile</p>
                </a>
                
                <a href="wishlist.php" class="action-card">
                    <div class="icon" style="color: #9C27B0;">❤️</div>
                    <h3>Wishlist</h3>
                    <p>Your favorite items</p>
                </a>
            </div>

            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <div id="recent-orders-list">
                    <p>Loading recent orders...</p>
                </div>
            </div>
        </div>
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

    <script src="../../Includes/script.js"></script>
    <script src="dashboard.js"></script>
    <script>
    // Banner Slider Variables
    let currentSlide = 0;
    let slides = [];
    let slideInterval;
    
    // Banner Slider Functions
    function loadBanners() {
        fetch('get_banners.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.banners.length > 0) {
                    displayBanners(data.banners);
                    startAutoSlide();
                } else {
                    // Show fallback content if no banners
                    showFallbackBanner();
                }
            })
            .catch(error => {
                console.error('Error loading banners:', error);
                showFallbackBanner();
            });
    }
    
    function displayBanners(banners) {
        const slidesContainer = document.getElementById('banner-slides');
        const indicatorsContainer = document.getElementById('slide-indicators');
        
        // Clear loading content
        slidesContainer.innerHTML = '';
        indicatorsContainer.innerHTML = '';
        
        slides = banners;
        
        // Create slides
        banners.forEach((banner, index) => {
            const slide = document.createElement('div');
            slide.className = 'slide';
            
            slide.innerHTML = `
                <img src="${banner.image_url}" alt="${banner.title}" onerror="this.style.display='none'">
                <div class="slide-content">
                    <h2>${banner.title}</h2>
                    <p>${banner.subtitle}</p>
                    ${banner.link_url ? `<a href="${banner.link_url}" class="btn">${banner.button_text || 'Learn More'}</a>` : ''}
                </div>
            `;
            
            slidesContainer.appendChild(slide);
            
            // Create indicator
            const indicator = document.createElement('div');
            indicator.className = `indicator ${index === 0 ? 'active' : ''}`;
            indicator.onclick = () => goToSlide(index);
            indicatorsContainer.appendChild(indicator);
        });
        
        // Show first slide
        updateSlidePosition();
    }
    
    function showFallbackBanner() {
        const slidesContainer = document.getElementById('banner-slides');
        slidesContainer.innerHTML = `
            <div class="slide loading-slide">
                <div class="slide-content">
                    <h2>Welcome to Nitto Proyojon</h2>
                    <p>Find fresh products at the best prices!</p>
                    <a href="products.php" class="btn">Shop Now</a>
                </div>
            </div>
        `;
        
        // Hide indicators and arrows
        document.getElementById('slide-indicators').style.display = 'none';
        document.querySelectorAll('.nav-arrow').forEach(arrow => arrow.style.display = 'none');
    }
    
    function changeSlide(direction) {
        if (slides.length <= 1) return;
        
        currentSlide += direction;
        
        if (currentSlide >= slides.length) {
            currentSlide = 0;
        } else if (currentSlide < 0) {
            currentSlide = slides.length - 1;
        }
        
        updateSlidePosition();
        resetAutoSlide();
    }
    
    function goToSlide(index) {
        if (slides.length <= 1) return;
        
        currentSlide = index;
        updateSlidePosition();
        resetAutoSlide();
    }
    
    function updateSlidePosition() {
        const slidesContainer = document.getElementById('banner-slides');
        const indicators = document.querySelectorAll('.indicator');
        
        slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        
        // Update indicators
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentSlide);
        });
    }
    
    function startAutoSlide() {
        if (slides.length <= 1) return;
        
        slideInterval = setInterval(() => {
            changeSlide(1);
        }, 5000); // Change slide every 5 seconds
    }
    
    function resetAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
            startAutoSlide();
        }
    }
    
    // Pause auto-slide on hover
    document.addEventListener('DOMContentLoaded', function() {
        const sliderContainer = document.querySelector('.slider-container');
        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', () => {
                if (slideInterval) clearInterval(slideInterval);
            });
            
            sliderContainer.addEventListener('mouseleave', () => {
                startAutoSlide();
            });
        }
    });
    </script>
    <script src="../../Includes/script.js"></script>
    <script>
        // Re-attach cart functionality to dynamically loaded products
        function attachCartEventListeners() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            addToCartButtons.forEach(button => {
                // Remove existing listeners to prevent duplicates
                button.replaceWith(button.cloneNode(true));
            });
            
            // Re-attach listeners to all add-to-cart buttons
            const newAddToCartButtons = document.querySelectorAll('.add-to-cart');
            newAddToCartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-id');
                    if (typeof addToCart === 'function') {
                        addToCart(productId);
                    } else {
                        console.error('addToCart function not available');
                    }
                });
            });
        }
        
        // Enhanced featured products loading with cart functionality
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize cart count
            initializeCartCount();
            
            // Load banners
            loadBanners();
            
            // Load featured products
            const container = document.getElementById('featured-products');
            try {
                const res = await fetch('get_featured_products.php');
                if (!res.ok) throw new Error('Network response was not ok');
                const data = await res.text();
                container.innerHTML = data;
                
                // Attach cart event listeners to newly loaded products
                setTimeout(() => {
                    attachCartEventListeners();
                }, 100);
                
            } catch (err) {
                console.error('Error loading featured products:', err);
                container.innerHTML = '<p class="error">Error loading products. Please try again later.</p>';
            }
        });
        
        // Initialize cart count from session/localStorage
        async function initializeCartCount() {
            try {
                const response = await fetch('get_cart_count.php');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        updateCartCountDisplay(data.count);
                    }
                }
            } catch (error) {
                console.log('Could not load cart count:', error);
            }
        }
    </script>
</body>
</html>