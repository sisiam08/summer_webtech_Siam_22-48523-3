<?php
// Database connection
require_once 'config/database.php';

echo "Creating delivery request and management tables...\n";

// Create delivery_requests table
$sql = "CREATE TABLE IF NOT EXISTS delivery_requests (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    shop_id INT(11) NOT NULL,
    status ENUM('pending', 'assigned', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Delivery requests table created successfully\n";
} else {
    echo "Error creating delivery requests table: " . $conn->error . "\n";
}

// Create or update deliveries table to support multi-shop deliveries
$sql = "CREATE TABLE IF NOT EXISTS deliveries (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    delivery_person_id INT(11) NOT NULL,
    status ENUM('assigned', 'picked_up', 'delivered', 'failed', 'cancelled') NOT NULL DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    picked_up_at TIMESTAMP NULL DEFAULT NULL,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    estimated_delivery TIMESTAMP NULL DEFAULT NULL,
    delivery_notes TEXT,
    customer_signature VARCHAR(255),
    delivery_proof VARCHAR(255),
    customer_rating INT(1),
    customer_feedback TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_person_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Deliveries table created/updated successfully\n";
} else {
    echo "Error with deliveries table: " . $conn->error . "\n";
}

// Create delivery_items table for tracking which items are delivered together
$sql = "CREATE TABLE IF NOT EXISTS delivery_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT(11) NOT NULL,
    order_item_id INT(11) NOT NULL,
    status ENUM('pending', 'delivered', 'returned') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Delivery items table created successfully\n";
} else {
    echo "Error creating delivery items table: " . $conn->error . "\n";
}

echo "Delivery management system setup complete!\n";
?>
