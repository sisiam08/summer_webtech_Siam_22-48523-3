<?php
/**
 * CONSOLIDATED FUNCTIONS FILE
 * 
 * This file contains all application functions:
 * - User authentication and role management
 * - Cart utility functions (from cart_utils.php)
 * - Shop management functions (from shop_functions.php)
 * - Order processing and database operations
 * - Flash messages and general utilities
 * 
 * Last consolidated: September 16, 2025
 */

// Include database connection, session functions, and helper functions
require_once __DIR__ . "/../Database/database.php";
require_once __DIR__ . "/session_init.php";
require_once __DIR__ . "/global_functions.php";

// ========================================
// USER AUTHENTICATION & ROLE FUNCTIONS
// ========================================

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
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'delivery_man';
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

// Function to get shop ID for logged-in shop owner
function getShopIdForOwner() {
    if (!isShopOwner()) {
        return null;
    }
    
    // Check if shop_id is already in session
    if (isset($_SESSION['shop_id'])) {
        return $_SESSION['shop_id'];
    }
    
    // Try to get shop_id from database
    global $conn;
    try {
        $conn = connectDB();
        $query = "SELECT id FROM shops WHERE owner_id = :user_id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop) {
            $_SESSION['shop_id'] = $shop['id']; // Store in session for future use
            return $shop['id'];
        }
    } catch (Exception $e) {
        error_log("Error getting shop ID for owner: " . $e->getMessage());
    }
    
    return null;
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
    
    $userId = $_SESSION['user_id'];
    
    // Try to use PDO connection first
    try {
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback to mysqli if PDO fails
        global $conn;
        $sql = "SELECT * FROM users WHERE id = $userId";
        return fetchOne($sql);
    }
}

// Function to check if email exists
function emailExists($email) {
    global $conn;
    
    // Sanitize email
    $email = sanitize($email);
    
    // Check if email exists
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = fetchOne($sql);
    
    return $result !== false;
}

// Function to register user
function registerUser($name, $email, $phone, $password) {
    global $conn;
    
    // Sanitize inputs
    $name = sanitize($name);
    $email = sanitize($email);
    $phone = sanitize($phone);
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$hashedPassword', 'customer')";
    
    if (executeQuery($sql)) {
        return $conn->insert_id;
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

// ========================================
// CART UTILITY FUNCTIONS
// ========================================

/**
 * Get the total number of items in the cart
 * This function handles both old and new cart formats
 *
 * @return int The total number of items
 */
function calculateCartCount() {
    $cartCount = 0;
    
    // First check if user is logged in and using database cart
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../Database/database.php';
            $conn = connectDB();
            
            $stmt = $conn->prepare("SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row['cart_count'] ?? 0;
            }
        } catch (Exception $e) {
            // If error (like table doesn't exist), fall back to session cart
            error_log("Database cart error: " . $e->getMessage());
        }
    }
    
    // Use session cart
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productId => $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $cartCount += $item['quantity'];
            } else {
                $cartCount += $item;
            }
        }
    }
    
    return $cartCount;
}

/**
 * Ensure cart is using the new structure
 * Converts old format (product_id => quantity) to new format (product_id => ['quantity' => quantity, 'added_at' => timestamp])
 */
function normalizeCartStructure() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        return;
    }
    
    foreach ($_SESSION['cart'] as $productId => $item) {
        if (!is_array($item)) {
            // Convert old format to new format
            $_SESSION['cart'][$productId] = [
                'quantity' => $item,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}

/**
 * Add product to cart or update quantity
 * 
 * @param int $productId The product ID
 * @param int $quantity The quantity to add
 * @return bool Success or failure
 */
function addProductToCart($productId, $quantity = 1) {
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($productId <= 0 || $quantity <= 0) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Ensure cart has the correct structure
    normalizeCartStructure();
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return true;
}

/**
 * Remove item from cart
 * 
 * @param int $productId The product ID to remove
 * @return bool Success or failure
 */
function removeProductFromCart($productId) {
    $productId = (int)$productId;
    
    if ($productId <= 0) {
        return false;
    }
    
    // Check if user is logged in and use database cart
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../Database/database.php';
            $conn = connectDB();
            
            $query = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Database cart remove error: " . $e->getMessage());
            // Fall back to session cart
        }
    }
    
    // Use session cart
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    
    return false;
}

/**
 * Clear the entire cart
 */
function emptyShoppingCart() {
    $_SESSION['cart'] = [];
}

/**
 * Get cart items grouped by shop
 * @return array Organized cart data by shop
 */
