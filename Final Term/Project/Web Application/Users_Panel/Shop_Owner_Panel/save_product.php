<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $conn = connectDB();
    
    // Get shop ID using the function
    $shopId = getShopIdForOwner();
    
    if (!$shopId) {
        throw new Exception('No shop associated with this account');
    }
    
    // Get form data
    $name = $_POST['product_name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $cost = $_POST['cost'] ?? null;
    $stock = $_POST['stock'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $description = $_POST['description'] ?? '';
    $minStock = $_POST['min_stock'] ?? 5;
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? ($_POST['discount_percent'] ?? 0) : 0;
    
    // Validate required fields
    if (empty($name) || empty($categoryId) || empty($price) || $stock === '' || empty($unit) || empty($description)) {
        throw new Exception('Please fill all required fields');
    }
    
    // Validate numeric fields
    if (!is_numeric($price) || $price <= 0) {
        throw new Exception('Price must be a positive number');
    }
    
    if (!is_numeric($stock) || $stock < 0) {
        throw new Exception('Stock must be a non-negative number');
    }
    
    if ($hasDiscount && (!is_numeric($discountPercent) || $discountPercent < 1 || $discountPercent > 99)) {
        throw new Exception('Discount percentage must be between 1 and 99');
    }
    
    // Handle image upload
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP');
        }
        
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $maxFileSize) {
            throw new Exception('Image size exceeds the limit (5MB)');
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('product_') . '.' . $extension;
        
        // Create uploads directory if it doesn't exist
        $uploadDir = '../../Uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $imageName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload image');
        }
    }
    
    // Calculate discounted price
    $discountedPrice = $hasDiscount ? $price * (1 - $discountPercent / 100) : null;
    
    // Insert product into database
    $stmt = $conn->prepare("
        INSERT INTO products (
            shop_id, name, category_id, price, cost, discounted_price, 
            stock, min_stock, unit, description, image, is_active, is_featured, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // Debug information
    error_log("Adding new product for shop ID: $shopId");
    error_log("Parameters: name=$name, category_id=$categoryId, price=$price, cost=" . 
             ($cost ?? 'NULL') . ", discounted_price=" . 
             ($discountedPrice ?? 'NULL') . ", stock=$stock, min_stock=$minStock, unit=$unit, image=" . ($imageName ?? 'NULL'));
    
    // Execute with parameters
    if ($stmt->execute([
        $shopId, $name, $categoryId, $price, $cost, $discountedPrice, 
        $stock, $minStock, $unit, $description, $imageName, $isActive, $isFeatured
    ])) {
        $productId = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'product_id' => $productId
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("PDO Error: " . $errorInfo[2]);
        throw new Exception('Failed to add product: ' . $errorInfo[2]);
    }
    
} catch (Exception $e) {
    error_log("Exception in save_product.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>