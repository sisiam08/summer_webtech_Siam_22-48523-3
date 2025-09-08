<?php
// Script to create an admin user
require_once '../config/database.php';
require_once '../php/functions.php';

// Check if admin user exists
$sql = "SELECT * FROM users WHERE role = 'admin'";
$admin = fetchOne($sql);

if ($admin) {
    echo "Admin user already exists: " . $admin['email'] . "\n";
} else {
    // Admin user details
    $name = "Admin User";
    $email = "admin@example.com";
    $password = "admin123";
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashedPassword', 'admin')";
    
    if (executeQuery($sql)) {
        echo "Admin user created successfully!\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        echo "Failed to create admin user. Error: " . mysqli_error($conn) . "\n";
    }
}

// Make sure the users table has the role column
$checkRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if (mysqli_num_rows($checkRoleColumn) === 0) {
    echo "Adding 'role' column to users table...\n";
    
    // Add role column
    $sql = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'customer'";
    
    if (executeQuery($sql)) {
        echo "Role column added successfully!\n";
        
        // Update the admin user if it exists without a role
        $sql = "UPDATE users SET role = 'admin' WHERE email = 'admin@example.com'";
        executeQuery($sql);
    } else {
        echo "Failed to add role column. Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Role column already exists in users table.\n";
}

echo "\nDone!\n";
?>
