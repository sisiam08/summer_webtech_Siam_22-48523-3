<?php
session_start();
require_once 'db_connection.php';

// Get product id from GET parameter
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($productId <= 0) {
    echo "Invalid product ID";
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Connect to database
$conn = connectDB();

try {
    if ($isLoggedIn) {
        // Remove product from cart in database for logged in user
        $query = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Remove product from cart in session for guest user
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }
    
    echo "Product removed from cart";
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Error removing product from cart";
} finally {
    $conn = null;
}
?>
