<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (isLoggedIn()) {
    $user = getCurrentUser();
    
    if ($user && $user['role'] === 'admin') {
        echo json_encode([
            'is_admin' => true,
            'name' => $user['name']
        ]);
        exit;
    }
}

// If not admin or not logged in
echo json_encode([
    'is_admin' => false
]);
?>
