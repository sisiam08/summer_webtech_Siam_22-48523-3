<?php
// Initialize session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'helpers.php';

// Logout the user
logoutUser();

// Set flash message
setFlashMessage('success', 'You have been logged out successfully');

// Redirect to home page
redirect('index.php');
?>
