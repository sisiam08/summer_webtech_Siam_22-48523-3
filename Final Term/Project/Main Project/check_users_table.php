<?php
// Script to check the current structure of the users table
require_once 'config/database.php';

echo "Checking current users table structure...\n";

$result = $conn->query("DESCRIBE users");

if ($result) {
    echo "Users table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error getting table structure: " . $conn->error . "\n";
}
?>
