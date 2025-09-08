<?php
// Start session with SameSite=None for cross-domain functionality
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'On');
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Debug information
$debug = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'auth_check' => [
        'user_id_exists' => isset($_SESSION['user_id']),
        'user_role_exists' => isset($_SESSION['user_role']),
        'is_shop_owner' => isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner',
    ],
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
];

// For testing, we'll bypass authentication and always return success
$response = [
    'success' => true,
    'message' => 'Product saved successfully (TEST MODE)',
    'product_id' => rand(100, 999),
    'debug' => $debug
];

echo json_encode($response);
?>
