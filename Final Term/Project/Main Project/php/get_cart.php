<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// If not logged in, we'll use session cart
if (!$isLoggedIn && !isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items
$cartItems = [];
$totalPrice = 0;

// Connect to database
$conn = connectDB();

try {
    if ($isLoggedIn) {
        // Get cart items from database for logged in user
        $query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image, (p.price * c.quantity) as item_total 
                 FROM cart c 
                 JOIN products p ON c.product_id = p.id 
                 WHERE c.user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cartItems[] = $row;
            $totalPrice += $row['item_total'];
        }
    } else {
        // Get cart items from session
        if (!empty($_SESSION['cart'])) {
            $productIds = array_keys($_SESSION['cart']);
            
            // Convert array to comma-separated string for SQL IN clause
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            $query = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->execute($productIds);
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[$row['id']] = $row;
            }
            
            // Create cart items array
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                if (isset($products[$productId])) {
                    $product = $products[$productId];
                    $itemTotal = $product['price'] * $quantity;
                    
                    $cartItems[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'item_total' => $itemTotal
                    ];
                    
                    $totalPrice += $itemTotal;
                }
            }
        }
    }
    
    // Display cart content
    if (empty($cartItems)) {
        // Empty cart
        echo '<div class="empty-cart">';
        echo '  <h3>Your cart is empty</h3>';
        echo '  <p>Looks like you haven\'t added any products to your cart yet.</p>';
        echo '  <div class="continue-shopping">';
        echo '    <a href="products.html">Continue Shopping</a>';
        echo '  </div>';
        echo '</div>';
    } else {
        // Cart with items
        echo '<div class="cart-container">';
        echo '  <table class="cart-table">';
        echo '    <thead>';
        echo '      <tr>';
        echo '        <th>Product</th>';
        echo '        <th>Price</th>';
        echo '        <th>Quantity</th>';
        echo '        <th>Total</th>';
        echo '        <th>Action</th>';
        echo '      </tr>';
        echo '    </thead>';
        echo '    <tbody>';
        
        foreach ($cartItems as $item) {
            $image = !empty($item['image']) ? $item['image'] : 'default-product.jpg';
            $unitPrice = number_format($item['price'], 2);
            $itemTotal = number_format($item['item_total'], 2);
            
            echo '      <tr>';
            echo '        <td>';
            echo '          <div style="display: flex; align-items: center;">';
            echo '            <img src="images/products/' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($item['name']) . '" class="cart-image">';
            echo '            <span class="cart-product-name" style="margin-left: 10px;">' . htmlspecialchars($item['name']) . '</span>';
            echo '          </div>';
            echo '        </td>';
            echo '        <td>$' . $unitPrice . '</td>';
            echo '        <td>';
            echo '          <div class="quantity-control">';
            echo '            <button class="quantity-btn decrease-quantity" data-product-id="' . $item['product_id'] . '">-</button>';
            echo '            <input type="text" class="quantity-input" value="' . $item['quantity'] . '" readonly>';
            echo '            <button class="quantity-btn increase-quantity" data-product-id="' . $item['product_id'] . '">+</button>';
            echo '          </div>';
            echo '        </td>';
            echo '        <td>$' . $itemTotal . '</td>';
            echo '        <td><button class="remove-btn" data-product-id="' . $item['product_id'] . '">Remove</button></td>';
            echo '      </tr>';
        }
        
        echo '    </tbody>';
        echo '  </table>';
        
        // Cart summary
        echo '  <div class="cart-summary">';
        echo '    <div class="summary-row">';
        echo '      <span>Subtotal:</span>';
        echo '      <span>$' . number_format($totalPrice, 2) . '</span>';
        echo '    </div>';
        echo '    <div class="summary-row">';
        echo '      <span>Shipping:</span>';
        echo '      <span>$' . number_format(0, 2) . '</span>';
        echo '    </div>';
        echo '    <div class="summary-row cart-total">';
        echo '      <span>Total:</span>';
        echo '      <span>$' . number_format($totalPrice, 2) . '</span>';
        echo '    </div>';
        echo '    <button id="checkout-btn" class="checkout-btn">Proceed to Checkout</button>';
        echo '  </div>';
        echo '</div>';
        
        echo '<div class="continue-shopping">';
        echo '  <a href="products.html">Continue Shopping</a>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<p>Error loading cart. Please try again later.</p>';
} finally {
    $conn = null;
}
?>
