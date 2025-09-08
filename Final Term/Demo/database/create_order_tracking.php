<?php
// Database connection
require_once 'config/database.php';

// Create order_tracking table
$sql = "CREATE TABLE IF NOT EXISTS order_tracking (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Order tracking table created successfully\n";
} else {
    echo "Error creating order tracking table: " . $conn->error . "\n";
}

// Add update_order_status function to automatically create tracking events
$sql = "CREATE OR REPLACE FUNCTION after_order_status_update() 
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO order_tracking (order_id, status, description, created_at)
    VALUES (NEW.id, NEW.status, 'Order status updated to ' || NEW.status, NOW());
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;";

if ($conn->query($sql) === TRUE) {
    echo "Function created successfully\n";
} else {
    echo "Error creating function: " . $conn->error . "\n";
    // If PostgreSQL function fails, let's create a similar one in PHP code
}

echo "Order tracking system setup complete!\n";
?>
