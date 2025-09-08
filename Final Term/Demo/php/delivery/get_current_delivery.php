<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Connect to database
require_once '../db_connect.php';

try {
    // Get delivery person ID
    $stmt = $conn->prepare("SELECT id FROM delivery_personnel WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Delivery personnel not found'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $delivery_id = $row['id'];
    
    // Get current delivery (prioritize 'picked_up' status, then 'assigned')
    $stmt = $conn->prepare("
        SELECT d.*, o.order_number, o.created_at, o.total_amount, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.phone AS customer_phone,
        a.name AS address_name, a.address_line1, a.address_line2, a.city, a.state, a.postal_code, a.phone AS address_phone
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users c ON o.user_id = c.id
        JOIN addresses a ON o.address_id = a.id
        WHERE d.delivery_person_id = ? AND d.status IN ('picked_up', 'assigned')
        ORDER BY FIELD(d.status, 'picked_up', 'assigned')
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'has_current_delivery' => false
        ]);
        exit;
    }
    
    $delivery = $result->fetch_assoc();
    
    // Format customer name
    $delivery['customer_name'] = $delivery['customer_first_name'] . ' ' . $delivery['customer_last_name'];
    
    // Format address
    $delivery['address'] = [
        'name' => $delivery['address_name'],
        'address_line1' => $delivery['address_line1'],
        'address_line2' => $delivery['address_line2'],
        'city' => $delivery['city'],
        'state' => $delivery['state'],
        'postal_code' => $delivery['postal_code'],
        'phone' => $delivery['address_phone']
    ];
    
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
    
    echo json_encode([
        'success' => true,
        'has_current_delivery' => true,
        'delivery' => $delivery
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
