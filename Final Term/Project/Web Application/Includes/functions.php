<?php
// Include database connection, session functions, and helper functions
require_once __DIR__ . "/../Database/database.php";
require_once __DIR__ . "/session_init.php";
require_once __DIR__ . "/global_functions.php";



// Functions.php contains application-specific functions that aren't general utilities


// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is an admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check if user is a shop owner
function isShopOwner() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';
}

// Function to check if user is a delivery person
function isDelivery() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'delivery';
}

// Function to check if user is a customer
function isCustomer() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

// Function to check user role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to get user role
function getUserRole() {
    return isLoggedIn() ? ($_SESSION['user_role'] ?? null) : null;
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
    // Use the global_functions.php implementation if available
    if (function_exists('config')) {
        $symbol = config('site.currency_symbol', '৳');
        return $symbol . ' ' . number_format($price, 2);
    }
    
    // Fallback for backward compatibility
    return '৳ ' . number_format($price, 2);
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
function registerUser($name, $email, $phone, $password) {
    global $conn;
    
    // Sanitize inputs
    $name = sanitize($name);
    $email = sanitize($email);
    $phone = sanitize($phone);
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$hashedPassword', 'customer')";
    
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
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        
        // For shop owners, set shop_id in session
        if ($user['role'] === 'shop_owner') {
            $shopSql = "SELECT id, name FROM shops WHERE owner_id = {$user['id']}";
            $shop = fetchOne($shopSql);
            
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
            }
        }
        
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
        // Handle both old and new cart formats
        if (is_array($_SESSION['cart'][$productId]) && isset($_SESSION['cart'][$productId]['quantity'])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            // Convert old format to new format
            $oldQuantity = $_SESSION['cart'][$productId];
            $_SESSION['cart'][$productId] = [
                'quantity' => $oldQuantity + $quantity,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
    } else {
        // Add new product to cart with new format
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
    }
}

// Function to update cart item quantity
function updateCartItem($productId, $quantity) {
    if (isset($_SESSION['cart'][$productId])) {
        if ($quantity <= 0) {
            removeFromCart($productId);
        } else {
            // Handle both old and new cart formats
            if (is_array($_SESSION['cart'][$productId]) && isset($_SESSION['cart'][$productId]['quantity'])) {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            } else {
                // Convert to new format
                $_SESSION['cart'][$productId] = [
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s')
                ];
            }
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

/**
 * Helper function to get quantity from cart item (handles both old and new formats)
 * @param mixed $cartItem The cart item from $_SESSION['cart']
 * @return int The quantity
 */
function getCartItemQuantity($cartItem) {
    if (is_array($cartItem) && isset($cartItem['quantity'])) {
        return (int)$cartItem['quantity'];
    } else {
        return (int)$cartItem;
    }
}

// Product functions

// Function to get all products
function getAllProducts() {
    $sql = "SELECT * FROM products ORDER BY name";
    return fetchAll($sql);
}

// Function to get product by ID
function getProductById($id) {
    global $conn;
    $id = (int)$id;
    
    // Use prepared statement for better security
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get featured products
function getFeaturedProducts($limit) {
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
