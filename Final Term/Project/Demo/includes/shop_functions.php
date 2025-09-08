<?php
// Functions to handle multi-shop functionality

// Include cart utilities
require_once __DIR__ . '/cart_utils.php';

// Function to check if multi-shop tables exist
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

// Function to get shop information by ID
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

// Function to get all shops
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

// Function to get shop orders by main order ID
function getShopOrdersByOrderId($orderId) {
    global $conn;
    
    $shopOrders = [];
    
    // Get shop orders
    $sql = "SELECT so.*, s.name as shop_name 
            FROM shop_orders so 
            JOIN shops s ON so.shop_id = s.id 
            WHERE so.order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($shopOrder = $result->fetch_assoc()) {
        // Get items for this shop order
        $sql = "SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.shop_order_id = ?";
        
        $itemStmt = $conn->prepare($sql);
        $itemStmt->bind_param("i", $shopOrder['id']);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        
        $items = [];
        while ($item = $itemResult->fetch_assoc()) {
            $item['total'] = $item['price'] * $item['quantity'];
            $items[] = $item;
        }
        
        $shopOrder['items'] = $items;
        $shopOrders[] = $shopOrder;
    }
    
    return $shopOrders;
}

// Function to get products by shop
function getProductsByShop($shopId) {
    global $conn;
    
    $sql = "SELECT * FROM products WHERE shop_id = ? ORDER BY name";
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

// Function to get products by category and show shop information
function getProductsByCategoryWithShopInfo($categoryId) {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        // If multi-shop not enabled, use traditional function and add shop info
        $products = getProductsByCategory($categoryId);
        foreach ($products as &$product) {
            $product['shop_name'] = 'Default Shop';
            $product['delivery_charge'] = 5.00;
        }
        return $products;
    }
    
    $sql = "SELECT p.*, s.name as shop_name, s.delivery_charge 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
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

// Function to get all products with shop information
function getAllProductsWithShopInfo() {
    global $conn;
    
    if (!isMultiShopEnabled()) {
        // If multi-shop not enabled, use traditional function and add shop info
        $products = getAllProducts();
        foreach ($products as &$product) {
            $product['shop_name'] = 'Default Shop';
            $product['delivery_charge'] = 5.00;
        }
        return $products;
    }
    
    $sql = "SELECT p.*, s.name as shop_name, s.delivery_charge 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            ORDER BY p.name";
    $result = $conn->query($sql);
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to get cart items grouped by shop
function getCartItemsByShop() {
    // Debug information
    error_log("Cart session: " . print_r($_SESSION['cart'] ?? [], true));
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        error_log("Cart is empty or not set in session");
        return [];
    }
    
    // Ensure cart structure is normalized
    normalizeCartStructure();
    
    $shopCartItems = [];
    
    // Default shop if multi-shop is not enabled
    $defaultShopId = 1;
    $defaultShopName = 'Default Shop';
    $defaultDeliveryCharge = 5.00;
    
    foreach ($_SESSION['cart'] as $productId => $cartItem) {
        // Handle both old and new cart formats
        if (is_array($cartItem) && isset($cartItem['quantity'])) {
            $quantity = $cartItem['quantity'];
        } else {
            $quantity = $cartItem; // Old format where cart item is just a quantity
        }
        
        error_log("Processing product ID: $productId with quantity: " . json_encode($quantity));
        
        try {
            $product = getProductById($productId);
            error_log("Product data: " . ($product ? json_encode($product) : "Product not found"));
            
            if ($product) {
            if (isMultiShopEnabled() && isset($product['shop_id'])) {
                $shopId = $product['shop_id'];
                $shop = getShopById($shopId);
                $shopName = $shop ? $shop['name'] : 'Unknown Shop';
                $deliveryCharge = $shop ? $shop['delivery_charge'] : 5.00;
            } else {
                $shopId = $defaultShopId;
                $shopName = $defaultShopName;
                $deliveryCharge = $defaultDeliveryCharge;
            }
            
            if (!isset($shopCartItems[$shopId])) {
                $shopCartItems[$shopId] = [
                    'shop_id' => $shopId,
                    'shop_name' => $shopName,
                    'delivery_charge' => $deliveryCharge,
                    'items' => [],
                    'subtotal' => 0
                ];
            }
            
            $product['quantity'] = $quantity;
            $product['total'] = $product['price'] * $quantity;
            $shopCartItems[$shopId]['items'][] = $product;
            $shopCartItems[$shopId]['subtotal'] += $product['total'];
        } else {
            error_log("Error: Product ID $productId not found in database but exists in cart");
        }
    } catch (Exception $e) {
        error_log("Error processing product ID $productId: " . $e->getMessage());
    }
}
    
error_log("Shop cart items: " . json_encode($shopCartItems));
return $shopCartItems;
}

// Function to get cart count
function getCartCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    
    $cartCount = 0;
    foreach ($_SESSION['cart'] as $productId => $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $cartCount += $item['quantity'];
        } else {
            $cartCount += $item;
        }
    }
    
    return $cartCount;
}

