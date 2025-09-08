<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY: Skip authentication for this script
/*
// Check if user is logged in and is a shop owner or admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'shop_owner' && $_SESSION['user_role'] !== 'admin')) {
    // Return JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}
*/

// Function to execute SQL query
function executeSqlQuery($conn, $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            return [
                'success' => true,
                'message' => 'SQL executed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error executing SQL: ' . $conn->error
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// SQL queries to add columns
$queries = [];

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($sql);
    return $result->num_rows > 0;
}

// Required columns in products table
$requiredColumns = [
    'discounted_price' => "ALTER TABLE products ADD COLUMN discounted_price DECIMAL(10, 2) DEFAULT NULL AFTER price",
    'unit' => "ALTER TABLE products ADD COLUMN unit VARCHAR(50) DEFAULT 'piece' AFTER stock",
    'shop_id' => "ALTER TABLE products ADD COLUMN shop_id INT DEFAULT 1 AFTER id",
    'is_active' => "ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER image",
    'is_featured' => "ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active"
];

// Fix NULL values in shop_id column
if (columnExists($conn, 'products', 'shop_id')) {
    $queries[] = "UPDATE products SET shop_id = 1 WHERE shop_id IS NULL";
}

// Add a NOT NULL constraint to shop_id column if possible
$queries[] = "ALTER TABLE products MODIFY COLUMN shop_id INT NOT NULL DEFAULT 1";

// Remove redundant featured column if it exists
if (columnExists($conn, 'products', 'featured')) {
    // Transfer values from featured to is_featured if needed
    $queries[] = "UPDATE products SET is_featured = featured WHERE featured = 1 AND is_featured = 0";
    $queries[] = "ALTER TABLE products DROP COLUMN featured";
}

// Execute each query and collect results
$results = [];
foreach ($queries as $sql) {
    $results[] = executeSqlQuery($conn, $sql);
}

// Return results
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'results' => $results
]);
?>
