<?php
// Add a product to the cart
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Get customer ID
$customer_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || empty($data['product_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

if (!isset($data['quantity']) || intval($data['quantity']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Valid quantity is required']);
    exit;
}

$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);

try {
    // Check if product exists and is in stock
    $stmt = $conn->prepare("SELECT id, name, price, sale_price, stock FROM products WHERE id = :product_id AND active = 1");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found or inactive']);
        exit;
    }
    
    if ($product['stock'] < $quantity) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not enough stock available. Only ' . $product['stock'] . ' items left.']);
        exit;
    }
    
    // Get the current price (regular or sale price)
    $price = ($product['sale_price'] && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
    
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // If not found, add to cart
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price
        ];
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'cart_count' => count($_SESSION['cart'])
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
