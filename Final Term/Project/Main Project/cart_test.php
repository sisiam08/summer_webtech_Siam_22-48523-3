<?php
// Direct cart testing script - bypasses AJAX
session_start();

// Include error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/shop_functions.php';
require_once 'includes/cart_utils.php';

// Function to add a test message
function addTestMessage($message, $type = 'info') {
    global $testMessages;
    $testMessages[] = [
        'message' => $message,
        'type' => $type
    ];
}

// Initialize test messages array
$testMessages = [];

// Ensure cart has correct structure
normalizeCartStructure();
addTestMessage("Cart structure normalized", "info");

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    addTestMessage("Cart initialized in session", "info");
}

// Get a list of valid product IDs
$productIds = [];
$query = "SELECT id, name FROM products LIMIT 10";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productIds[$row['id']] = $row['name'];
    }
    addTestMessage("Found " . count($productIds) . " products in database", "info");
} else {
    addTestMessage("No products found in database", "error");
}

// Add a test product directly to the session
if (isset($_GET['add']) && !empty($productIds)) {
    $testProductId = isset($_GET['id']) ? intval($_GET['id']) : array_key_first($productIds);
    
    // Use the new cart structure with quantity and added_at
    if (isset($_SESSION['cart'][$testProductId])) {
        $_SESSION['cart'][$testProductId]['quantity']++;
        addTestMessage("Increased quantity for product ID: $testProductId (" . $productIds[$testProductId] . ")", "success");
    } else {
        $_SESSION['cart'][$testProductId] = [
            'quantity' => 1,
            'added_at' => date('Y-m-d H:i:s')
        ];
        addTestMessage("Added product ID: $testProductId (" . $productIds[$testProductId] . ") to cart", "success");
    }
}

// Add multiple products for testing
if (isset($_GET['add_multiple'])) {
    $count = 0;
    foreach ($productIds as $id => $name) {
        if ($count >= 3) break; // Add max 3 products
        
        // Use the new cart structure
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = [
                'quantity' => 1,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
        $count++;
    }
    addTestMessage("Added $count products to cart", "success");
}

// Clear the cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    addTestMessage("Cart cleared", "warning");
}

// Test the getCartItemsByShop function
$shopCartItems = [];
try {
    if (!empty($_SESSION['cart'])) {
        addTestMessage("Testing getCartItemsByShop function", "info");
        $shopCartItems = getCartItemsByShop();
        if (empty($shopCartItems)) {
            addTestMessage("getCartItemsByShop returned EMPTY result", "error");
        } else {
            addTestMessage("getCartItemsByShop returned " . count($shopCartItems) . " shops with items", "success");
        }
    }
} catch (Exception $e) {
    addTestMessage("Error in getCartItemsByShop: " . $e->getMessage(), "error");
}

// Test product retrieval for the first item in cart
if (!empty($_SESSION['cart'])) {
    $firstProductId = array_key_first($_SESSION['cart']);
    addTestMessage("Testing product retrieval for ID: $firstProductId", "info");
    
    try {
        $product = getProductById($firstProductId);
        if ($product) {
            addTestMessage("Successfully retrieved product: " . $product['name'], "success");
        } else {
            addTestMessage("Failed to retrieve product with ID: $firstProductId", "error");
        }
    } catch (Exception $e) {
        addTestMessage("Error in getProductById: " . $e->getMessage(), "error");
    }
}

// Check session structure
$correctStructure = true;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId => $data) {
        if (!isset($data['quantity']) || !isset($data['added_at'])) {
            $correctStructure = false;
            addTestMessage("Incorrect cart structure for product ID: $productId", "error");
        }
    }
    
    if ($correctStructure) {
        addTestMessage("Cart structure is correct", "success");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .actions { margin: 20px 0; }
        .actions a { display: inline-block; margin-right: 10px; margin-bottom: 10px; }
        .message { padding: 10px; margin: 5px 0; border-radius: 3px; }
        .info { background-color: #e3f2fd; }
        .warning { background-color: #fff3e0; }
        .error { background-color: #ffebee; }
        .success { background-color: #e8f5e9; }
        .debug-box { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow: auto; max-height: 300px; }
        pre { margin: 0; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .product-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cart Test</h1>
        
        <div class="card">
            <h2>Actions</h2>
            <div class="actions">
                <a href="cart_test.php" class="btn">Refresh</a>
                <a href="cart_test.php?add=1" class="btn">Add Test Product</a>
                <a href="cart_test.php?add_multiple=1" class="btn">Add Multiple Products</a>
                <a href="cart_test.php?clear=1" class="btn btn-danger">Clear Cart</a>
                <a href="cart.php" class="btn">View Cart Page</a>
                <a href="products.php" class="btn">Go to Products</a>
            </div>
            
            <?php if (!empty($productIds)): ?>
            <h3>Add Specific Product</h3>
            <div class="product-grid">
                <?php foreach ($productIds as $id => $name): ?>
                <div class="product-item">
                    <p><strong><?php echo $name; ?></strong></p>
                    <p>ID: <?php echo $id; ?></p>
                    <a href="cart_test.php?add=1&id=<?php echo $id; ?>" class="btn">Add to Cart</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Test Results</h2>
            <?php foreach ($testMessages as $msg): ?>
                <div class="message <?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h2>Session Cart Data</h2>
            <div class="debug-box">
                <pre><?php print_r($_SESSION['cart']); ?></pre>
            </div>
            
            <h3>Session Info</h3>
            <p>Session ID: <?php echo session_id(); ?></p>
            <p>Session Name: <?php echo session_name(); ?></p>
            <p>Cart Item Count: <?php echo array_sum(array_map(function($item) { 
                return $item['quantity'] ?? 1; 
            }, $_SESSION['cart'])); ?></p>
        </div>
        
        <?php if (!empty($shopCartItems)): ?>
        <div class="card">
            <h2>getCartItemsByShop Result</h2>
            <div class="debug-box">
                <pre><?php print_r($shopCartItems); ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
