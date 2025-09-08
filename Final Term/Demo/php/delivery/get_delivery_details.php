<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if delivery ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Delivery ID is required'
    ]);
    exit;
}

$delivery_id = $_GET['id'];

// Connect to database
require_once '../db_connect.php';

try {
    // Get delivery person ID to ensure they can only view their own deliveries
    $stmt = $conn->prepare("SELECT id FROM delivery_personnel WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'Delivery personnel not found'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $delivery_person_id = $row['id'];
    
    // Get delivery details
    $stmt = $conn->prepare("
        SELECT d.*, o.order_number, o.created_at, o.total_amount, o.payment_method,
        c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.phone AS customer_phone,
        a.name AS address_name, a.address_line1, a.address_line2, a.city, a.state, a.postal_code, a.phone AS address_phone
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users c ON o.user_id = c.id
        JOIN addresses a ON o.address_id = a.id
        WHERE d.id = ? AND d.delivery_person_id = ?
    ");
    
    $stmt->bind_param("ii", $delivery_id, $delivery_person_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'Delivery not found or not assigned to you'
        ]);
        exit;
    }
    
    $delivery = $result->fetch_assoc();
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    
    $stmt->bind_param("i", $delivery['order_id']);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    $item_count = 0;
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
        $item_count += $item['quantity'];
    }
    
    // Format data for response
    $delivery['customer_name'] = $delivery['customer_first_name'] . ' ' . $delivery['customer_last_name'];
    $delivery['address'] = [
        'name' => $delivery['address_name'],
        'address_line1' => $delivery['address_line1'],
        'address_line2' => $delivery['address_line2'],
        'city' => $delivery['city'],
        'state' => $delivery['state'],
        'postal_code' => $delivery['postal_code'],
        'phone' => $delivery['address_phone']
    ];
    $delivery['items'] = $items;
    $delivery['item_count'] = $item_count;
    
    // Clean up response
    unset($delivery['customer_first_name']);
    unset($delivery['customer_last_name']);
    unset($delivery['address_name']);
    unset($delivery['address_line1']);
    unset($delivery['address_line2']);
    unset($delivery['city']);
    unset($delivery['state']);
    unset($delivery['postal_code']);
    unset($delivery['address_phone']);
    
    echo json_encode($delivery);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
