<?php
// Get detailed information about a specific order
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

// Get order ID from request
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = intval($_GET['id']);

try {
    // First, check if the order belongs to the logged-in customer
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE id = :order_id AND customer_id = :customer_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found or you do not have permission to view it']);
        exit;
    }
    
    // Get order details
    $stmt = $conn->prepare("SELECT o.*, 
                                  o.subtotal,
                                  o.tax,
                                  o.shipping_cost,
                                  o.created_at,
                                  o.updated_at,
                                  a.id as address_id,
                                  a.name,
                                  a.address_line1,
                                  a.address_line2,
                                  a.city,
                                  a.state,
                                  a.postal_code,
                                  a.country,
                                  a.phone
                           FROM orders o
                           LEFT JOIN addresses a ON o.shipping_address_id = a.id
                           WHERE o.id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Format shipping address
    $order['shipping_address'] = [
        'id' => $order['address_id'],
        'name' => $order['name'],
        'address_line1' => $order['address_line1'],
        'address_line2' => $order['address_line2'],
        'city' => $order['city'],
        'state' => $order['state'],
        'postal_code' => $order['postal_code'],
        'country' => $order['country'],
        'phone' => $order['phone']
    ];
    
    // Remove address fields from main order array
    unset($order['address_id']);
    unset($order['name']);
    unset($order['address_line1']);
    unset($order['address_line2']);
    unset($order['city']);
    unset($order['state']);
    unset($order['postal_code']);
    unset($order['country']);
    unset($order['phone']);
    
    // Get order items
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.price, p.image
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to order
    $order['items'] = $items;
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($order);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
