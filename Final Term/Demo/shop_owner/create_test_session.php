<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Create a test session for shop owner role
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Shop Owner';
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_role'] = 'shop_owner';
$_SESSION['shop_id'] = 1;
$_SESSION['shop_name'] = 'Test Shop';

// Verify the session variables were set
$response = [
    'success' => true,
    'message' => 'Test session created successfully',
    'session_id' => session_id(),
    'session_data' => $_SESSION
];

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
