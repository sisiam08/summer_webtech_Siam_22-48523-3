<?php
// Start session and include required files
session_start();
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in and is a shop owner
if (!isLoggedIn() || !isShopOwner()) {
    echo '<p>Access denied. Please login as a shop owner.</p>';
    exit;
}

// Get the logged-in shop owner's shop ID
$shopId = getShopIdForOwner();
if (!$shopId) {
    echo '<p>No shop found for this account. Please contact administrator.</p>';
    exit;
}

// Get products from the database for this specific shop
$conn = connectDB();

try {
    $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    
    if ($categoryId > 0) {
        // Get products for a specific category AND this shop
        $query = "SELECT * FROM products WHERE category_id = :category_id AND shop_id = :shop_id AND is_active = 1 ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':shop_id', $shopId, PDO::PARAM_INT);
    } else {
        // Get all products for this shop only
        $query = "SELECT * FROM products WHERE shop_id = :shop_id AND is_active = 1 ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':shop_id', $shopId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    // Check if we have products
    if ($stmt->rowCount() == 0) {
        if ($categoryId > 0) {
            echo '<p>No products found in this category for your shop.</p>';
        } else {
            echo '<p>No products found for your shop. <a href="add_product.php">Add your first product</a></p>';
        }
        exit;
    }
    
    // Output products
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        outputProductCard($row);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<p>Error loading products. Please try again later.</p>';
} finally {
    $conn = null;
}

// Function to output a product card
function outputProductCard($product) {
    $image = !empty($product['image']) ? $product['image'] : 'default-product.jpg';
    $price = number_format($product['price'], 2);
    
    echo '<div class="product-card">';
    echo '  <div class="product-image">';
    echo '    <img src="images/products/' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($product['name']) . '">';
    echo '  </div>';
    echo '  <div class="product-info">';
    echo '    <h3>' . htmlspecialchars($product['name']) . '</h3>';
    echo '    <p class="product-description">' . htmlspecialchars($product['description']) . '</p>';
    echo '    <p class="product-price">$' . $price . '</p>';
    echo '    <button class="add-to-cart-btn" data-product-id="' . $product['id'] . '">Add to Cart</button>';
    echo '  </div>';
    echo '</div>';
}
?>