// Function to get total delivery charge for all shops in cart
function getTotalDeliveryCharge() {
    $shopCartItems = getCartItemsByShop();
    $totalDeliveryCharge = 0;
    
    foreach ($shopCartItems as $shopCart) {
        $totalDeliveryCharge += $shopCart['delivery_charge'];
    }
    
    return $totalDeliveryCharge;
}

// Function to get cart total including delivery charges
function getCartTotalWithDelivery() {
    $cartTotal = getCartTotal();
    $deliveryCharge = getTotalDeliveryCharge();
    
    return $cartTotal + $deliveryCharge;
}

// Function to create multi-shop order
function createMultiShopOrder($userId, $shippingAddress, $paymentMethod) {
    global $conn;
    
    // If multi-shop is not enabled, use the traditional order creation
    if (!isMultiShopEnabled()) {
        $cartItems = getCartItems();
        $cartTotal = getCartTotal();
        
        // Create main order
        $orderId = createOrder($userId, $cartTotal, $shippingAddress, $paymentMethod);
        
        if ($orderId) {
            // Add order items
            foreach ($cartItems as $item) {
                addOrderItem($orderId, $item['id'], $item['quantity'], $item['price']);
            }
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            return $orderId;
        }
        
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $shopCartItems = getCartItemsByShop();
        $cartTotal = getCartTotal();
        $totalDeliveryCharge = getTotalDeliveryCharge();
        $grandTotal = $cartTotal + $totalDeliveryCharge;
        
        // Create main order
        $sql = "INSERT INTO orders (user_id, total_amount, total_delivery_charge, shipping_address, payment_method) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddss", $userId, $cartTotal, $totalDeliveryCharge, $shippingAddress, $paymentMethod);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating main order: " . $stmt->error);
        }
        
        $orderId = $conn->insert_id;
        
        // Create shop orders and order items
        foreach ($shopCartItems as $shopCart) {
            // Create shop order
            $shopId = $shopCart['shop_id'];
            $subtotal = $shopCart['subtotal'];
            $deliveryCharge = $shopCart['delivery_charge'];
            
            $sql = "INSERT INTO shop_orders (order_id, shop_id, subtotal, delivery_charge) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iidd", $orderId, $shopId, $subtotal, $deliveryCharge);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating shop order: " . $stmt->error);
            }
            
            $shopOrderId = $conn->insert_id;
            
            // Add order items for this shop
            foreach ($shopCart['items'] as $item) {
                $productId = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $sql = "INSERT INTO order_items (order_id, shop_order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiid", $orderId, $shopOrderId, $productId, $quantity, $price);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error adding order item: " . $stmt->error);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        return $orderId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        error_log($e->getMessage());
        return false;
    }
}
