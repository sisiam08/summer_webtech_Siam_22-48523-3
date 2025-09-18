<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

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
if (!isset($data['name']) || empty($data['name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category name is required'
    ]);
    exit;
}

// Sanitize inputs
$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';
// Remove is_active since column doesn't exist

// Check if category with same name already exists
$checkSql = "SELECT id FROM categories WHERE name = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('s', $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'A category with this name already exists'
    ]);
    exit;
}

// Insert new category - using only existing columns
$sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $name, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'id' => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add category: ' . $conn->error
    ]);
}
?>
