<?php
// Include database connection, session functions, and helper functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/global_functions.php';

// Functions.php contains application-specific functions that aren't general utilities

// Function to get current user data
function getCurrentUser() {
    global $conn;
    
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return false;
}

// Function to get product by ID
function getProductById($productId) {
    global $conn;
    
    // Log the product request for debugging
    error_log("Getting product by ID: $productId");
    
    try {
        $sql = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            error_log("Found product: " . json_encode($product));
            return $product;
        }
    } catch (Exception $e) {
        error_log("Error getting product by ID: " . $e->getMessage());
    }
    
    return false;
}

// Function to get cart items
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

// Function to search products
function searchProducts($keyword) {
    global $conn;
    
    $keyword = "%" . sanitize($keyword) . "%";
    
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
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
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $userId, $totalAmount, $shippingAddress, $paymentMethod);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

// Function to add order item
function addOrderItem($orderId, $productId, $quantity, $price) {
    global $conn;
    
    $orderId = (int)$orderId;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    $price = (float)$price;
    
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
    
    return $stmt->execute();
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

// Function to get products by category
function getProductsByCategory($categoryId) {
    global $conn;
    
    $sql = "SELECT * FROM products WHERE category_id = ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to get featured products
function getFeaturedProducts($limit = 4) {
    global $conn;
    
    $sql = "SELECT * FROM products WHERE is_featured = 1 LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to get all categories
function getAllCategories() {
    global $conn;
    
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Function to get category by ID
function getCategoryById($id) {
    global $conn;
    
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Function to get all products
function getAllProducts() {
    global $conn;
    
    $sql = "SELECT * FROM products ORDER BY name";
    $result = $conn->query($sql);
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Cart Functions

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

// Function to clear cart
function clearCart() {
    $_SESSION['cart'] = [];
}

// Authentication Functions

// Function to check if email exists
function emailExists($email) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'] > 0;
    }
    
    return false;
}

// Function to register a new user
function registerUser($name, $email, $password) {
    global $conn;
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

// Function to login user
function loginUser($email, $password) {
    global $conn;
    
    // Get user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $user = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
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
?>
