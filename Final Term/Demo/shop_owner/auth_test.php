<?php
// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Output debugging information
echo '<h1>Authentication Test</h1>';
echo '<h2>Session Information</h2>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

// Check authentication
if (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner') {
    echo '<h2>Authentication Status: <span style="color: green;">LOGGED IN</span></h2>';
    echo '<p>You are logged in as a shop owner.</p>';
    
    echo '<h2>Shop Information</h2>';
    if (isset($_SESSION['shop_id']) && isset($_SESSION['shop_name'])) {
        echo '<p>Shop ID: ' . $_SESSION['shop_id'] . '</p>';
        echo '<p>Shop Name: ' . $_SESSION['shop_name'] . '</p>';
    } else {
        echo '<p style="color: red;">Shop information is missing in session!</p>';
    }
} else {
    echo '<h2>Authentication Status: <span style="color: red;">NOT LOGGED IN</span></h2>';
    
    if (!isLoggedIn()) {
        echo '<p>You are not logged in. Session user_id is not set.</p>';
    } else if (!isset($_SESSION['user_role'])) {
        echo '<p>User role is not set in session.</p>';
    } else if ($_SESSION['user_role'] !== 'shop_owner') {
        echo '<p>You are logged in but not as a shop owner. Your role is: ' . $_SESSION['user_role'] . '</p>';
    }
}

// Add links
echo '<h2>Actions</h2>';
echo '<ul>';
echo '<li><a href="login.php">Go to Login Page</a></li>';
echo '<li><a href="index.html">Go to Dashboard</a></li>';
echo '<li><a href="logout.php">Logout</a></li>';
echo '<li><a href="debug.html">Go to Debug Tool</a></li>';
echo '</ul>';

// Add form to set session variables manually for testing
echo '<h2>Set Session Manually (For Testing Only)</h2>';
echo '<form method="post" action="set_test_session.php">';
echo '<p><label>User ID: <input type="text" name="user_id" value="1"></label></p>';
echo '<p><label>User Name: <input type="text" name="user_name" value="Test Shop Owner"></label></p>';
echo '<p><label>User Email: <input type="text" name="user_email" value="test@example.com"></label></p>';
echo '<p><label>User Role: <input type="text" name="user_role" value="shop_owner"></label></p>';
echo '<p><label>Shop ID: <input type="text" name="shop_id" value="1"></label></p>';
echo '<p><label>Shop Name: <input type="text" name="shop_name" value="Test Shop"></label></p>';
echo '<p><input type="submit" value="Set Test Session"></p>';
echo '</form>';
?>
