<?php
// Include functions file
require_once '../php/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log session data
error_log("Session data: " . json_encode($_SESSION));

// Check if user is logged in and is an admin
if (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Get user data
    $user = getCurrentUser();
    
    echo json_encode([
        'authenticated' => true,
        'role' => $_SESSION['user_role'],
        'name' => $user ? $user['name'] : 'Admin',
        'id' => $_SESSION['user_id']
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'role' => $_SESSION['user_role'] ?? 'none',
        'session_data' => $_SESSION
    ]);
}
?>
