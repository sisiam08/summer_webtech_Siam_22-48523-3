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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Check if product ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$productId = intval($data['id']);

// Check if product exists
$checkSql = "SELECT id, image FROM products WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found'
    ]);
    exit;
}

// Get product image before deleting
$product = $result->fetch_assoc();
$productImage = $product['image'];

// Check if product has related orders
$orderCheckSql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
$stmt = $conn->prepare($orderCheckSql);
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();
$orderCount = $result->fetch_assoc()['count'];

if ($orderCount > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete product as it has related orders. Consider setting it as inactive instead.'
    ]);
    exit;
}

// Delete product
$sql = "DELETE FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $productId);

if ($stmt->execute()) {
    // Delete product image if exists
    if ($productImage) {
        $imagePath = '../../uploads/products/' . $productImage;
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete product: ' . $conn->error
    ]);
}
?>
