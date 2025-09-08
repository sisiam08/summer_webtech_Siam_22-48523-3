<?php
// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a log file for debugging
$logFile = '../test_debug_log.txt';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("Test script started");

// Include required files
require_once '../config/database.php';

// Test database connection
writeLog("Testing database connection");
if ($conn) {
    writeLog("Database connection successful");
    
    // Test querying the products table structure
    $result = mysqli_query($conn, "DESCRIBE products");
    if ($result) {
        writeLog("Products table structure:");
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row;
            writeLog("  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']})");
        }
    } else {
        writeLog("Error querying products table: " . mysqli_error($conn));
    }
    
    // Try a simple insert
    writeLog("Attempting a test product insert");
    
    $shopId = 1;
    $name = "Test Product " . time();
    $categoryId = 1;
    $price = 9.99;
    $discountedPrice = 8.99;
    $stock = 10;
    $unit = "piece";
    $description = "This is a test product";
    $imageName = "default.jpg";
    $isActive = 1;
    $isFeatured = 0;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert product into database
        $stmt = $conn->prepare("
            INSERT INTO products (
                shop_id, name, category_id, price, discounted_price, stock, unit, 
                description, image, is_active, is_featured, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        // Bind parameters with correct types
        $bindResult = $stmt->bind_param(
            'isiddiissii',
            $shopId, $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
            $description, $imageName, $isActive, $isFeatured
        );
        
        if (!$bindResult) {
            throw new Exception("Parameter binding failed: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add product: " . $stmt->error);
        }
        
        $productId = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        writeLog("Test product inserted successfully with ID: $productId");
        
    } catch (Exception $e) {
        // Rollback transaction if one is active
        if ($conn->ping()) {
            $conn->rollback();
        }
        
        writeLog("Error during test insert: " . $e->getMessage());
    }
    
} else {
    writeLog("Database connection failed");
}

echo "Test completed. Check the test_debug_log.txt file for results.";
?>
