<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get order ID
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$userId = $_SESSION['user_id'];
$conn = connectDB();

try {
    // Get order details with items
    $stmt = $conn->prepare("
        SELECT oi.id, oi.product_id, oi.quantity, oi.price,
               p.name as product_name, p.image as product_image
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = :order_id AND o.user_id = :user_id
        ORDER BY oi.id
    ");
    
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Order not found or no items']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>