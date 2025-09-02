<?php
// Include required files
require_once 'database_connection.php';
require_once 'helpers.php';

// Get featured products
$featuredProducts = getFeaturedProducts(4);

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
            <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</a>
        </div>
        <?php
    }
}
?>
