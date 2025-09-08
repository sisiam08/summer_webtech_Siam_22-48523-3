<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Disable error display but log errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Simple debug function
function debug_to_file($message) {
    file_put_contents('../product_update_debug.log', date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND);
}

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Database connection - directly in this file to avoid any issues
$host = '127.0.0.1';
$username = 'root';
$password = 'Siam@MySQL2025';
$database = 'grocery_store';
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    debug_to_file("Connection failed: " . $conn->connect_error);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

debug_to_file("Database connected successfully");

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
    $productId = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $description = $_POST['description'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    debug_to_file("Unit value from form: " . $unit);
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? ($_POST['discount_percent'] ?? 0) : 0;
    $currentImage = $_POST['current_image'] ?? '';
    
    debug_to_file("Form data: " . json_encode($_POST));
    
    // Validate required fields
    if (empty($productId) || empty($name) || empty($categoryId) || empty($price) || $stock === '' || empty($unit)) {
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
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $imageName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload image');
        }
        
        // Delete old image if exists and is different from default
        if (!empty($currentImage) && $currentImage !== 'default.jpg' && file_exists($uploadDir . $currentImage)) {
            unlink($uploadDir . $currentImage);
        }
    }
    
    // Calculate discounted price
    $discountedPrice = $hasDiscount ? $price * (1 - $discountPercent / 100) : null;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update product in database - removed updated_at column
    $stmt = $conn->prepare("
        UPDATE products SET
            name = ?,
            category_id = ?,
            price = ?,
            discounted_price = ?,
            stock = ?,
            unit = ?,
            description = ?,
            image = ?,
            is_active = ?,
            is_featured = ?
        WHERE id = ?
    ");
    
    debug_to_file("Prepare statement successful");
    
    $stmt->bind_param(
        'siddsssiii',
        $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
        $description, $imageName, $isActive, $isFeatured, $productId
    );
    
    debug_to_file("Parameters bound successfully");
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product: ' . $stmt->error);
    }
    
    debug_to_file("Product updated successfully with ID: $productId");
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
    
} catch (Exception $e) {
    debug_to_file("Error: " . $e->getMessage());
    
    // Rollback transaction if one is active
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
        debug_to_file("Transaction rolled back");
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error updating product: ' . $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Make sure to exit after sending JSON response
exit;
?>
