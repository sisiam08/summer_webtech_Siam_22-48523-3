<?php
// Start session
session_start();

// Set session variables for testing
$_SESSION['user_id'] = $_POST['user_id'] ?? 1;
$_SESSION['user_name'] = $_POST['user_name'] ?? 'Test Shop Owner';
$_SESSION['user_email'] = $_POST['user_email'] ?? 'test@example.com';
$_SESSION['user_role'] = $_POST['user_role'] ?? 'shop_owner';
$_SESSION['shop_id'] = $_POST['shop_id'] ?? 1;
$_SESSION['shop_name'] = $_POST['shop_name'] ?? 'Test Shop';

// Ensure session is written
session_write_close();
session_start();

// Redirect back to the auth test page
header('Location: auth_test.php');
exit;
?>
