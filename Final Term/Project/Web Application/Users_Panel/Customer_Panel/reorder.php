<?php
// Reorder - add items from a previous order to the cart
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

if (!isset($data['order_id']) || empty($data['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = intval($data['order_id']);

try {
    // First, check if the order belongs to the logged-in customer
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE id = :order_id AND customer_id = :customer_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found or you do not have permission to reorder it']);
        exit;
    }
    
    // Get order items
    $stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.price, p.stock
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No items found in this order']);
        exit;
    }
    
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add items to cart
    $unavailableItems = [];
    
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        
        // Check if product is still available and has enough stock
        if ($item['stock'] <= 0) {
            $unavailableItems[] = $product_id;
            continue;
        }
        
        // Adjust quantity if necessary
        if ($quantity > $item['stock']) {
            $quantity = $item['stock'];
        }
        
        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['product_id'] == $product_id) {
                $cartItem['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // If not found, add to cart
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $item['price']
            ];
        }
    }
    
    // Return response
    header('Content-Type: application/json');
    
    if (!empty($unavailableItems)) {
        echo json_encode([
            'success' => true,
            'message' => 'Items have been added to your cart, but some items are no longer available.',
            'unavailableItems' => $unavailableItems
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'All items have been added to your cart.'
        ]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
