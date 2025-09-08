<?php
session_start();
require_once 'db_connection.php';

// Get product id and action from GET parameters
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($productId <= 0 || empty($action)) {
    echo "Invalid request";
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Connect to database
$conn = connectDB();

try {
    if ($isLoggedIn) {
        // Update cart in database for logged in user
        if ($action === 'increase') {
            // Check if product exists in cart
            $query = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Product exists in cart, update quantity
                $newQuantity = $row['quantity'] + 1;
                
                $query = "UPDATE cart SET quantity = :quantity WHERE id = :cart_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                $stmt->bindParam(':cart_id', $row['id'], PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Product doesn't exist in cart, insert new record
                $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $stmt->execute();
            }
        } else if ($action === 'decrease') {
            // Get current quantity
            $query = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $newQuantity = $row['quantity'] - 1;
                
                if ($newQuantity > 0) {
                    // Update quantity
                    $query = "UPDATE cart SET quantity = :quantity WHERE id = :cart_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                    $stmt->bindParam(':cart_id', $row['id'], PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Remove item if quantity is 0 or less
                    $query = "DELETE FROM cart WHERE id = :cart_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':cart_id', $row['id'], PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    } else {
        // Update cart in session for guest user
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if ($action === 'increase') {
            // Add to cart or increase quantity
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]++;
            } else {
                $_SESSION['cart'][$productId] = 1;
            }
        } else if ($action === 'decrease') {
            // Decrease quantity or remove if quantity becomes 0
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]--;
                
                if ($_SESSION['cart'][$productId] <= 0) {
                    unset($_SESSION['cart'][$productId]);
                }
            }
        }
    }
    
    echo "Cart updated successfully";
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Error updating cart";
} finally {
    $conn = null;
}
?>
