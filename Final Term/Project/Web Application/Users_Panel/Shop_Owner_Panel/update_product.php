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
    // Get shop ID from session
    $shopId = $_SESSION['shop_id'] ?? null;
    
    if (!$shopId) {
        throw new Exception('No shop associated with this account');
    }
    
    // Get form data
    $productId = $_POST['product_id'] ?? $_POST['id'] ?? '';
    $name = $_POST['product_name'] ?? $_POST['name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $description = $_POST['description'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? ($_POST['discount_percent'] ?? 0) : 0;
    $currentImage = $_POST['current_image'] ?? '';
    
    // Debug: Log received POST data
    error_log("Received POST data: " . json_encode($_POST));
    error_log("Extracted values: productId=$productId, name=$name, categoryId=$categoryId, price=$price, stock=$stock, unit=$unit");
    
    // Validate required fields
    if (empty($productId) || empty($name) || empty($categoryId) || $price === '' || $price <= 0 || $stock === '' || empty($unit)) {
        error_log("Validation failed: productId=" . ($productId ?: 'EMPTY') . ", name=" . ($name ?: 'EMPTY') . 
                 ", categoryId=" . ($categoryId ?: 'EMPTY') . ", price=" . ($price !== '' ? $price : 'EMPTY') . 
                 ", stock=" . ($stock !== '' ? $stock : 'EMPTY') . ", unit=" . ($unit ?: 'EMPTY'));
        throw new Exception('Please fill all required fields');
    }
    
    // Verify the product belongs to this shop
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND shop_id = ?");
    $stmt->bind_param('ii', $productId, $shopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Product not found or does not belong to your shop');
    }
    
    // Handle image upload
    $imageName = $currentImage;
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
        
        // Delete old image if exists and is different
        if (!empty($currentImage) && file_exists($uploadDir . $currentImage)) {
            unlink($uploadDir . $currentImage);
        }
    }
    
    // Calculate discounted price
    $discountedPrice = $hasDiscount ? $price * (1 - $discountPercent / 100) : null;
    
    // Update product in database
    $stmt = $conn->prepare("
        UPDATE products SET
            name = ?,
            category_id = ?,
            price = ?,
            stock = ?,
            unit = ?,
            description = ?,
            image = ?,
            is_active = ?,
            is_featured = ?
        WHERE id = ? AND shop_id = ?
    ");
    
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // Debug information
    error_log("Updating product ID: $productId for shop: $shopId");
    error_log("Parameters: name=$name, category_id=$categoryId, price=$price, stock=$stock, unit=$unit, image=$imageName");
    
    // Correct parameter binding
    $stmt->bind_param(
        'sidisissiii',
        $name, $categoryId, $price, $stock, $unit, 
        $description, $imageName, $isActive, $isFeatured, $productId, $shopId
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        error_log("MySQL Error: " . $stmt->error);
        throw new Exception('Failed to update product: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Exception in update_product.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
