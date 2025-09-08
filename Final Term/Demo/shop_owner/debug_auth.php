<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Set header for JSON response
header('Content-Type: application/json');

// Output current auth status
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$is_shop_owner = $is_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'auth_status' => [
        'is_logged_in' => $is_logged_in,
        'is_shop_owner' => $is_shop_owner,
        'session_data' => $_SESSION
    ],
    'cookie_data' => $_COOKIE,
    'server_info' => [
        'php_self' => $_SERVER['PHP_SELF'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]
];

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
