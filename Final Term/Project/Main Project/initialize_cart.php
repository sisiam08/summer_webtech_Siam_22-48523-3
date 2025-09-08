<?php
// Initialize cart system
session_start();
require_once 'config/database.php';
require_once 'includes/cart_helpers.php';

// Connect to database
$conn = connectDB();

// Create cart table if it doesn't exist
$success = ensureCartTableExists($conn);

if ($success) {
    echo "<div style='padding: 20px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; border-radius: 4px;'>
            <h3>Cart system initialized successfully!</h3>
            <p>The cart table has been created and is ready to use.</p>
            <p><a href='index.php'>Return to homepage</a></p>
          </div>";
} else {
    echo "<div style='padding: 20px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
            <h3>Error initializing cart system</h3>
            <p>There was a problem creating the cart table. Please check your database connection and permissions.</p>
            <p><a href='index.php'>Return to homepage</a></p>
          </div>";
}
?>
