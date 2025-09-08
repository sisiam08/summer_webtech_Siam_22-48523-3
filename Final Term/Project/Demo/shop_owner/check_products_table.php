<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Get the products table structure
$query = "DESCRIBE products";
$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get table structure: ' . $conn->error
    ]);
    exit;
}

// Create array to hold column info
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
}

// Get sample data from products table
$dataQuery = "SELECT * FROM products LIMIT 1";
$dataResult = $conn->query($dataQuery);
$sampleData = $dataResult->num_rows > 0 ? $dataResult->fetch_assoc() : null;

// Return the results
echo json_encode([
    'success' => true,
    'table_structure' => $columns,
    'sample_data' => $sampleData
]);
?>
