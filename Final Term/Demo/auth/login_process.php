<?php
// Include functions file
require_once 'functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
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
    
    // Attempt login
    if (loginUser($email, $password)) {
        // Get user data
        $user = getCurrentUser();
        
        // Determine redirect based on role
        $redirect = 'index.html';
        if ($user['role'] === 'admin') {
            $redirect = 'admin/index.html';
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect
        ]);
    } else {
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
