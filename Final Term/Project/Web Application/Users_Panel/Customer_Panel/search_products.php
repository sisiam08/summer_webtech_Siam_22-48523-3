<?php
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Get keyword from GET parameter
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if (empty($keyword)) {
    echo '<p>Please enter a search term.</p>';
    exit;
}

// Search products from the database
$conn = connectDB();

try {
    $searchTerm = "%$keyword%";
    
    // Updated query to include shop information like the main products page
    $query = "SELECT p.*, s.name as shop_name 
              FROM products p 
              LEFT JOIN shops s ON p.shop_id = s.id 
              WHERE (p.name LIKE :keyword OR p.description LIKE :keyword) 
              ORDER BY p.name";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if we have products
    if (empty($products)) {
        echo '<div class="product-content">';
        echo '<p>No products found matching "' . htmlspecialchars($keyword) . '".</p>';
        echo '</div>';
        exit;
    }
    
    // Output search results in same format as main products page
    echo '<div class="product-content">';
    echo '<h3>Search Results for "' . htmlspecialchars($keyword) . '" (' . count($products) . ' found)</h3>';
    echo '<div class="product-grid">';
    
    foreach ($products as $product) {
        outputProductCard($product);
    }
    
    echo '</div>';
    echo '</div>';
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<div class="product-content">';
    echo '<p>Error searching products. Please try again later.</p>';
    echo '</div>';
} finally {
    $conn = null;
}

// Function to output a product card matching the main products page format
function outputProductCard($product) {
    $image = !empty($product['image']) ? $product['image'] : 'no-image.jpg';
    $price = isset($product['price']) ? formatPrice($product['price']) : '$0.00';
    $shopName = $product['shop_name'] ?? 'Unknown Shop';
    
    echo '<div class="product-card">';
    echo '    <img src="../../Uploads/products/' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($product['name']) . '">';
    echo '    <div class="shop-badge">' . htmlspecialchars($shopName) . '</div>';
    echo '    <h3>' . htmlspecialchars($product['name']) . '</h3>';
    echo '    <p class="price">' . $price . '</p>';
    echo '    <button type="button" class="btn add-to-cart" data-id="' . $product['id'] . '">Add to Cart</button>';
    echo '</div>';
}
?>
?>
