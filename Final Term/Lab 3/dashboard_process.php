<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login_process.php");
    exit;
}

// Get cookies data
$cookie_username = isset($_COOKIE["user_login"]) ? $_COOKIE["user_login"] : "Not set";
$cookie_remember = isset($_COOKIE["user_remember"]) ? "Yes" : "No";
$cookie_created = isset($_COOKIE["cookie_created"]) ? $_COOKIE["cookie_created"] : "Not set";
$cookie_expiry = isset($_COOKIE["cookie_expiry"]) ? $_COOKIE["cookie_expiry"] : "Not set";

// Calculate session duration
$session_start = isset($_SESSION["session_created"]) ? $_SESSION["session_created"] : $_SESSION["login_time"];
$session_duration = time() - strtotime($session_start);
$session_duration_formatted = sprintf(
    "%02d:%02d:%02d", 
    ($session_duration/3600), 
    ($session_duration/60%60), 
    $session_duration%60
);

// Include the HTML template for dashboard
include 'dashboard_template.html';
?>
