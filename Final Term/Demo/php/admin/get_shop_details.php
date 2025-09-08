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

// Check if shop ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'Shop ID is required'
    ]);
    exit;
}

$shopId = intval($_GET['id']);

try {
    // Get shop details with owner information
    $sql = "SELECT v.*, u.name as owner_name, u.email, u.phone 
            FROM vendors v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$shopId]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode([
            'error' => 'Shop not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'shop' => $shop
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
