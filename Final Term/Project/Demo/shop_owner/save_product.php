<?php
// Start session
session_start();

// Enable detailed error reporting for debugging
ini_set('display_errors', 0); // Disable displaying errors directly
error_reporting(E_ALL);

// Set headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Create a simple debugging function
function debug_to_file($message) {
    file_put_contents('../product_debug.log', date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND);
}

debug_to_file("Script started");

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Special case for viewing the file
    if (isset($_GET['view'])) {
        highlight_file(__FILE__);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
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

debug_to_file("Database connection successful");

try {
    // Get shop ID from session (use 1 as default if not set)
    $shopId = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 1;
    debug_to_file("Shop ID: $shopId");
    
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $unit = isset($_POST['unit']) ? trim($_POST['unit']) : 'piece';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
    $discountPercent = $hasDiscount ? (isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0) : 0;
    
    // Log form data
    debug_to_file("Form data: " . json_encode([
        'name' => $name,
        'category_id' => $categoryId,
        'price' => $price,
        'stock' => $stock,
        'unit' => $unit,
        'is_active' => $isActive,
        'is_featured' => $isFeatured,
        'has_discount' => $hasDiscount,
        'discount_percent' => $discountPercent
    ]));
    
    // Validate required fields
    if (empty($name) || empty($categoryId) || $price <= 0 || $stock < 0 || empty($unit)) {
        throw new Exception('Please fill all required fields with valid values');
    }
    
    // Handle image upload
    $imageName = 'default.jpg'; // Default image name
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        debug_to_file("Image upload: Type: $fileType, Size: " . $_FILES['image']['size']);
        
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
            if (!mkdir($uploadDir, 0755, true)) {
                debug_to_file("Failed to create upload directory: $uploadDir");
                throw new Exception('Failed to create upload directory');
            }
        }
        
        $uploadPath = $uploadDir . $imageName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            debug_to_file("Failed to move uploaded file to: $uploadPath");
            throw new Exception('Failed to upload image');
        }
        
        debug_to_file("Image uploaded successfully: $imageName");
    }
    
    // Calculate discounted price
    $discountedPrice = $hasDiscount ? ($price * (1 - $discountPercent / 100)) : null;
    debug_to_file("Price: $price, Discounted price: " . ($discountedPrice ?? 'NULL'));
    
    // Start transaction
    $conn->begin_transaction();
    debug_to_file("Transaction started");
    
    // First check if the products table exists
    $tableResult = $conn->query("SHOW TABLES LIKE 'products'");
    if ($tableResult->num_rows == 0) {
        debug_to_file("Products table does not exist");
        throw new Exception('Products table does not exist in the database');
    }
    
    // Check table structure
    $columnsResult = $conn->query("DESCRIBE products");
    $columns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    debug_to_file("Products table columns: " . implode(", ", $columns));
    
    // Insert product into database
    $stmt = $conn->prepare("
        INSERT INTO products (
            shop_id, name, category_id, price, discounted_price, stock, unit, 
            description, image, is_active, is_featured, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        debug_to_file("Prepare statement failed: " . $conn->error);
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    debug_to_file("Prepare statement successful");
    
    // Bind parameters - use the correct types
    // i - integer, s - string, d - double/decimal
    $bindResult = $stmt->bind_param(
        'isiddiissii',
        $shopId, $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
        $description, $imageName, $isActive, $isFeatured
    );
    
    if (!$bindResult) {
        debug_to_file("Parameter binding failed: " . $stmt->error);
        throw new Exception('Parameter binding failed: ' . $stmt->error);
    }
    
    debug_to_file("Parameter binding successful");
    debug_to_file("About to execute query");
    
    if (!$stmt->execute()) {
        debug_to_file("Execute failed: " . $stmt->error);
        throw new Exception('Failed to add product: ' . $stmt->error);
    }
    
    $productId = $conn->insert_id;
    debug_to_file("Product added successfully with ID: $productId");
    
    // Commit transaction
    $conn->commit();
    debug_to_file("Transaction committed");
    
    // Close statement
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $productId
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
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
        debug_to_file("Database connection closed");
    }
    
    debug_to_file("Script ended");
}
?>

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