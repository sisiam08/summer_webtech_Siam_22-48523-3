<?php
require_once 'db_connection.php';

// Get keyword from GET parameter
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if (empty($keyword)) {
    echo '<p>Please enter a search term.</p>';
    exit;
}

// Search products from the database
$conn = connectDB();

try {
    $searchTerm = "%$keyword%";
    $query = "SELECT * FROM products WHERE (name LIKE :keyword OR description LIKE :keyword) AND is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    // Check if we have products
    if ($stmt->rowCount() == 0) {
        echo '<p>No products found matching "' . htmlspecialchars($keyword) . '".</p>';
        exit;
    }
    
    // Output search results
    echo '<h3>Search Results for "' . htmlspecialchars($keyword) . '"</h3>';
    
    // Output products
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        outputProductCard($row);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<p>Error searching products. Please try again later.</p>';
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
