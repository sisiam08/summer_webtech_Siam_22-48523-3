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
            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
            <h3><?php echo $product['name']; ?></h3>
            <p class="price"><?php echo formatPrice($product['price']); ?></p>
            <button class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
        </div>
        <?php
    }
}
?>
