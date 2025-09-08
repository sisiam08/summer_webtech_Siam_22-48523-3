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

// Check if category ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category ID is required'
    ]);
    exit;
}

$categoryId = intval($data['id']);

// Check if category exists
$checkSql = "SELECT id FROM categories WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Category not found'
    ]);
    exit;
}

// Check if category is in use by products
$checkProductsSql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
$stmt = $conn->prepare($checkProductsSql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();
$productCount = $result->fetch_assoc()['count'];

if ($productCount > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete category. There are ' . $productCount . ' products using this category. Please reassign these products to another category first.'
    ]);
    exit;
}

// Delete category
$sql = "DELETE FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $categoryId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Category deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete category: ' . $conn->error
    ]);
}
?>
