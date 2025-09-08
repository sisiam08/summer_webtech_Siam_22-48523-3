<?php
// Fix the original save_product.php file
header('Content-Type: application/json');

try {
    // Path to save_product.php
    $filePath = __DIR__ . '/save_product.php';
    
    // New content for save_product.php
    $newContent = <<<'PHP'
<?php
// Start session
session_start();

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Get shop ID from session (use 1 as default if not set)
    $shopId = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 1;
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $unit = $_POST['unit'] ?? 'piece';
    $description = $_POST['description'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? ($_POST['discount_percent'] ?? 0) : 0;
    
    // Validate required fields
    if (empty($name) || empty($categoryId) || empty($price) || $stock === '' || empty($unit)) {
        throw new Exception('Please fill all required fields');
    }
    
    // Handle image upload
    $imageName = 'default.jpg'; // Default image name
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
    }
    
    // Calculate discounted price
    $discountedPrice = $hasDiscount ? $price * (1 - $discountPercent / 100) : null;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert product into database
    $stmt = $conn->prepare("
        INSERT INTO products (
            shop_id, name, category_id, price, discounted_price, stock, unit, 
            description, image, is_active, is_featured, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $bindResult = $stmt->bind_param(
        'isiddsssii',
        $shopId, $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
        $description, $imageName, $isActive, $isFeatured
    );
    
    if (!$bindResult) {
        throw new Exception('Parameter binding failed: ' . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add product: ' . $stmt->error);
    }
    
    $productId = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if one is active
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
PHP;

    // Write the new content to the file
    if (file_put_contents($filePath, $newContent) !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'save_product.php file updated successfully'
        ]);
    } else {
        throw new Exception('Failed to write to save_product.php file');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
