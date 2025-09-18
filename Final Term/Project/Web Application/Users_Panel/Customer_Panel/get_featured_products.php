<?php
// Include functions file
require_once __DIR__ . "/../../Database/database.php";
require_once __DIR__ . "/../../Includes/functions.php";
header('Content-Type: text/html; charset=UTF-8');

// Get featured products
$featuredProducts = getFeaturedProducts(6);

// Display featured products
if (empty($featuredProducts)) {
    echo '<p>No featured products available.</p>';
} else {
    foreach ($featuredProducts as $product) {
        ?>
        <div class="product-card">
            <?php 
            $imageSrc = (!empty($product['image']) && $product['image'] !== '') 
                ? '../../Uploads/products/' . $product['image']
                : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDMwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMjAgNjBIMTgwVjkwSDEyMFY2MFoiIGZpbGw9IiNEREREREQiLz4KPGNPCMNSQ2xlIGN4PSIxNDAiIGN5PSI3NSIgcj0iNSIgZmlsbD0iI0RERERERCIvPgo8dGV4dCB4PSIxNTAiIHk9IjExMCIgZmlsbD0iIzk5OSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5ObyBJbWFnZTwvdGV4dD4KPC9zdmc+';
            ?>
            <img src="<?php echo $imageSrc; ?>" alt="<?php echo $product['name']; ?>">
            <h3><?php echo $product['name']; ?></h3>
            <p class="price"><?php echo formatPrice($product['price']); ?></p>
            <button class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
        </div>
        <?php
    }
}
?>
