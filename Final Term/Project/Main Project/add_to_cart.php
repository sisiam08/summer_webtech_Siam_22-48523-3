<?php
// Initialize session
session_start();

// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Invalid product ID');
    redirect('products.php');
}

$productId = (int)$_GET['id'];
$product = getProductById($productId);

// Check if product exists
if (!$product) {
    setFlashMessage('error', 'Product not found');
    redirect('products.php');
}

// Add product to cart
addToCart($productId);

// Set success message
setFlashMessage('success', $product['name'] . ' added to cart');

// Redirect back to previous page or products page
$referer = $_SERVER['HTTP_REFERER'] ?? 'products.php';
redirect($referer);
?>
