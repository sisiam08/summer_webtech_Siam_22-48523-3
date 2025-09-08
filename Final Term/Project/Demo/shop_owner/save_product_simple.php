<?php
// Simple product saving script - no authentication, no image uploads, just basic DB insertion
// Connect to database directly
$host = 'localhost';
$user = 'root'; // Default XAMPP user
$pass = 'Siam@MySQL2025'; // Your actual database password
$db = 'grocery_store'; // Your database name - adjust if different

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get minimal form data
    $name = $_POST['name'] ?? 'Test Product';
    $categoryId = $_POST['category_id'] ?? 1;
    $price = $_POST['price'] ?? 9.99;
    $stock = $_POST['stock'] ?? 10;
    $unit = $_POST['unit'] ?? 'piece';
    $description = $_POST['description'] ?? 'Test description';
    
    // Hard-code values for simplicity
    $shopId = 1; 
    $isActive = 1;
    $isFeatured = 0;
    $discountedPrice = null;
    $imageName = 'default.jpg';
    
    // Check if the products table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception("The products table does not exist");
    }

    // Check table structure
    $columns = [];
    $structureCheck = $conn->query("DESCRIBE products");
    while ($row = $structureCheck->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    // Simplify the SQL query based on existing columns
    $fields = [];
    $placeholders = [];
    $values = [];
    $types = '';
    
    // Add fields that definitely exist in most DB schemas
    $fieldMap = [
        'name' => ['value' => $name, 'type' => 's'],
        'price' => ['value' => $price, 'type' => 'd'],
        'description' => ['value' => $description, 'type' => 's'],
        'stock' => ['value' => $stock, 'type' => 'i']
    ];
    
    // Add optional fields if they exist in the database
    if (isset($columns['category_id'])) {
        $fieldMap['category_id'] = ['value' => $categoryId, 'type' => 'i'];
    }
    
    if (isset($columns['shop_id'])) {
        $fieldMap['shop_id'] = ['value' => $shopId, 'type' => 'i'];
    }
    
    if (isset($columns['unit'])) {
        $fieldMap['unit'] = ['value' => $unit, 'type' => 's'];
    }
    
    if (isset($columns['discounted_price'])) {
        $fieldMap['discounted_price'] = ['value' => $discountedPrice, 'type' => 'd'];
    }
    
    if (isset($columns['image'])) {
        $fieldMap['image'] = ['value' => $imageName, 'type' => 's'];
    }
    
    if (isset($columns['is_active'])) {
        $fieldMap['is_active'] = ['value' => $isActive, 'type' => 'i'];
    }
    
    if (isset($columns['is_featured'])) {
        $fieldMap['is_featured'] = ['value' => $isFeatured, 'type' => 'i'];
    }
    
    // Build the query dynamically
    foreach ($fieldMap as $field => $data) {
        $fields[] = $field;
        $placeholders[] = '?';
        $values[] = $data['value'];
        $types .= $data['type'];
    }
    
    // Add created_at if it exists
    if (isset($columns['created_at'])) {
        $fields[] = 'created_at';
        $placeholders[] = 'NOW()';
    }
    
    // Build the SQL query
    $sql = "INSERT INTO products (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    // Log for debugging
    $debugInfo = [
        'sql' => $sql,
        'types' => $types,
        'values' => $values,
        'columns' => array_keys($columns)
    ];
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error . " SQL: " . $sql);
    }
    
    // Build the parameter array for bind_param
    $bindParams = [$types];
    foreach ($values as $i => $value) {
        $bindParams[] = &$values[$i];
    }
    
    // Call bind_param with the dynamic parameters
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $productId = $conn->insert_id;
    
    // Return success with debugging info
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $productId,
        'debug' => $debugInfo
    ]);

} catch (Exception $e) {
    // Return detailed error information
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => isset($debugInfo) ? $debugInfo : null,
        'post' => $_POST
    ]);
}
?>
