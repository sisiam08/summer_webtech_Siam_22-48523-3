<?php
// Include database connection
require_once 'db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect user
function redirect($location) {
    header("Location: $location");
    exit;
}

// Function to set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        
        echo "<div class='$type'>$message</div>";
        
        // Remove the flash message
        unset($_SESSION['flash']);
    }
}

// Function to format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM users WHERE id = $userId";
    return fetchOne($sql);
}

// Function to check if email exists
function emailExists($email) {
    global $conn;
    $email = sanitize($email);
    
    $sql = "SELECT COUNT(*) as count FROM users WHERE email = '$email'";
    $result = fetchOne($sql);
    
    return $result['count'] > 0;
}

// Function to register a new user
function registerUser($name, $email, $password) {
    global $conn;
    
    // Sanitize inputs
    $name = sanitize($name);
    $email = sanitize($email);
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashedPassword', 'customer')";
    
    if (executeQuery($sql)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

// Function to login user
function loginUser($email, $password) {
    global $conn;
    
    // Sanitize input
    $email = sanitize($email);
    
    // Get user
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $user = fetchOne($sql);
    
    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'] ?? 'customer';
        
        // Debug: Log session data after setting
        error_log("Session data after login: " . json_encode($_SESSION));
        
        return true;
    }
    
    return false;
}

// Function to logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

// Cart functions

// Function to add item to cart
function addToCart($productId, $quantity = 1) {
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already in cart
    if (isset($_SESSION['cart'][$productId])) {
        // Increment quantity
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        // Add new product to cart
        $_SESSION['cart'][$productId] = $quantity;
    }
}

// Function to update cart item quantity
function updateCartItem($productId, $quantity) {
    if (isset($_SESSION['cart'][$productId])) {
        if ($quantity <= 0) {
            removeFromCart($productId);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }
}

// Function to remove item from cart
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

// Function to get cart items with product details
function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $cartItems = [];
    
    foreach ($_SESSION['cart'] as $productId => $item) {
        // Handle both old and new cart formats
        if (is_array($item) && isset($item['quantity'])) {
            $quantity = $item['quantity'];
        } else {
            $quantity = $item; // Old format where cart item is just a quantity
        }
        
        $product = getProductById($productId);
        
        if ($product) {
            $product['quantity'] = $quantity;
            $product['total'] = $product['price'] * $quantity;
            $cartItems[] = $product;
        }
    }
    
    return $cartItems;
}

// Function to get cart total
function getCartTotal() {
    $cartItems = getCartItems();
    $total = 0;
    
    foreach ($cartItems as $item) {
        $total += $item['total'];
    }
    
    return $total;
}

// Function to clear cart
function clearCart() {
    $_SESSION['cart'] = [];
}

// Product functions

// Function to get all products
function getAllProducts() {
    $sql = "SELECT * FROM products ORDER BY name";
    return fetchAll($sql);
}

// Function to get product by ID
function getProductById($id) {
    $id = (int)$id;
    $sql = "SELECT * FROM products WHERE id = $id";
    return fetchOne($sql);
}

// Function to get products by category
function getProductsByCategory($categoryId) {
    $categoryId = (int)$categoryId;
    $sql = "SELECT * FROM products WHERE category_id = $categoryId ORDER BY name";
    return fetchAll($sql);
}

// Function to get featured products
function getFeaturedProducts($limit = 4) {
    $limit = (int)$limit;
    $sql = "SELECT * FROM products WHERE is_featured = 1 LIMIT $limit";
    return fetchAll($sql);
}

// Category functions

// Function to get all categories
function getAllCategories() {
    $sql = "SELECT * FROM categories ORDER BY name";
    return fetchAll($sql);
}

// Function to get category by ID
function getCategoryById($id) {
    $id = (int)$id;
    $sql = "SELECT * FROM categories WHERE id = $id";
    return fetchOne($sql);
}

// Function to search products
function searchProducts($keyword) {
    $keyword = sanitize($keyword);
    $sql = "SELECT * FROM products 
            WHERE name LIKE '%$keyword%' 
            OR description LIKE '%$keyword%' 
            ORDER BY name";
    return fetchAll($sql);
}

// Order functions

// Function to create order
function createOrder($userId, $totalAmount, $shippingAddress, $paymentMethod) {
    global $conn;
    
    $userId = (int)$userId;
    $totalAmount = (float)$totalAmount;
    $shippingAddress = sanitize($shippingAddress);
    $paymentMethod = sanitize($paymentMethod);
    
    $sql = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
            VALUES ($userId, $totalAmount, '$shippingAddress', '$paymentMethod')";
    
    if (executeQuery($sql)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

// Function to add order item
function addOrderItem($orderId, $productId, $quantity, $price) {
    $orderId = (int)$orderId;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    $price = (float)$price;
    
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES ($orderId, $productId, $quantity, $price)";
    
    return executeQuery($sql);
}

// Function to get user orders
function getUserOrders($userId) {
    $userId = (int)$userId;
    $sql = "SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC";
    return fetchAll($sql);
}

// Function to get order details
function getOrderDetails($orderId) {
    $orderId = (int)$orderId;
    $sql = "SELECT * FROM orders WHERE id = $orderId";
    return fetchOne($sql);
}

// Function to get order items
function getOrderItems($orderId) {
    $orderId = (int)$orderId;
    $sql = "SELECT oi.*, p.name, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = $orderId";
    return fetchAll($sql);
}
?>
