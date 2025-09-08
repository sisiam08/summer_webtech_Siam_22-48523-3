<?php
// Automatic database fixer script
// This script will automatically fix the products table structure

// Connect to database directly
$host = 'localhost';
$user = 'root'; // Default XAMPP user
$pass = 'Siam@MySQL2025'; // Your actual database password
$db = 'grocery_store'; // Your database name

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

    // Start with empty array for results
    $results = [];

    // Check if products table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
    if ($tableCheck->num_rows == 0) {
        // Create products table if it doesn't exist
        $createTable = "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            stock INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($createTable)) {
            $results[] = "Created products table";
        } else {
            throw new Exception("Failed to create products table: " . $conn->error);
        }
    }

    // Define required columns and their definitions
    $requiredColumns = [
        'shop_id' => "ADD COLUMN shop_id INT NOT NULL DEFAULT 1 AFTER id",
        'category_id' => "ADD COLUMN category_id INT AFTER shop_id",
        'name' => null, // Already exists
        'description' => null, // Already exists
        'price' => null, // Already exists
        'discounted_price' => "ADD COLUMN discounted_price DECIMAL(10, 2) NULL AFTER price",
        'stock' => null, // Already exists
        'unit' => "ADD COLUMN unit VARCHAR(50) DEFAULT 'piece' AFTER stock",
        'image' => "ADD COLUMN image VARCHAR(255) DEFAULT 'default.jpg' AFTER unit",
        'is_active' => "ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER image",
        'is_featured' => "ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active",
        'created_at' => null // Already exists
    ];

    // Get current columns in the products table
    $columns = [];
    $columnCheck = $conn->query("SHOW COLUMNS FROM products");
    while ($row = $columnCheck->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }

    // Add missing columns
    foreach ($requiredColumns as $column => $definition) {
        if (!isset($columns[$column]) && $definition !== null) {
            $sql = "ALTER TABLE products $definition";
            if ($conn->query($sql)) {
                $results[] = "Added column: $column";
            } else {
                $results[] = "Failed to add column $column: " . $conn->error;
            }
        }
    }

    // Remove redundant 'featured' column if it exists
    if (isset($columns['featured'])) {
        // Copy values to is_featured
        $updateSql = "UPDATE products SET is_featured = featured WHERE featured = 1 AND is_featured = 0";
        $conn->query($updateSql);
        
        // Drop the column
        $dropSql = "ALTER TABLE products DROP COLUMN featured";
        if ($conn->query($dropSql)) {
            $results[] = "Removed redundant column: featured";
        } else {
            $results[] = "Failed to remove column featured: " . $conn->error;
        }
    }

    // Ensure non-null columns have proper defaults
    $defaultFixes = [
        "ALTER TABLE products MODIFY COLUMN shop_id INT NOT NULL DEFAULT 1",
        "ALTER TABLE products MODIFY COLUMN price DECIMAL(10, 2) NOT NULL",
        "ALTER TABLE products MODIFY COLUMN stock INT NOT NULL DEFAULT 0",
        "ALTER TABLE products MODIFY COLUMN unit VARCHAR(50) NOT NULL DEFAULT 'piece'",
        "ALTER TABLE products MODIFY COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
        "ALTER TABLE products MODIFY COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0"
    ];

    foreach ($defaultFixes as $sql) {
        if ($conn->query($sql)) {
            $results[] = "Fixed column defaults: " . explode(' ', $sql)[4];
        } else {
            $results[] = "Failed to fix column defaults: " . $conn->error;
        }
    }

    // Return results
    echo json_encode([
        'success' => true,
        'message' => 'Database structure updated',
        'actions' => $results,
        'columns' => array_keys($columns)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
