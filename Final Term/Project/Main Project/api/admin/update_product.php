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

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if product ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$productId = intval($_POST['id']);

// Validate required fields
$requiredFields = ['name', 'description', 'price', 'stock', 'category_id', 'vendor_id', 'status'];
$errors = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Sanitize input
$name = sanitize($_POST['name']);
$description = sanitize($_POST['description']);
$price = floatval($_POST['price']);
$stock = intval($_POST['stock']);
$categoryId = intval($_POST['category_id']);
$vendorId = intval($_POST['vendor_id']);
$status = sanitize($_POST['status']);

// Get current product info
$currentProductSql = "SELECT image FROM products WHERE id = ?";
$stmt = $conn->prepare($currentProductSql);
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

$currentProduct = $result->fetch_assoc();
$currentImage = $currentProduct['image'];

// Handle image upload
$imageName = $currentImage; // Default to current image
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imageName = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $imageName;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload image'
        ]);
        exit;
    }
    
    // Delete old image if exists
    if ($currentImage && file_exists($uploadDir . $currentImage)) {
        unlink($uploadDir . $currentImage);
    }
}

// Update product
$sql = "UPDATE products 
        SET name = ?, description = ?, price = ?, stock = ?, 
            category_id = ?, vendor_id = ?, status = ?, image = ? 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssdiiissi', $name, $description, $price, $stock, 
                   $categoryId, $vendorId, $status, $imageName, $productId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update product: ' . $conn->error
    ]);
}
?>
