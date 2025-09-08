<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting admin setup process...\n\n";

// Admin setup script - RUN ONCE to create the admin account
// IMPORTANT: DELETE THIS FILE AFTER RUNNING IT FOR SECURITY

// Include database connection
require_once 'php/db_connection.php';

// Check if database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() . "\n");
} else {
    echo "Database connection successful!\n";
}

// Check if users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) == 0) {
    // Users table doesn't exist, create it
    echo "Users table does not exist. Creating table...\n";
    $create_table = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'shop_owner', 'delivery', 'customer') NOT NULL DEFAULT 'customer',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "Table 'users' created successfully!\n";
    } else {
        die("Error creating users table: " . mysqli_error($conn) . "\n");
    }
} else {
    echo "Table 'users' already exists.\n";
}

// Admin credentials - CHANGE THESE BEFORE RUNNING
$admin_email = 'admin@grocerystore.com';
$admin_password = 'Admin@123'; // Will be hashed before storing
$admin_name = 'System Administrator';

echo "Setting up admin user with email: $admin_email\n";

// Check if admin account already exists
$query = "SELECT id FROM users WHERE email = '$admin_email' AND role = 'admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "Admin account already exists. No changes made.\n";
    echo "You can use this account to log in to the admin panel.\n";
    echo "Email: $admin_email\n";
    echo "Password: Admin@123\n";
} else {
    // Hash the password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $query = "INSERT INTO users (name, email, password, role, is_active) 
              VALUES ('$admin_name', '$admin_email', '$hashed_password', 'admin', 1)";
    
    if (mysqli_query($conn, $query)) {
        echo "Admin account created successfully!\n";
        echo "Email: $admin_email\n";
        echo "Password: Admin@123\n\n";
        echo "IMPORTANT: Delete this file immediately for security reasons.\n";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
