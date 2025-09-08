<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$orderId = (int)$_GET['id'];

// Include database connection
require_once '../config/database.php';

try {
    // For demonstration purposes, return dummy data
    // In a real application, you would fetch this from the database
    
    // Order details
    $order = [
        'id' => $orderId,
        'order_number' => 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
        'customer_name' => 'John Doe',
        'customer_email' => 'john.doe@example.com',
        'customer_phone' => '555-123-4567',
        'order_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 10) . ' days')),
        'status' => ['Processing', 'Shipped', 'Delivered'][rand(0, 2)],
        'payment_method' => ['Credit Card', 'PayPal', 'Cash on Delivery'][rand(0, 2)],
        'payment_status' => ['Paid', 'Pending', 'Failed'][rand(0, 2)],
        'subtotal' => 45.97,
        'tax' => 4.60,
        'shipping' => 5.00,
        'total' => 55.57,
        'shipping_address' => [
            'name' => 'John Doe',
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zipcode' => '12345',
            'country' => 'United States'
        ],
        'items' => [
            [
                'id' => 1,
                'product_id' => 101,
                'product_name' => 'Fresh Apples',
                'price' => 2.99,
                'quantity' => 5,
                'subtotal' => 14.95
            ],
            [
                'id' => 2,
                'product_id' => 203,
                'product_name' => 'Organic Milk',
                'price' => 3.49,
                'quantity' => 2,
                'subtotal' => 6.98
            ],
            [
                'id' => 3,
                'product_id' => 305,
                'product_name' => 'Whole Wheat Bread',
                'price' => 2.29,
                'quantity' => 1,
                'subtotal' => 2.29
            ]
        ],
        'history' => [
            [
                'status' => 'Order Placed',
                'date' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 10) . ' days')),
                'comment' => 'Order has been placed successfully'
            ],
            [
                'status' => 'Processing',
                'date' => date('Y-m-d H:i:s', strtotime('-' . rand(3, 5) . ' days')),
                'comment' => 'Payment confirmed, processing order'
            ],
            [
                'status' => 'Shipped',
                'date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 3) . ' days')),
                'comment' => 'Order has been shipped via Express Delivery'
            ]
        ]
    ];
    
    // Return order details
    echo json_encode($order);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching order details: ' . $e->getMessage()]);
}
?>
