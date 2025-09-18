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
if (!isset($data['id']) || !isset($data['name']) || empty($data['name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category ID and name are required'
    ]);
    exit;
}

// Sanitize inputs
$id = intval($data['id']);
$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';
// Remove is_active since column doesn't exist

try {
    // Check if category exists
    $checkSql = "SELECT id FROM categories WHERE id = ?";
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
    
    // Check if another category with same name already exists (excluding current category)
    $checkNameSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
    $stmt = $conn->prepare($checkNameSql);
    $stmt->bind_param('si', $name, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'A category with this name already exists'
        ]);
        exit;
    }
    
    // Update category - using only existing columns
    $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $name, $description, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update category: ' . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating category: ' . $e->getMessage()
    ]);
}
?>