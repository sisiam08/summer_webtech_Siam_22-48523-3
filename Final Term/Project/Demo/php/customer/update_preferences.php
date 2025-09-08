<?php
// Update customer preferences
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

// Set default values if not provided
$newsletter = isset($data['newsletter']) ? (int)$data['newsletter'] : 0;
$promo_emails = isset($data['promo_emails']) ? (int)$data['promo_emails'] : 0;
$order_updates = isset($data['order_updates']) ? (int)$data['order_updates'] : 1;

try {
    // Update customer preferences
    $stmt = $conn->prepare("UPDATE customers SET 
                           newsletter = :newsletter,
                           promo_emails = :promo_emails,
                           order_updates = :order_updates,
                           updated_at = NOW()
                           WHERE id = :customer_id");
    
    $stmt->bindParam(':newsletter', $newsletter, PDO::PARAM_INT);
    $stmt->bindParam(':promo_emails', $promo_emails, PDO::PARAM_INT);
    $stmt->bindParam(':order_updates', $order_updates, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customer_id);
    
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Preferences updated successfully'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
