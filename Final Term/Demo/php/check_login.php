<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Return login status
echo json_encode(['logged_in' => $isLoggedIn]);
?>
