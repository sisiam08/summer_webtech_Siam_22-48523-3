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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Category ID is required'
    ]);
    exit;
}

$categoryId = intval($_GET['id']);

// Get category by ID
$sql = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'error' => 'Category not found'
    ]);
    exit;
}

$category = $result->fetch_assoc();

// Return category data
echo json_encode($category);
?>
