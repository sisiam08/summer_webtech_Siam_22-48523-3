<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

echo "Session ID: " . session_id() . "\n\n";
echo "SESSION Contents:\n";
var_export($_SESSION);
echo "\n\n";

echo "Cookie Information:\n";
var_export($_COOKIE);
echo "\n\n";

echo "Session Variables Existence Check:\n";
echo "user_id exists: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "\n";
echo "user_role exists: " . (isset($_SESSION['user_role']) ? 'Yes' : 'No') . "\n";
echo "user_role is shop_owner: " . (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner' ? 'Yes' : 'No') . "\n";
echo "shop_id exists: " . (isset($_SESSION['shop_id']) ? 'Yes' : 'No') . "\n";

echo "\n\nSession File Path: " . session_save_path() . "\n";
echo "Session Cookie Parameters:\n";
var_export(session_get_cookie_params());

// Let's try to check for auth
echo "\n\nAuth Check Result:\n";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo "Authentication Failed!";
} else {
    echo "Authentication Successful as Shop Owner!";
}
?>
