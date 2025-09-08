<?php
// Script to update the users table structure
require_once 'config/database.php';

echo "Updating users table structure...\n";

// Add missing columns to users table
$alterUserTable = "ALTER TABLE users 
                   ADD COLUMN phone VARCHAR(15) AFTER email,
                   ADD COLUMN address VARCHAR(255) AFTER password,
                   ADD COLUMN city VARCHAR(100) AFTER address,
                   ADD COLUMN postal_code VARCHAR(20) AFTER city,
                   ADD COLUMN country VARCHAR(100) AFTER postal_code,
                   MODIFY COLUMN role ENUM('admin', 'customer', 'shop_owner', 'delivery_man') NOT NULL DEFAULT 'customer',
                   ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER role";

if ($conn->query($alterUserTable)) {
    echo "Users table updated successfully with new columns.\n";
} else {
    echo "Error updating users table: " . $conn->error . "\n";
}

echo "Database update completed.\n";
?>
