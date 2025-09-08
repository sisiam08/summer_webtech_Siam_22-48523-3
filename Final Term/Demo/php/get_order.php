<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    echo '<div class="confirmation-container">';
    echo '  <div class="confirmation-icon">❌</div>';
    echo '  <div class="confirmation-message">Access Denied</div>';
    echo '  <p>You need to be logged in to view order information.</p>';
    echo '  <div class="action-buttons">';
    echo '    <a href="login.html" class="action-button">Login</a>';
    echo '    <a href="index.html" class="action-button secondary-button">Return to Home</a>';
    echo '  </div>';
    echo '</div>';
    exit;
}

// Get order ID from GET parameter
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    echo '<div class="confirmation-container">';
    echo '  <div class="confirmation-icon">❌</div>';
    echo '  <div class="confirmation-message">Invalid Order ID</div>';
    echo '  <p>The order ID is invalid or missing.</p>';
    echo '  <div class="action-buttons">';
    echo '    <a href="index.html" class="action-button">Return to Home</a>';
    echo '  </div>';
    echo '</div>';
    exit;
}

// Connect to database
$conn = connectDB();

try {
    // Get order details
    $query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo '<div class="confirmation-container">';
        echo '  <div class="confirmation-icon">❌</div>';
        echo '  <div class="confirmation-message">Order Not Found</div>';
        echo '  <p>We couldn\'t find the order you\'re looking for.</p>';
        echo '  <div class="action-buttons">';
        echo '    <a href="index.html" class="action-button">Return to Home</a>';
        echo '  </div>';
        echo '</div>';
        exit;
    }
    
    // Get order items
    $query = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    
    $orderItems = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orderItems[] = $row;
    }
    
    // Display order confirmation
    echo '<div class="confirmation-container">';
    echo '  <div class="confirmation-icon">✅</div>';
    echo '  <div class="confirmation-message">Thank You for Your Order!</div>';
    echo '  <p>Your order has been received and is now being processed.</p>';
    
    // Order details
    echo '  <div class="order-details">';
    echo '    <h3>Order Details</h3>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Order Number:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['order_number']) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Date:</div>';
    echo '      <div class="detail-value">' . date('F j, Y', strtotime($order['created_at'])) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Payment Method:</div>';
    echo '      <div class="detail-value">' . ucfirst(htmlspecialchars($order['payment_method'])) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Status:</div>';
    echo '      <div class="detail-value">' . ucfirst(htmlspecialchars($order['status'])) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Total Amount:</div>';
    echo '      <div class="detail-value">$' . number_format($order['total_amount'], 2) . '</div>';
    echo '    </div>';
    
    // Shipping address
    echo '    <h3>Shipping Address</h3>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Name:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['shipping_name']) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Address:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['shipping_address']) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">City:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['shipping_city']) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Postal Code:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['shipping_postal_code']) . '</div>';
    echo '    </div>';
    
    echo '    <div class="detail-row">';
    echo '      <div class="detail-label">Country:</div>';
    echo '      <div class="detail-value">' . htmlspecialchars($order['shipping_country']) . '</div>';
    echo '    </div>';
    
    // Order items
    echo '    <h3>Order Items</h3>';
    
    echo '    <table class="cart-table">';
    echo '      <thead>';
    echo '        <tr>';
    echo '          <th>Product</th>';
    echo '          <th>Price</th>';
    echo '          <th>Quantity</th>';
    echo '          <th>Total</th>';
    echo '        </tr>';
    echo '      </thead>';
    echo '      <tbody>';
    
    foreach ($orderItems as $item) {
        $image = !empty($item['image']) ? $item['image'] : 'default-product.jpg';
        $unitPrice = number_format($item['price'], 2);
        $itemTotal = number_format($item['total'], 2);
        
        echo '        <tr>';
        echo '          <td>';
        echo '            <div style="display: flex; align-items: center;">';
        echo '              <img src="images/products/' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($item['name']) . '" class="cart-image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">';
        echo '              <span style="margin-left: 10px;">' . htmlspecialchars($item['name']) . '</span>';
        echo '            </div>';
        echo '          </td>';
        echo '          <td>$' . $unitPrice . '</td>';
        echo '          <td>' . $item['quantity'] . '</td>';
        echo '          <td>$' . $itemTotal . '</td>';
        echo '        </tr>';
    }
    
    echo '      </tbody>';
    echo '    </table>';
    
    echo '  </div>';
    
    echo '  <div class="action-buttons">';
    echo '    <a href="index.html" class="action-button">Continue Shopping</a>';
    echo '  </div>';
    
    echo '</div>';
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<div class="confirmation-container">';
    echo '  <div class="confirmation-icon">❌</div>';
    echo '  <div class="confirmation-message">Error</div>';
    echo '  <p>There was an error retrieving your order information. Please try again later.</p>';
    echo '  <div class="action-buttons">';
    echo '    <a href="index.html" class="action-button">Return to Home</a>';
    echo '  </div>';
    echo '</div>';
} finally {
    $conn = null;
}
?>
