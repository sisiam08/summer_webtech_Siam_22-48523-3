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
    // Build query
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
    ";
    
    // Add search filter if provided
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchFilter = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $searchFilter = " WHERE p.name LIKE ? OR c.name LIKE ? ";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    
    $query .= $searchFilter . " ORDER BY p.id DESC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all products
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
