<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    // Return empty response or error
    echo '<div class="checkout-error">';
    echo '  <h3>Please log in to continue</h3>';
    echo '  <p>You need to be logged in to checkout.</p>';
    echo '  <p><a href="login.html?redirect=checkout.html">Click here to login</a></p>';
    echo '</div>';
    exit;
}

// Get cart items and user details
$cartItems = [];
$totalPrice = 0;
$user = null;

// Connect to database
$conn = connectDB();

try {
    // Get user details
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo '<div class="checkout-error">';
        echo '  <h3>User not found</h3>';
        echo '  <p>There was an error retrieving your account information.</p>';
        echo '  <p><a href="login.html">Click here to login again</a></p>';
        echo '</div>';
        exit;
    }
    
    // Get cart items
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
    
    if (empty($cartItems)) {
        echo '<div class="checkout-error">';
        echo '  <h3>Your cart is empty</h3>';
        echo '  <p>You need to add items to your cart before checkout.</p>';
        echo '  <p><a href="products.html">Click here to continue shopping</a></p>';
        echo '</div>';
        exit;
    }
    
    // Display checkout form and order summary
    echo '<div class="checkout-container">';
    
    // Checkout form
    echo '  <div class="checkout-form">';
    echo '    <h3 class="section-title">Shipping Information</h3>';
    echo '    <form id="checkout-form" method="post">';
    
    // Hidden payment method field
    echo '      <input type="hidden" id="payment_method" name="payment_method" value="cod">';
    
    // Personal information
    echo '      <div class="form-group">';
    echo '        <label for="full_name">Full Name *</label>';
    echo '        <input type="text" id="full_name" name="full_name" value="' . htmlspecialchars($user['name'] ?? '') . '" required>';
    echo '      </div>';
    
    echo '      <div class="form-row">';
    echo '        <div class="form-group">';
    echo '          <label for="email">Email *</label>';
    echo '          <input type="email" id="email" name="email" value="' . htmlspecialchars($user['email'] ?? '') . '" required>';
    echo '        </div>';
    echo '        <div class="form-group">';
    echo '          <label for="phone">Phone *</label>';
    echo '          <input type="tel" id="phone" name="phone" value="' . htmlspecialchars($user['phone'] ?? '') . '" required>';
    echo '        </div>';
    echo '      </div>';
    
    // Address information
    echo '      <div class="form-group">';
    echo '        <label for="address">Address *</label>';
    echo '        <input type="text" id="address" name="address" value="' . htmlspecialchars($user['address'] ?? '') . '" required>';
    echo '      </div>';
    
    echo '      <div class="form-row">';
    echo '        <div class="form-group">';
    echo '          <label for="city">City *</label>';
    echo '          <input type="text" id="city" name="city" value="' . htmlspecialchars($user['city'] ?? '') . '" required>';
    echo '        </div>';
    echo '        <div class="form-group">';
    echo '          <label for="postal_code">Postal Code *</label>';
    echo '          <input type="text" id="postal_code" name="postal_code" value="' . htmlspecialchars($user['postal_code'] ?? '') . '" required>';
    echo '        </div>';
    echo '      </div>';
    
    echo '      <div class="form-group">';
    echo '        <label for="country">Country *</label>';
    echo '        <select id="country" name="country" required>';
    echo '          <option value="">Select Country</option>';
    echo '          <option value="Bangladesh" ' . (($user['country'] ?? '') === 'Bangladesh' ? 'selected' : '') . '>Bangladesh</option>';
    echo '          <option value="India" ' . (($user['country'] ?? '') === 'India' ? 'selected' : '') . '>India</option>';
    echo '          <option value="Pakistan" ' . (($user['country'] ?? '') === 'Pakistan' ? 'selected' : '') . '>Pakistan</option>';
    echo '          <option value="USA" ' . (($user['country'] ?? '') === 'USA' ? 'selected' : '') . '>United States</option>';
    echo '          <option value="UK" ' . (($user['country'] ?? '') === 'UK' ? 'selected' : '') . '>United Kingdom</option>';
    echo '        </select>';
    echo '      </div>';
    
    echo '      <div class="form-group">';
    echo '        <label for="notes">Order Notes (Optional)</label>';
    echo '        <textarea id="notes" name="notes" rows="3"></textarea>';
    echo '      </div>';
    
    // Payment methods
    echo '      <h3 class="section-title">Payment Method</h3>';
    echo '      <div class="payment-methods">';
    
    echo '        <div class="payment-method selected" data-method="cod">';
    echo '          <img src="images/cod.png" alt="Cash on Delivery">';
    echo '          <div>Cash on Delivery</div>';
    echo '        </div>';
    
    echo '        <div class="payment-method" data-method="bkash">';
    echo '          <img src="images/bkash.png" alt="bKash">';
    echo '          <div>bKash</div>';
    echo '        </div>';
    
    echo '        <div class="payment-method" data-method="card">';
    echo '          <img src="images/credit-card.png" alt="Credit Card">';
    echo '          <div>Credit/Debit Card</div>';
    echo '        </div>';
    
    echo '      </div>';
    
    echo '      <button type="submit" class="place-order-btn">Place Order</button>';
    
    echo '    </form>';
    echo '  </div>';
    
    // Order summary
    echo '  <div class="order-summary">';
    echo '    <h3 class="section-title">Order Summary</h3>';
    
    echo '    <div class="order-items">';
    foreach ($cartItems as $item) {
        $image = !empty($item['image']) ? $item['image'] : 'default-product.jpg';
        $unitPrice = number_format($item['price'], 2);
        $itemTotal = number_format($item['item_total'], 2);
        
        echo '      <div class="order-item">';
        echo '        <img src="images/products/' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($item['name']) . '" class="order-item-image">';
        echo '        <div class="order-item-details">';
        echo '          <div class="order-item-name">' . htmlspecialchars($item['name']) . '</div>';
        echo '          <div class="order-item-price">$' . $unitPrice . ' x ' . $item['quantity'] . '</div>';
        echo '        </div>';
        echo '        <div class="order-item-total">$' . $itemTotal . '</div>';
        echo '      </div>';
    }
    echo '    </div>';
    
    // Order totals
    echo '    <table class="order-summary-table">';
    echo '      <tr>';
    echo '        <td>Subtotal:</td>';
    echo '        <td>$' . number_format($totalPrice, 2) . '</td>';
    echo '      </tr>';
    
    // Calculate shipping (free for now)
    $shipping = 0;
    
    echo '      <tr>';
    echo '        <td>Shipping:</td>';
    echo '        <td>$' . number_format($shipping, 2) . '</td>';
    echo '      </tr>';
    
    // Calculate total
    $orderTotal = $totalPrice + $shipping;
    
    echo '      <tr class="order-total">';
    echo '        <td>Total:</td>';
    echo '        <td>$' . number_format($orderTotal, 2) . '</td>';
    echo '      </tr>';
    echo '    </table>';
    
    echo '  </div>';
    
    echo '</div>';
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<div class="checkout-error">';
    echo '  <h3>Error loading checkout</h3>';
    echo '  <p>There was an error loading your checkout information. Please try again later.</p>';
    echo '</div>';
} finally {
    $conn = null;
}
?>
