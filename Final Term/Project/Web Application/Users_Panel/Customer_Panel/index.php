<?php
// Initialize session
session_start();

// Include required files
require '../../Database/database.php';
require '../../Includes/functions.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$isCustomer = $isLoggedIn && isCustomer();

if ($isLoggedIn && !$isCustomer) {
    // Redirect other user types to their respective dashboards
    $user = getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            redirect('../../Admin_Panel/index.php');
            break;
        case 'shop_owner':
            redirect('../../Shop_Owner_Panel/index.php');
            break;
        case 'delivery':
            redirect('../../Delivery_Panel/index.php');
            break;
    }
}

// Redirect to HTML file for display
header('Location: ../HTML/index.html');
exit();
?>