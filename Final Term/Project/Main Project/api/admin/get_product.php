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

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Product ID is required'
    ]);
    exit;
}

$productId = $_GET['id'];

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'error' => 'Product not found'
    ]);
    exit;
}

$product = $result->fetch_assoc();

// Return product details
echo json_encode($product);
?>
