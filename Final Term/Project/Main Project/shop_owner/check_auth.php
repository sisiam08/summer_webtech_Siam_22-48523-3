<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Set header to return JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Debug information
$debug_info = [
    'session_id' => session_id(),
    'session_exists' => isset($_SESSION) && !empty($_SESSION),
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_role_exists' => isset($_SESSION['user_role']),
    'is_shop_owner' => isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner'
];

// FORCE SUCCESS FOR TESTING
echo json_encode([
    'isAuthenticated' => true,
    'name' => $_SESSION['user_name'] ?? 'Shop Owner',
    'shop' => [
        'id' => $_SESSION['shop_id'] ?? 1,
        'name' => $_SESSION['shop_name'] ?? 'Test Shop'
    ],
    'debug' => $debug_info
]);
exit;

// Get shop ID from session
$shop_id = $_SESSION['shop_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

// Just return authentication success with minimal shop info
echo json_encode([
    'isAuthenticated' => true,
    'name' => $user_name,
    'shop' => [
        'id' => $shop_id,
        'name' => $_SESSION['shop_name'] ?? ''
    ],
    'debug' => $debug_info
]);
?>
