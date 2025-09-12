<?php
// Script to remove postal_code and country columns from users table
require_once 'config/database.php';

echo "Updating users table structure...\n";

// Check if columns exist
$checkPostalCode = $conn->query("SHOW COLUMNS FROM users LIKE 'postal_code'");
$postalCodeExists = $checkPostalCode->num_rows > 0;

$checkCountry = $conn->query("SHOW COLUMNS FROM users LIKE 'country'");
$countryExists = $checkCountry->num_rows > 0;

if ($postalCodeExists || $countryExists) {
    // Build the ALTER TABLE statement
    $alterQuery = "ALTER TABLE users";
    
    if ($postalCodeExists) {
        $alterQuery .= " DROP COLUMN postal_code";
        if ($countryExists) {
            $alterQuery .= ",";
        }
    }
    
    if ($countryExists) {
        $alterQuery .= " DROP COLUMN country";
    }
    
    if ($conn->query($alterQuery)) {
        echo "Users table updated successfully. Removed unnecessary columns.\n";
    } else {
        echo "Error updating users table: " . $conn->error . "\n";
    }
} else {
    echo "The columns postal_code and country don't exist in the users table.\n";
}

echo "Database update completed.\n";
?>
