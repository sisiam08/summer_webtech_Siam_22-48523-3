<?php
session_start();

require_once __DIR__ . '/../../Database/database.php';

// Get product id from POST or GET parameter
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Connect to database
$conn = connectDB();

// Ensure cart table exists
try {
    $conn->query("SELECT 1 FROM cart LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `cart` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `product_id` INT(11) NOT NULL,
        `quantity` INT(11) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTableSQL);
}

try {
    // Check if product exists
    $query = "SELECT id, name, price FROM products WHERE id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Product exists, now add to cart
    if ($isLoggedIn) {
        // Add to database cart for logged in user
        
        // Check if product already exists in cart
        $query = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($cartItem = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Product exists in cart, update quantity
            $newQuantity = $cartItem['quantity'] + $quantity;
            
            $query = "UPDATE cart SET quantity = :quantity WHERE id = :cart_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
            $stmt->bindParam(':cart_id', $cartItem['id'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Product doesn't exist in cart, insert new record
            $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Get cart count
        $query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $result['cart_count'] ?? 0;
        
    } else {
        // Add to session cart for guest user
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        // Get cart count
        $cartCount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cartCount += $item;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Product added to cart', 
        'product_name' => $row['name'],
        'cart_count' => $cartCount
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(
        [
            'success' => false, 
            'message' => 'Error adding product to cart'
            // 'message' => $e
        ]
    );
} finally {
    $conn = null;
}
?>
