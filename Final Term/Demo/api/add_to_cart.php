<?php
session_start();
require_once '../config/database.php';
require_once '../includes/cart_helpers.php';
require_once '../includes/cart_utils.php'; // Adding this missing include

// Enable error logging
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', '../logs/cart_errors.log');

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ensure cart has correct structure
normalizeCartStructure();

// Get product id from POST or GET parameter
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

error_log("Adding product ID: $productId, Quantity: $quantity to cart");

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

try {
    // Connect to database
    $conn = connectDB();

    // Check if product exists and is active
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
        // For logged-in users (using database cart)
        // Make sure cart table exists
        ensureCartTableExists($conn);
        
        // Add to database cart for logged in user
        try {
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
        } catch (Exception $e) {
            // If there's an error with the cart table, fall back to session cart
            error_log("Error with database cart, falling back to session: " . $e->getMessage());
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$productId])) {
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
                $_SESSION['cart'][$productId] = [
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Get cart count
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                if (is_array($item) && isset($item['quantity'])) {
                    $cartCount += $item['quantity'];
                } else {
                    $cartCount += $item;
                }
            }
        }
    } else {
        // For guest users (using session cart)
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Always ensure cart has proper structure
        normalizeCartStructure();
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'quantity' => $quantity,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Get cart count using utility function
        $cartCount = calculateCartCount();
        
        error_log("Updated cart for guest user. Cart now contains: " . print_r($_SESSION['cart'], true));
    }
    
    // If it's an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $response = [
            'success' => true, 
            'message' => 'Product added to cart', 
            'product_name' => $row['name'],
            'cart_count' => $cartCount,
            'cart_items' => $_SESSION['cart']
        ];
        echo json_encode($response);
        error_log("AJAX Response: " . json_encode($response));
    } else {
        // For regular link clicks, redirect to the referring page or cart
        $_SESSION['flash_message'] = $row['name'] . ' has been added to your cart.';
        
        // Redirect back to the referring page or to the cart page
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../cart.php';
        header("Location: $redirect");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
    } else {
        $_SESSION['flash_error'] = 'Error adding product to cart. Please try again.';
        header('Location: ../products.php');
        exit;
    }
}
?>

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Connect to database
$conn = connectDB();

try {
    // Check if product exists and is active (if is_active column exists)
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
        // Make sure cart table exists
        ensureCartTableExists($conn);
        
        // Add to database cart for logged in user
        try {
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
        } catch (Exception $e) {
            // If there's an error with the cart table, fall back to session cart
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
            foreach ($_SESSION['cart'] as $productId => $item) {
                if (is_array($item) && isset($item['quantity'])) {
                    $cartCount += $item['quantity'];
                } else {
                    $cartCount += $item;
                }
            }
        }
        
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
        foreach ($_SESSION['cart'] as $productId => $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $cartCount += $item['quantity'];
            } else {
                $cartCount += $item;
            }
        }
        
        error_log("Updated cart for guest user. Cart now contains: " . print_r($_SESSION['cart'], true));
    }
    
    // If it's an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $response = [
            'success' => true, 
            'message' => 'Product added to cart', 
            'product_name' => $row['name'],
            'cart_count' => $cartCount,
            'cart_items' => $_SESSION['cart']
        ];
        echo json_encode($response);
        error_log("AJAX Response: " . json_encode($response));
    } else {
        // For regular link clicks, redirect to the referring page or cart
        $_SESSION['flash_message'] = $row['name'] . ' has been added to your cart.';
        
        // Redirect back to the referring page or to the cart page
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
        header("Location: $redirect");
        exit;
    }
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../cart.php';
        header('Location: ' . $redirect);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
    } else {
        $_SESSION['flash_error'] = 'Error adding product to cart. Please try again.';
        header('Location: ../products.php');
        exit;
    }
} finally {
    $conn = null;
}
?>
