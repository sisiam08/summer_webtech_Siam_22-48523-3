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

// Check if vendor ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor ID is required'
    ]);
    exit;
}

$vendorId = intval($data['id']);

// Check if vendor exists
$checkSql = "SELECT id FROM vendors WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('i', $vendorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor not found'
    ]);
    exit;
}

// Check if vendor has products
$checkProductsSql = "SELECT COUNT(*) as count FROM products WHERE vendor_id = ?";
$stmt = $conn->prepare($checkProductsSql);
$stmt->bind_param('i', $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$productCount = $result->fetch_assoc()['count'];

if ($productCount > 0) {
    // Option 1: Prevent deletion if vendor has products
    /* 
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete vendor. There are ' . $productCount . ' products linked to this vendor. Please reassign these products to another vendor first.'
    ]);
    exit;
    */
    
    // Option 2: Automatically set vendor_id to NULL for all products
    $updateProductsSql = "UPDATE products SET vendor_id = NULL WHERE vendor_id = ?";
    $stmt = $conn->prepare($updateProductsSql);
    $stmt->bind_param('i', $vendorId);
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update vendor products: ' . $conn->error
        ]);
        exit;
    }
}

// Delete vendor
$sql = "DELETE FROM vendors WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $vendorId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Vendor deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete vendor: ' . $conn->error
    ]);
}
?>
