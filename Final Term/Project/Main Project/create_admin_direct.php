<?php
// Direct admin account creation script
echo "Starting direct admin account creation...\n\n";

// Database configuration - MAKE SURE THESE MATCH YOUR CONFIGURATION
$db_host = '127.0.0.1';
$db_user = 'root';
$db_password = 'Siam@MySQL2025';
$db_name = 'grocery_store';
$db_port = 3306;

// Admin credentials
$admin_email = 'admin@grocerystore.com';
$admin_password = 'Admin@123';
$admin_name = 'System Administrator';

echo "Connecting to database...\n";
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    echo "Error: Unable to connect to MySQL. " . mysqli_connect_error() . "\n";
    exit;
}

echo "Connected to database successfully!\n";

// Create users table if it doesn't exist
echo "Checking if users table exists...\n";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");

if ($table_check === false) {
    echo "Error checking tables: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit;
}

if (mysqli_num_rows($table_check) == 0) {
    echo "Creating users table...\n";
    $create_table = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'shop_owner', 'delivery', 'customer') NOT NULL DEFAULT 'customer',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        echo "Error creating users table: " . mysqli_error($conn) . "\n";
        mysqli_close($conn);
        exit;
    }
    
    echo "Users table created successfully!\n";
} else {
    echo "Users table already exists.\n";
}

// Check if admin account already exists
echo "Checking if admin account exists...\n";
$admin_check = "SELECT id FROM users WHERE email = '$admin_email'";
$result = mysqli_query($conn, $admin_check);

if ($result === false) {
    echo "Error checking admin account: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo "Admin account already exists.\n";
    
    // Update password if needed
    echo "Updating admin password...\n";
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $update = "UPDATE users SET password = '$hashed_password', role = 'admin' WHERE email = '$admin_email'";
    
    if (mysqli_query($conn, $update)) {
        echo "Admin password updated successfully!\n";
    } else {
        echo "Error updating admin password: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Creating new admin account...\n";
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $create_admin = "INSERT INTO users (name, email, password, role, is_active) 
                     VALUES ('$admin_name', '$admin_email', '$hashed_password', 'admin', 1)";
    
    if (mysqli_query($conn, $create_admin)) {
        echo "Admin account created successfully!\n";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn) . "\n";
    }
}

echo "\nAdmin account information:\n";
echo "Email: $admin_email\n";
echo "Password: $admin_password\n";

mysqli_close($conn);
echo "\nScript completed.\n";
?>
