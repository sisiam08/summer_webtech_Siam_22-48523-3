<?php
// Create cart table script
require_once 'config/database.php';

// Create connection using the connectDB function
$conn = connectDB();

try {
    // Check if the table already exists
    $tableExistsQuery = "SHOW TABLES LIKE 'cart'";
    $result = $conn->query($tableExistsQuery);
    
    // PDO returns a PDOStatement object, rowCount gives us the number of rows
    if ($result->rowCount() == 0) {
        // Create cart table if it doesn't exist
        $createTableSQL = "CREATE TABLE `cart` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `quantity` INT(11) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($createTableSQL);
        echo "Cart table created successfully";
    } else {
        echo "Cart table already exists";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// PDO connection doesn't need explicit close
echo "<br>You can go back to your application now.";
?>
