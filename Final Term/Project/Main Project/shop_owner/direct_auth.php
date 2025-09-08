<?php
// DIRECT API ENDPOINT TO TEST CHECK_AUTH REPLACEMENT
// This is a simple standalone version of check_auth.php
// Start session
session_start();

// Set header to return JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Force authentication success for testing
echo json_encode([
    'isAuthenticated' => true,
    'name' => $_SESSION['user_name'] ?? 'Test Shop Owner',
    'shop' => [
        'id' => $_SESSION['shop_id'] ?? 1,
        'name' => $_SESSION['shop_name'] ?? 'Test Shop'
    ],
    'debug' => [
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]
]);
?>
