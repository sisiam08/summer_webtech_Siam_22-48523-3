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

try {
    // Get all categories - using actual column names
    $sql = "SELECT id, name, description, created_at FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => intval($row['id']),
                'name' => $row['name'],
                'description' => $row['description'],
                'is_active' => 1, // Default to active since no is_active column exists
                'created_at' => $row['created_at']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading categories: ' . $e->getMessage()
    ]);
}
?>