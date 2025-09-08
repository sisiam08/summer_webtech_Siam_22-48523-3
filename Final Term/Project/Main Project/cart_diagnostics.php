<?php
// Cart diagnostics tool
session_start();

// Include required files
require_once 'config/database.php';
require_once 'helpers.php';
require_once 'includes/shop_functions.php';

// Function to test adding a product to cart
function testAddToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    return $_SESSION['cart'];
}

// Clean all session data
function cleanSession() {
    $_SESSION = [];
    return true;
}

// Add product directly from URL parameters
if (isset($_GET['add_product'])) {
    $productId = (int)$_GET['add_product'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    testAddToCart($productId, $quantity);
    echo "<div style='background-color: #dff0d8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "Added Product ID: $productId, Quantity: $quantity to cart</div>";
}

// Clean session if requested
if (isset($_GET['clean'])) {
    cleanSession();
    echo "<div style='background-color: #d9edf7; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "Session cleaned successfully</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .section { margin-bottom: 30px; padding: 15px; background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 8px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn.red { background: #f44336; }
        .btn.blue { background: #2196F3; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Cart Diagnostics Tool</h1>
    
    <div class="section">
        <h2>Current Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="section">
        <h2>Cart Contents</h2>
        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Quantity</th>
                        <th>Product Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $productId => $item): ?>
                        <?php 
                            // Handle both old and new cart formats
                            if (is_array($item) && isset($item['quantity'])) {
                                $quantity = $item['quantity'];
                                $added_at = $item['added_at'] ?? 'N/A';
                            } else {
                                $quantity = $item; // Old format where cart item is just a quantity
                                $added_at = 'N/A';
                            }
                        ?>
                        <tr>
                            <td><?php echo $productId; ?></td>
                            <td><?php echo $quantity; ?> (Added: <?php echo $added_at; ?>)</td>
                            <td>
                                <?php 
                                    $product = getProductById($productId);
                                    if ($product) {
                                        echo $product['name'] . ' - ' . formatPrice($product['price']);
                                    } else {
                                        echo "Product not found";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div>
                <h3>Cart Functions Test</h3>
                <pre>getCartItemsByShop(): <?php print_r(getCartItemsByShop()); ?></pre>
                <pre>getCartTotal(): <?php echo getCartTotal(); ?></pre>
            </div>
        <?php else: ?>
            <p>Cart is empty</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Quick Actions</h2>
        <a href="cart_diagnostics.php?clean=1" class="btn red">Clean Session</a>
        <a href="cart.php" class="btn blue">View Cart Page</a>
        <a href="products.php" class="btn">View Products</a>
        
        <h3>Add Product Directly to Cart</h3>
        <form action="cart_diagnostics.php" method="get">
            <div style="margin-bottom: 10px;">
                <label for="product_id">Product ID:</label>
                <input type="number" name="add_product" id="product_id" min="1" required>
            </div>
            <div style="margin-bottom: 10px;">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1">
            </div>
            <button type="submit" class="btn">Add to Cart</button>
        </form>
    </div>
</body>
</html>
