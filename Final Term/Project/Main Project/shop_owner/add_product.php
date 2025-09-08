<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../config/database.php';

try {
    // Validate required fields
    $requiredFields = ['name', 'category_id', 'price', 'stock', 'description'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $categoryId = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    
    // Handle image upload
    $imageName = 'no-image.jpg'; // Default image
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('product_') . '.' . $fileExtension;
        $targetFile = $uploadDir . $imageName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            throw new Exception('Failed to upload image');
        }
    }
    
    // For demonstration, we'll return success
    // In a real application, you would insert data into the database
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product' => [
            'id' => rand(10, 100), // Simulated new product ID
            'name' => $name,
            'category_id' => $categoryId,
            'price' => $price,
            'stock' => $stock,
            'description' => $description,
            'image' => $imageName
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $e->getMessage()]);
}
?>