function getCartItemsByShop() {
    $shopCartItems = [];
    
    // Check if user is logged in and use database cart
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../Database/database.php';
            $conn = connectDB();
            
            // Get cart items from database with product and shop details
            $query = "SELECT c.id as cart_id, c.product_id, c.quantity, 
                            p.name, p.price, p.image, p.shop_id,
                            s.name as shop_name, s.delivery_charge
                     FROM cart c
                     JOIN products p ON c.product_id = p.id
                     LEFT JOIN shops s ON p.shop_id = s.id
                     WHERE c.user_id = :user_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by shop
            foreach ($cartItems as $item) {
                $shopId = $item['shop_id'] ?? 0;
                $shopName = $item['shop_name'] ?? 'Default Shop';
                $deliveryCharge = $item['delivery_charge'] ?? 50;
                
                if (!isset($shopCartItems[$shopId])) {
                    $shopCartItems[$shopId] = [
                        'shop_name' => $shopName,
                        'delivery_charge' => $deliveryCharge,
                        'items' => [],
                        'subtotal' => 0
                    ];
                }
                
                $itemTotal = $item['price'] * $item['quantity'];
                $shopCartItems[$shopId]['items'][] = [
                    'id' => $item['product_id'],
                    'cart_id' => $item['cart_id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'image' => $item['image'],
                    'total' => $itemTotal
                ];
                
                $shopCartItems[$shopId]['subtotal'] += $itemTotal;
            }
            
        } catch (Exception $e) {
            error_log("Database cart error: " . $e->getMessage());
            // Fall back to session cart
            return getSessionCartItemsByShop();
        }
    } else {
        // Use session cart for guest users
        return getSessionCartItemsByShop();
    }
    
    return $shopCartItems;
}

/**
 * Get session cart items grouped by shop
 * @return array Organized session cart data by shop
 */
function getSessionCartItemsByShop() {
    $shopCartItems = [];
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return $shopCartItems;
    }
    
    require_once __DIR__ . '/../Database/database.php';
    $conn = connectDB();
    
    // Get product details for items in session cart
    $productIds = array_keys($_SESSION['cart']);
    if (!empty($productIds)) {
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $query = "SELECT p.id, p.name, p.price, p.image, p.shop_id,
                        s.name as shop_name, s.delivery_charge
                 FROM products p
                 LEFT JOIN shops s ON p.shop_id = s.id
                 WHERE p.id IN ($placeholders)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by shop
        foreach ($products as $product) {
            $productId = $product['id'];
            $quantity = $_SESSION['cart'][$productId];
            
            // Handle both old and new cart formats
            if (is_array($quantity)) {
                $quantity = $quantity['quantity'] ?? 1;
            }
            
            $shopId = $product['shop_id'] ?? 0;
            $shopName = $product['shop_name'] ?? 'Default Shop';
            $deliveryCharge = $product['delivery_charge'] ?? 50;
            
            if (!isset($shopCartItems[$shopId])) {
                $shopCartItems[$shopId] = [
                    'shop_name' => $shopName,
                    'delivery_charge' => $deliveryCharge,
                    'items' => [],
                    'subtotal' => 0
                ];
            }
            
            $itemTotal = $product['price'] * $quantity;
            $shopCartItems[$shopId]['items'][] = [
                'id' => $productId,
                'cart_id' => $productId, // For session cart, use product_id as cart_id
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image'],
                'total' => $itemTotal
            ];
            
            $shopCartItems[$shopId]['subtotal'] += $itemTotal;
        }
    }
    
    return $shopCartItems;
}

/**
 * Get cart total
 * @return float Total cart amount
 */
function getCartTotal() {
    $total = 0;
    $shopCartItems = getCartItemsByShop();
    
    foreach ($shopCartItems as $shopCart) {
        $total += $shopCart['subtotal'];
    }
    
    return $total;
}

/**
 * Get total delivery charge
 * @return float Total delivery charges
 */
function getTotalDeliveryCharge() {
    $totalDelivery = 0;
    $shopCartItems = getCartItemsByShop();
    
    foreach ($shopCartItems as $shopCart) {
        $totalDelivery += $shopCart['delivery_charge'];
    }
    
    return $totalDelivery;
}

/**
 * Update cart item quantity
 * @param int $productId Product ID
 * @param int $quantity New quantity
 * @return bool Success or failure
 */
