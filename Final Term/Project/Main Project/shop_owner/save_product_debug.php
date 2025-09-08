<?php
// Start session
session_start();

// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for debugging (allow error output)
header("Content-Type: application/json");

// Log the raw POST data
file_put_contents('debug_post_data.log', print_r($_POST, true) . "\n\n" . print_r($_FILES, true));

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Check for authentication
$authenticated = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';

// Log authentication status
$authData = [
    'authenticated' => $authenticated,
    'session' => $_SESSION
];
file_put_contents('debug_auth.log', print_r($authData, true));

// Check if user is logged in and is a shop owner
if (!$authenticated) {
    // Not authenticated
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required',
        'session_data' => $_SESSION
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

try {
    // Get shop ID from session
    $shopId = $_SESSION['shop_id'] ?? 1; // Default to 1 if not set
    
    if (!$shopId) {
        throw new Exception('No shop associated with this account');
    }
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $description = $_POST['description'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? ($_POST['discount_percent'] ?? 0) : 0;
    
    // Log the parsed data
    $parsedData = [
        'shop_id' => $shopId,
        'name' => $name,
        'category_id' => $categoryId,
        'price' => $price,
        'stock' => $stock,
        'unit' => $unit,
        'description' => $description,
        'is_active' => $isActive,
        'is_featured' => $isFeatured,
        'has_discount' => $hasDiscount,
        'discount_percent' => $discountPercent
    ];
    file_put_contents('debug_parsed_data.log', print_r($parsedData, true));
    
    // Validate required fields
    if (empty($name) || empty($categoryId) || empty($price) || $stock === '' || empty($unit)) {
        throw new Exception('Please fill all required fields');
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
    
    // Check if products table has all required columns
    $tableCheckQuery = "SHOW COLUMNS FROM products";
    $tableCheckResult = $conn->query($tableCheckQuery);
    $columns = [];
    
    if ($tableCheckResult) {
        while ($column = $tableCheckResult->fetch_assoc()) {
            $columns[$column['Field']] = $column;
        }
    }
    
    // Log columns
    file_put_contents('debug_columns.log', print_r($columns, true));
    
    // Check if all required columns exist
    $requiredColumns = ['shop_id', 'name', 'category_id', 'price', 'discounted_price', 'stock', 'unit', 'description', 'image', 'is_active', 'is_featured'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!isset($columns[$column])) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        throw new Exception('Missing columns in products table: ' . implode(', ', $missingColumns));
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert product into database
    $sql = "
        INSERT INTO products (
            shop_id, name, category_id, price, discounted_price, stock, unit, 
            description, image, is_active, is_featured, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    // Log SQL
    file_put_contents('debug_sql.log', $sql);
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    // Bind parameters
    $bindResult = $stmt->bind_param(
        'isiddsssii',
        $shopId, $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
        $description, $imageName, $isActive, $isFeatured
    );
    
    if (!$bindResult) {
        throw new Exception('Parameter binding failed: ' . $stmt->error);
    }
    
    // Execute statement
    $executeResult = $stmt->execute();
    
    if (!$executeResult) {
        throw new Exception('SQL execution failed: ' . $stmt->error);
    }
    
    $productId = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $productId,
        'data' => $parsedData
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if one is active
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    
    // Log error
    file_put_contents('debug_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'query_error' => isset($stmt) ? $stmt->error : 'No statement',
        'conn_error' => isset($conn) ? $conn->error : 'No connection',
        'post_data' => $_POST,
        'session_data' => $_SESSION
    ]);
}
?>
