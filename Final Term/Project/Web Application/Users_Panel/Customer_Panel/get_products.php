<?php
require_once __DIR__ . '/../../Database/database.php';

// Get products from the database
$conn = connectDB();

try {
    $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    
    if ($categoryId > 0) {
        // Get products for a specific category
        $query = "SELECT * FROM products WHERE category_id = :category_id AND is_active = 1 ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    } else {
        // Get all products
        $query = "SELECT * FROM products WHERE is_active = 1 ORDER BY name";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    
    // Check if we have products
    if ($stmt->rowCount() == 0) {
        echo '<p>No products found in this category.</p>';
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
    // Better image handling for empty strings and null values
    if (!empty($product['image']) && $product['image'] !== '') {
        $imageSrc = '../../Uploads/products/' . htmlspecialchars($product['image']);
    } else {
        // Use a data URL for placeholder image
        $imageSrc = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDMwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMjAgNjBIMTgwVjkwSDEyMFY2MFoiIGZpbGw9IiNEREREREQiLz4KPGNPCMCSQ2xlIGN4PSIxNDAiIGN5PSI3NSIgcj0iNSIgZmlsbD0iI0RERERERCIvPgo8dGV4dCB4PSIxNTAiIHk9IjExMCIgZmlsbD0iIzk5OSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5ObyBJbWFnZTwvdGV4dD4KPC9zdmc+';
    }
    
    $price = number_format($product['price'], 2);
    
    echo '<div class="product-card">';
    echo '  <div class="product-image">';
    echo '    <img src="' . $imageSrc . '" alt="' . htmlspecialchars($product['name']) . '">';
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
