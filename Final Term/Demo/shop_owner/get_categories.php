<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    echo json_encode([]);
    exit;
}

// Include required files
require_once '../config/database.php';

try {
    // Get all categories
    $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
