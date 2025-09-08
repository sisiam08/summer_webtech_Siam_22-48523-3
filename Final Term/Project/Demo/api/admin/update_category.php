<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
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

if (!isset($data['name']) || empty($data['name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category name is required'
    ]);
    exit;
}

// Sanitize inputs
$id = intval($data['id']);
$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

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

// Check if another category with the same name exists
$checkNameSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
$stmt = $conn->prepare($checkNameSql);
$stmt->bind_param('si', $name, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Another category with this name already exists'
    ]);
    exit;
}

// Update category
$sql = "UPDATE categories SET name = ?, description = ?, is_active = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssii', $name, $description, $isActive, $id);

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
?>
