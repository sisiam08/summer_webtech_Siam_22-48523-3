<?php
// Cart Format Migration Script
// This script will convert old cart format to new format and fix any issues

session_start();
require_once 'config/database.php';
require_once 'includes/shop_functions.php';
require_once 'helpers.php';

// Define old and new cart formats for reference
$oldFormat = [
    '1' => 2,  // Product ID => Quantity
    '5' => 1
];

$newFormat = [
    '1' => [
        'quantity' => 2,
        'added_at' => '2023-08-15 10:30:00'
    ],
    '5' => [
        'quantity' => 1,
        'added_at' => '2023-08-15 11:45:00'
    ]
];

// Process the cart migration
$migrated = false;
$errors = [];
$messages = [];

// First, check if cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    $messages[] = "Created new empty cart";
} else {
    $messages[] = "Found existing cart with " . count($_SESSION['cart']) . " items";
}

// Check and migrate cart format if needed
foreach ($_SESSION['cart'] as $productId => $cartItem) {
    if (!is_array($cartItem)) {
        // Old format detected
        $messages[] = "Converting product ID $productId from old format to new format";
        $quantity = $cartItem;
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
        $migrated = true;
    } else if (!isset($cartItem['quantity']) || !isset($cartItem['added_at'])) {
        // Partial format, fix it
        $messages[] = "Fixing incomplete format for product ID $productId";
        $quantity = $cartItem['quantity'] ?? 1;
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
        $migrated = true;
    }
    
    // Verify product exists in database
    $product = getProductById($productId);
    if (!$product) {
        $errors[] = "Product ID $productId not found in database, removing from cart";
        unset($_SESSION['cart'][$productId]);
    }
}

// Test cart retrieval
try {
    $shopCartItems = getCartItemsByShop();
    $cartCount = calculateCartCount();
    $messages[] = "getCartItemsByShop processed " . count($shopCartItems) . " shops with items";
    $messages[] = "Current cart count: $cartCount";
} catch (Exception $e) {
    $errors[] = "Error processing cart: " . $e->getMessage();
}

// Output results as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Migration Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .code-block { background-color: #f8f9fa; border: 1px solid #eaecf0; padding: 10px; border-radius: 4px; overflow-x: auto; margin: 10px 0; }
        pre { margin: 0; }
        .nav { margin: 20px 0; }
        .nav a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cart Format Migration Tool</h1>
        
        <?php if ($migrated): ?>
        <div class="success">
            <p><strong>Cart format successfully migrated!</strong></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
        <h2>Information</h2>
        <?php foreach ($messages as $message): ?>
        <div class="info"><?php echo $message; ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <h2>Errors</h2>
        <?php foreach ($errors as $error): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        
        <h2>Current Cart Data</h2>
        <div class="code-block">
            <pre><?php print_r($_SESSION['cart']); ?></pre>
        </div>
        
        <?php if (!empty($shopCartItems)): ?>
        <h2>Processed Cart Items</h2>
        <div class="code-block">
            <pre><?php print_r($shopCartItems); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="nav">
            <a href="cart_migrate.php" class="btn">Refresh</a>
            <a href="cart.php" class="btn">Go to Cart</a>
            <a href="cart_test.php" class="btn">Cart Test Tool</a>
            <a href="products.php" class="btn">Products</a>
        </div>
    </div>
</body>
</html>
