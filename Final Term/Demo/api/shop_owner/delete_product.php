<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_POST['id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$product_id = $_POST['id'];

try {
    // First check if the product belongs to this shop owner
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND shop_owner_id = ?");
    $stmt->execute([$product_id, $shop_owner_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found or you do not have permission to delete it']);
        exit;
    }
    
    // Check if the product is used in any orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    if ($orderCount > 0) {
        // If product is used in orders, just mark it as deleted (soft delete)
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$product_id]);
    } else {
        // If product is not used in orders, delete it completely
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Return success message
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Rollback the transaction on error
    $pdo->rollBack();
    
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
