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

// Handle image upload
$imageName = null;
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
}

// Insert new product
$sql = "INSERT INTO products (name, description, price, stock, category_id, vendor_id, status, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssdiisss', $name, $description, $price, $stock, $categoryId, $vendorId, $status, $imageName);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add product: ' . $conn->error
    ]);
}
?>
