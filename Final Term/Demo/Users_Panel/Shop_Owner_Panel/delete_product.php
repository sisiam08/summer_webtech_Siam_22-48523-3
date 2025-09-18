<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get input data - handle both JSON and form data
$inputData = json_decode(file_get_contents('php://input'), true);

// If JSON parsing failed, try to use $_POST
if (json_last_error() !== JSON_ERROR_NONE) {
    $inputData = $_POST;
}

// Log the request for debugging
error_log("Delete product request: " . json_encode($inputData));

// Check if product ID is provided
if (!isset($inputData['id']) || !is_numeric($inputData['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$productId = (int)$inputData['id'];
error_log("Attempting to delete product with ID: $productId");

// Include database connection
require_once __DIR__ . '/../../Database/database.php';

try {
    // Get the shop ID from the session (fallback to user_id if shop_id is not set)
    $shopId = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
    
    // Log shop ID for debugging
    error_log("Delete Product: Using shop_id = $shopId for user_id = " . $_SESSION['user_id']);
    
    // Check if the product exists and belongs to this shop owner
    $checkQuery = "SELECT * FROM products WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Product doesn't exist
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Product exists, get its data
    $productData = $result->fetch_assoc();
    
    // For debugging purposes, log what we found
    error_log("Found product with ID $productId. Its shop_id is: " . $productData['shop_id']);
    
    // Start a transaction
    $conn->begin_transaction();
    
    
    // Delete the product - we're only checking by ID now
    $deleteQuery = "DELETE FROM products WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $productId);
    $deleteStmt->execute();
    
    if ($deleteStmt->affected_rows > 0) {
        // Commit the transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } else {
        // Rollback the transaction
        $conn->rollback();
        
        // No rows were affected, which shouldn't happen at this point
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
} catch (Exception $e) {
    // Rollback the transaction if there was an error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    // Log the error
    error_log("Error in delete_product.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
}
?>
