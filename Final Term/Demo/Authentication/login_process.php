<?php
// Start output buffering to prevent any accidental output
ob_start();

// Include functions file
require_once __DIR__ . "/../Includes/functions.php";

// Clear any output that might have been generated
ob_clean();

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate form data
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If there are errors, return them
    if ($errors) {
        die(json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]));
    }
    
    // Attempt login
    if (loginUser($email, $password)) {
        // Get user data
        $user = getCurrentUser();
        
        // Determine redirect based on role
        $redirect = '';
        switch ($user['role']) {
            case 'admin':
                $redirect = '../Users_Panel/Admin_Panel/admin_index.php';
                break;
            case 'shop_owner':
                $redirect = '../Users_Panel/Shop_Owner_Panel/shop_owner_index.php';
                break;
            case 'delivery_man':
                $redirect = '../Users_Panel/Delivery_Panel/delivery_index.php';
                break;
            case 'customer':
            default:
                $redirect = '../Users_Panel/Customer_Panel/index.php';
                break;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } 
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
            
        ]);
    }
} 
else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
