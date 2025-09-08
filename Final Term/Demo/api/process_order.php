<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$isLoggedIn) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to place an order']);
    exit;
}

// Get form data
$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$postalCode = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'cod';

// Validate required fields
if (empty($fullName) || empty($email) || empty($phone) || empty($address) || 
    empty($city) || empty($postalCode) || empty($country)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

// Connect to database
$conn = connectDB();

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get cart items
    $query = "SELECT c.product_id, c.quantity, p.price, p.name, (p.price * c.quantity) as item_total 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $cartItems = [];
    $orderTotal = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cartItems[] = $row;
        $orderTotal += $row['item_total'];
    }
    
    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    
    // Add shipping cost (free for now)
    $shipping = 0;
    $finalTotal = $orderTotal + $shipping;
    
    // Create order
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);
    $status = 'pending';
    
    $query = "INSERT INTO orders (order_number, user_id, total_amount, shipping_amount, status, 
                                  payment_method, shipping_name, shipping_email, shipping_phone, 
                                  shipping_address, shipping_city, shipping_postal_code, 
                                  shipping_country, notes, created_at) 
              VALUES (:order_number, :user_id, :total_amount, :shipping_amount, :status, 
                      :payment_method, :shipping_name, :shipping_email, :shipping_phone, 
                      :shipping_address, :shipping_city, :shipping_postal_code, 
                      :shipping_country, :notes, NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_number', $orderNumber, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':total_amount', $finalTotal, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_amount', $shipping, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':payment_method', $paymentMethod, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_name', $fullName, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_address', $address, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_city', $city, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_postal_code', $postalCode, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_country', $country, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->execute();
    
    $orderId = $conn->lastInsertId();
    
    // Add order items
    foreach ($cartItems as $item) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price, total) 
                  VALUES (:order_id, :product_id, :quantity, :price, :total)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price', $item['price'], PDO::PARAM_STR);
        $stmt->bindParam(':total', $item['item_total'], PDO::PARAM_STR);
        $stmt->execute();
    }
    
    // Clear cart
    $query = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully', 
        'order_id' => $orderId,
        'order_number' => $orderNumber
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error processing your order. Please try again later.']);
} finally {
    $conn = null;
}
?>
