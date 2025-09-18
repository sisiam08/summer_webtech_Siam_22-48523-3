<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get total number of orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get pending and processing orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
    $stmt->execute([$user_id]);
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get saved addresses count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM addresses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $savedAddresses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get wishlist items count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlistItems = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Return the dashboard data
    echo json_encode([
        'totalOrders' => $totalOrders,
        'pendingOrders' => $pendingOrders,
        'savedAddresses' => $savedAddresses,
        'wishlistItems' => $wishlistItems
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
