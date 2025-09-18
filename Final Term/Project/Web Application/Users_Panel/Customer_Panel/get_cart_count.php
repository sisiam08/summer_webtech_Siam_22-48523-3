<?php
// Get cart count API endpoint
session_start();

// Include functions
require_once __DIR__ . '/../../Includes/functions.php';

header('Content-Type: application/json');

try {
    // Get current cart count using the proper function
    $cartCount = calculateCartCount();
    
    echo json_encode([
        'success' => true,
        'count' => $cartCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error getting cart count',
        'count' => 0
    ]);
}
?>