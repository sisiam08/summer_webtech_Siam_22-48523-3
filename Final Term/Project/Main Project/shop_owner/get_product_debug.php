<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = '127.0.0.1';
$username = 'root';
$password = 'Siam@MySQL2025';
$database = 'grocery_store';
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$productId = $_GET['id'];

try {
    // Get product data with detailed error handling
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $bindResult = $stmt->bind_param('i', $productId);
    if (!$bindResult) {
        throw new Exception('Parameter binding failed: ' . $stmt->error);
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Add debug info
    $product['debug_info'] = [
        'query_success' => true,
        'product_id' => $productId,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $product
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>