function updateCartItem($productId, $quantity) {
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($productId <= 0 || $quantity < 0) {
        return false;
    }
    
    if ($quantity == 0) {
        return removeProductFromCart($productId);
    }
    
    // Check if user is logged in and use database cart
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../Database/database.php';
            $conn = connectDB();
            
            $query = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Database cart update error: " . $e->getMessage());
            // Fall back to session cart
        }
    }
    
    // Use session cart
    if (isset($_SESSION['cart'][$productId])) {
        if (is_array($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        return true;
    }
    
    return false;
}

// ========================================
// SHOP MANAGEMENT FUNCTIONS
// ========================================

/**
 * Check if multi-shop tables exist
 */
function isMultiShopEnabled() {
    global $conn;
    
    // Check if shops table exists
    $shopsTable = $conn->query("SHOW TABLES LIKE 'shops'");
    
    // Check if products table has shop_id column
    $productsShopId = $conn->query("SHOW COLUMNS FROM products LIKE 'shop_id'");
    
    // Check if at least one shop exists
    $shopsExist = false;
    if ($shopsTable && $shopsTable->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM shops");
        if ($result && $row = $result->fetch_assoc()) {
            $shopsExist = ($row['count'] > 0);
        }
    }
    
    // All conditions must be true for multi-shop to be enabled
    return ($shopsTable && $shopsTable->num_rows > 0) && 
           ($productsShopId && $productsShopId->num_rows > 0) &&
           $shopsExist;
}

/**
 * Get shop information by ID
 */
function getShopById($shopId) {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        return ['id' => 1, 'name' => 'Default Shop', 'delivery_charge' => 5.00];
    }
    
    $sql = "SELECT * FROM shops WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Get all shops
 */
function getAllShops() {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        return [['id' => 1, 'name' => 'Default Shop', 'delivery_charge' => 5.00]];
    }
    
    $sql = "SELECT * FROM shops ORDER BY name";
    $result = $conn->query($sql);
    
    $shops = [];
    while ($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
    
    return $shops;
}

// ========================================
// PRODUCT FUNCTIONS
// ========================================

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

// Function to get all products with shop information
function getAllProductsWithShopInfo() {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        return getAllProducts();
    }
    
    $sql = "SELECT p.*, s.name as shop_name, s.delivery_charge 
            FROM products p 
            LEFT JOIN shops s ON p.shop_id = s.id 
            ORDER BY p.name";
    
    $result = $conn->query($sql);
    $products = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get products by category with shop information
function getProductsByCategoryWithShopInfo($categoryId) {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        return getProductsByCategory($categoryId);
    }
    
    $sql = "SELECT p.*, s.name as shop_name, s.delivery_charge 
            FROM products p 
            LEFT JOIN shops s ON p.shop_id = s.id 
            WHERE p.category_id = ? 
            ORDER BY p.name";
    
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

// Function to get products by shop
function getProductsByShop($shopId) {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        return getAllProducts();
    }
    
    $sql = "SELECT p.*, s.name as shop_name, s.delivery_charge 
            FROM products p 
            LEFT JOIN shops s ON p.shop_id = s.id 
            WHERE p.shop_id = ? 
            ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// ========================================
// CATEGORY FUNCTIONS
// ========================================

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

// ========================================
// ORDER FUNCTIONS
// ========================================

// Function to get user orders
function getUserOrders($userId) {
    $userId = (int)$userId;
    $sql = "SELECT * FROM orders WHERE customer_id = $userId ORDER BY created_at DESC";
    return fetchAll($sql);
}

// Function to get order by ID
function getOrderById($orderId) {
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

/**
 * Create or update customer record in customers table
 */
function createOrUpdateCustomer($conn, $userId, $name, $email, $phone, $address) {
    try {
        // Parse address if it contains city information
        $addressParts = explode(',', $address);
        $street_address = trim($addressParts[0]);
        $city = isset($addressParts[1]) ? trim($addressParts[1]) : '';
        
        // Check user role to determine how to handle customer record
        $userRoleQuery = "SELECT role FROM users WHERE id = ?";
        $userRoleStmt = $conn->prepare($userRoleQuery);
        $userRoleStmt->execute([$userId]);
        $userRole = $userRoleStmt->fetchColumn();
        
        if ($userRole === 'customer') {
            // For actual customers, link to user_id
            $checkSql = "SELECT id FROM customers WHERE user_id = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$userId]);
            $existingCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCustomer) {
                // Update existing customer
                $updateSql = "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, city = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$name, $email, $phone, $street_address, $city, $existingCustomer['id']]);
                
                error_log("Updated customer ID: " . $existingCustomer['id'] . " for customer user ID: $userId");
                return $existingCustomer['id'];
            } else {
                // Create new customer linked to user
                $insertSql = "INSERT INTO customers (user_id, name, email, phone, address, city) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([$userId, $name, $email, $phone, $street_address, $city]);
                
                $customerId = $conn->lastInsertId();
                error_log("Created new customer ID: $customerId for customer user ID: $userId");
                return $customerId;
            }
        } else {
            // For shop owners, delivery personnel, admins - create standalone customer record
            // Check if a standalone customer record already exists with same email for this order context
            $checkEmailSql = "SELECT id FROM customers WHERE user_id IS NULL AND email = ? LIMIT 1";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->execute([$email]);
            $existingEmailCustomer = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingEmailCustomer) {
                // Update existing standalone customer record
                $updateSql = "UPDATE customers SET name = ?, phone = ?, address = ?, city = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$name, $phone, $street_address, $city, $existingEmailCustomer['id']]);
                
                error_log("Updated standalone customer ID: " . $existingEmailCustomer['id'] . " for $userRole user ID: $userId");
                return $existingEmailCustomer['id'];
            } else {
                // Create new standalone customer record (no user_id link)
                $customerName = $name . " (Customer)"; // Distinguish from their shop owner role
                $insertSql = "INSERT INTO customers (user_id, name, email, phone, address, city) VALUES (NULL, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([$customerName, $email, $phone, $street_address, $city]);
                
                $customerId = $conn->lastInsertId();
                error_log("Created standalone customer ID: $customerId for $userRole user ID: $userId");
                return $customerId;
            }
        }
    } catch (Exception $e) {
        error_log("Error creating/updating customer: " . $e->getMessage());
        throw new Exception("Failed to create or update customer record");
    }
}

