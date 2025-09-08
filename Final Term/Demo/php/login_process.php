<?php
// Start session first
session_start();

// Include functions file
require_once 'functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $isAdminLogin = isset($_POST['admin_login']) && $_POST['admin_login'] == '1';
    
    // Validate form data
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]);
        exit;
    }
    
    // Debug: Log login attempt
    error_log("Login attempt: $email, isAdminLogin: " . ($isAdminLogin ? 'Yes' : 'No'));
    
    // Attempt login
    if (loginUser($email, $password)) {
        // Get user data
        $user = getCurrentUser();
        
        // Debug: Log user data
        error_log("User logged in: " . ($user ? json_encode($user) : 'No user data'));
        error_log("Session data: " . json_encode($_SESSION));
        
        // For admin login, verify the user is actually an admin
        if ($isAdminLogin && (!isset($user['role']) || $user['role'] !== 'admin')) {
            error_log("Admin login attempt by non-admin user: $email");
            echo json_encode([
                'success' => false,
                'message' => 'You do not have administrator privileges.'
            ]);
            exit;
        }
        
        // Set a cookie to help maintain login state
        $token = bin2hex(random_bytes(32));
        setcookie('auth_token', $token, [
            'expires' => time() + 86400, // 24 hours
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // Store the token in the session
        $_SESSION['auth_token'] = $token;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user_role' => $user['role'] ?? 'unknown',
            'redirect' => $isAdminLogin ? 'admin/index.php' : 'index.php'
        ]);
    } else {
        error_log("Failed login attempt: $email");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
