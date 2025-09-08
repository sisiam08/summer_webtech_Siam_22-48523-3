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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Vendor ID is required'
    ]);
    exit;
}

$vendorId = intval($_GET['id']);

// Get vendor by ID
$sql = "SELECT * FROM vendors WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $vendorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'error' => 'Vendor not found'
    ]);
    exit;
}

$vendor = $result->fetch_assoc();

// Get vendor's products
$productsSql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($productsSql);
$stmt->bind_param('i', $vendorId);
$stmt->execute();
$productsResult = $stmt->get_result();

$products = [];
while ($product = $productsResult->fetch_assoc()) {
    $products[] = $product;
}

// Add products to vendor data
$vendor['products'] = $products;

// Return vendor data
echo json_encode($vendor);
?>
