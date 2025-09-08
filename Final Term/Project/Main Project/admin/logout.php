<?php
// Start session
session_start();

// Include admin authentication functions
require_once '../php/admin/admin_auth.php';

// Log out admin
logoutAdmin();

// Redirect to login page
header('Location: login.php');
exit;
?>
