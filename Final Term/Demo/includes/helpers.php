<?php
// Include database connection and session functions
require_once __DIR__ . '/../config/database.php';
require_once 'session.php';

// Helper functions for the application

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

// Function to format price - REMOVED to avoid duplication with global_functions.php
// This function is now available in global_functions.php using the config system
/*
function formatPrice($price) {
    // Use the global_functions.php implementation if available
    if (function_exists('config')) {
        $symbol = config('site.currency_symbol', '৳');
        return $symbol . ' ' . number_format($price, 2);
    }
    
    // Fallback for backward compatibility
    return '৳ ' . number_format($price, 2);
}
*/

// The following functions have been moved to functions.php:
// - getProductById
// - getProductsByCategory
// - getFeaturedProducts
// - getAllCategories
// - getCategoryById
// - addToCart
// - updateCartItem
// - removeFromCart
// - getCartItems
// - getCartTotal
// - clearCart
// - getAllProducts
// - emailExists
// - registerUser
// - loginUser
// - logoutUser
?>
