<?php
// Include functions file
require_once 'functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif (emailExists($email)) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]);
        exit;
    }
    
    // Register user
    $userId = registerUser($name, $email, $password);
    
    if ($userId) {
        // Auto login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'customer';
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => 'index.html'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
