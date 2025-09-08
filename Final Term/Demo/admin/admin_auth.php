<?php
// Admin authentication helper
function requireAdminAuth() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
    
    // Check if user has admin role
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: login.html?error=nopermission');
        exit;
    }
    
    // All checks passed, user is authenticated admin
    return true;
}
?>
