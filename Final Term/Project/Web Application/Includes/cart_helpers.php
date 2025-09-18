<?php
/**
 * Helper function to ensure the cart table exists
 */
function ensureCartTableExists($conn) {
    try {
        // Check if cart table exists using a SELECT query that would fail if the table doesn't exist
        $checkTableSQL = "SELECT 1 FROM cart LIMIT 1";
        $conn->query($checkTableSQL);
        
        // If we get here, the table exists
        return true;
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        try {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `cart` (
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
            
            $conn->query($createTableSQL);
            return true;
        } catch (PDOException $e) {
            // Failed to create table
            error_log("Failed to create cart table: " . $e->getMessage());
            return false;
        }
    }
}
?>
