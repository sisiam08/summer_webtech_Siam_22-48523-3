<?php
// Include functions file
require "../Includes/functions.php";

// Set header to return JSON
header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate form data
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
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
                $redirect = '../Users_Panel/Admin_Panel/index.php';
                break;
            case 'shop_owner':
                $redirect = '../Users_Panel/Shop_Owner_Panel/index.php';
                break;
            case 'delivery':
                $redirect = '../Users_Panel/Delivery_Panel/index.php';
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
