<?php
// Initialize session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'helpers.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    header('Location: cart.php');
    exit;
}

// Remove item from cart
if (isset($_SESSION['cart'][$productId])) {
    unset($_SESSION['cart'][$productId]);
    setFlashMessage('success', 'Item removed from cart.');
} else {
    setFlashMessage('error', 'Item not found in cart.');
}

// Redirect back to cart
header('Location: cart.php');
exit;
?>
