<?php
// Include functions file
require_once __DIR__ . "/../Includes/functions.php";

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
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

    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
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
    $userId = registerUser($name, $email, $phone, $password);
    
    if ($userId) {
        // Auto login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'customer';
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => '../Users_Panel/Customer_Panel/index.php'
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
