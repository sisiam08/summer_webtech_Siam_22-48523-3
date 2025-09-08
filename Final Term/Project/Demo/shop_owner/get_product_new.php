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
    file_put_contents('../product_get_debug.log', date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND);
}

debug_to_file("Script started");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    debug_to_file("User not authenticated");
    echo json_encode(null);
    exit;
}

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
    debug_to_file("Connection failed: " . $conn->connect_error);
    echo json_encode(null);
    exit;
}

debug_to_file("Database connected successfully");

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    debug_to_file("No product ID provided");
    echo json_encode(null);
    exit;
}

$productId = $_GET['id'];
debug_to_file("Fetching product with ID: $productId");

try {
    // Get product data
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    
    if (!$stmt) {
        debug_to_file("Prepare statement failed: " . $conn->error);
        echo json_encode(null);
        exit;
    }
    
    $bindResult = $stmt->bind_param('i', $productId);
    if (!$bindResult) {
        debug_to_file("Parameter binding failed: " . $stmt->error);
        echo json_encode(null);
        exit;
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        debug_to_file("Query execution failed: " . $stmt->error);
        echo json_encode(null);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        debug_to_file("Product not found");
        echo json_encode(null);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Ensure unit is properly formatted
    if (isset($product['unit'])) {
        // Make sure unit is lowercase to match option values
        $product['unit'] = strtolower($product['unit']);
        debug_to_file("Product unit: " . $product['unit']);
    } else {
        debug_to_file("Unit field is missing from the product data");
    }
    
    debug_to_file("Product found: " . json_encode($product));
    
    echo json_encode($product);
    
} catch (Exception $e) {
    debug_to_file("Error: " . $e->getMessage());
    echo json_encode(null);
}

// Close connection
$conn->close();
debug_to_file("Connection closed");

// Make sure to exit after sending JSON response
exit;
?>
