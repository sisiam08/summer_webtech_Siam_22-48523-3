<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Invalid product ID');
    redirect('cart.php');
}

$productId = (int)$_GET['id'];

// Remove product from cart
removeFromCart($productId);

// Set success message
setFlashMessage('success', 'Item removed from cart');

// Redirect to cart page
redirect('cart.php');
?>
