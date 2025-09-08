<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Order ID is required'
    ]);
    exit;
}

$orderId = $_GET['id'];

// Get order details
$orderSql = "SELECT o.*, pm.name as payment_method 
            FROM orders o
            LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
            WHERE o.id = ?";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    echo json_encode([
        'error' => 'Order not found'
    ]);
    exit;
}

$order = $orderResult->fetch_assoc();

// Get customer details
$customerSql = "SELECT * FROM users WHERE id = ?";
$customerStmt = $conn->prepare($customerSql);
$customerStmt->bind_param('i', $order['user_id']);
$customerStmt->execute();
$customerResult = $customerStmt->get_result();
$customer = $customerResult->fetch_assoc();

// Get shipping address
$addressSql = "SELECT * FROM addresses WHERE id = ?";
$addressStmt = $conn->prepare($addressSql);
$addressStmt->bind_param('i', $order['address_id']);
$addressStmt->execute();
$addressResult = $addressStmt->get_result();
$address = $addressResult->fetch_assoc();

// Format shipping address
$shippingAddress = $address['street'] . ', ' . $address['city'] . ', ' . $address['state'] . ', ' . $address['zip_code'] . ', ' . $address['country'];

// Get order items
$itemsSql = "SELECT oi.*, p.name as product_name 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Return order details
echo json_encode([
    'order' => $order,
    'customer' => $customer,
    'shipping_address' => $shippingAddress,
    'items' => $items
]);
?>