/**
 * Create multi-shop order
 */
function createMultiShopOrder($customerId, $customerName, $customerEmail, $customerPhone, $deliveryAddress, $paymentMethod) {
    $conn = connectDB(); // This returns a PDO connection
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Step 1: Create or update customer record in customers table
        $actualCustomerId = createOrUpdateCustomer($conn, $customerId, $customerName, $customerEmail, $customerPhone, $deliveryAddress);
        
        // Get cart items grouped by shop
        $shopCartItems = getCartItemsByShop();
        
        if (empty($shopCartItems)) {
            throw new Exception("Cart is empty");
        }
        
        // Calculate totals
        $cartTotal = getCartTotal();
        $totalDeliveryCharge = getTotalDeliveryCharge();
        $grandTotal = $cartTotal + $totalDeliveryCharge;
        
        error_log("Creating orders for user_id: $customerId, customer_id: $actualCustomerId, total: $grandTotal, shops: " . count($shopCartItems));
        
        $createdOrderIds = [];
        
        // Create separate order for each shop
        foreach ($shopCartItems as $shopId => $shopCart) {
            $shopSubtotal = $shopCart['subtotal'];
            $shopDeliveryCharge = $shopCart['delivery_charge'];
            $shopTotal = $shopSubtotal + $shopDeliveryCharge;
            
            // Create order for this shop with customer_id
            $sql = "INSERT INTO orders (user_id, customer_id, shop_id, total_amount, status, shipping_address, payment_method, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $values = [
                $customerId,
                $actualCustomerId,  // Add customer_id reference
                $shopId,
                $shopTotal,
                'pending',
                $deliveryAddress,
                $paymentMethod,
                date('Y-m-d H:i:s')
            ];
            
            $result = $stmt->execute($values);
            
            if (!$result) {
                throw new Exception("Order was not created for shop ID: $shopId");
            }
            
            $orderId = $conn->lastInsertId();
            $createdOrderIds[] = $orderId;
            error_log("Order created with ID: $orderId for shop: $shopId");
            
            // Add order items for this shop
            foreach ($shopCart['items'] as $item) {
                $productId = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$orderId, $productId, $quantity, $price]);
                
                if (!$result) {
                    throw new Exception("Failed to add order item for product ID: $productId");
                }
                
                error_log("Order item added: Order $orderId, Product $productId, Quantity $quantity");
            }
        }
        
        // Clear the cart after successful order creation
        if (isset($_SESSION['user_id'])) {
            try {
                $clearQuery = "DELETE FROM cart WHERE user_id = :user_id";
                $clearStmt = $conn->prepare($clearQuery);
                $clearStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $clearStmt->execute();
                error_log("Database cart cleared for user: " . $_SESSION['user_id']);
            } catch (Exception $e) {
                error_log("Could not clear database cart: " . $e->getMessage());
            }
        }
        $_SESSION['cart'] = [];
        error_log("Session cart cleared");
        
        // Commit transaction
        $conn->commit();
        
        // Return the first order ID for backward compatibility (or all order IDs)
        $primaryOrderId = !empty($createdOrderIds) ? $createdOrderIds[0] : null;
        error_log("Multi-shop orders completed successfully. Order IDs: " . implode(', ', $createdOrderIds));
        return $primaryOrderId;
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                error_log("Database cart cleared for user: " . $_SESSION['user_id']);
            } catch (Exception $e) {
                error_log("Could not clear database cart: " . $e->getMessage());
            }
        }
        $_SESSION['cart'] = [];
        error_log("Session cart cleared");
        
        error_log("Order $orderId completed successfully");
        return $orderId;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn) {
            $conn->rollback();
        }
        
        error_log("Order creation failed: " . $e->getMessage());
        throw $e; // Re-throw to get better error handling
    }
}
?>