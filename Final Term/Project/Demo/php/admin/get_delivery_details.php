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

// Check if delivery ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Delivery ID is required'
    ]);
    exit;
}

$deliveryId = intval($_GET['id']);

try {
    // Get delivery details
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as delivery_person_name, u.phone as delivery_person_phone, u.email as delivery_person_email
        FROM deliveries d
        JOIN users u ON d.delivery_person_id = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$deliveryId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$delivery) {
        echo json_encode([
            'error' => 'Delivery not found'
        ]);
        exit;
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$delivery['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image, s.name as shop_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN shops s ON p.shop_id = s.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$delivery['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get delivery person details
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$delivery['delivery_person_id']]);
    $deliveryPerson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'delivery' => $delivery,
        'order' => $order,
        'items' => $items,
        'delivery_person' => $deliveryPerson
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
