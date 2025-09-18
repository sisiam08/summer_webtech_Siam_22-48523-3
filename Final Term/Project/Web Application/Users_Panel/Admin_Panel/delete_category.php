<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category ID is required'
    ]);
    exit;
}

$id = intval($data['id']);

try {
    // Check if category exists
    $checkSql = "SELECT id, name FROM categories WHERE id = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category not found'
        ]);
        exit;
    }
    
    $category = $result->fetch_assoc();
    
    // Check if category is being used by any products
    $productCheckSql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
    $stmt = $conn->prepare($productCheckSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $productCount = $result->fetch_assoc()['product_count'];
    
    if ($productCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete category '{$category['name']}' because it is being used by {$productCount} product(s). Please move or delete those products first."
        ]);
        exit;
    }
    
    // Delete category
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Category '{$category['name']}' deleted successfully"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete category: ' . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting category: ' . $e->getMessage()
    ]);
}
?>