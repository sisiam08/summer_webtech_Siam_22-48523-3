<?php
// Script to apply database schema updates for multi-shop functionality
require_once '../config/database.php';

echo "Starting database schema update for multi-shop functionality...\n";

// Check if shops table exists
$checkShopsTable = $conn->query("SHOW TABLES LIKE 'shops'");
if ($checkShopsTable->num_rows == 0) {
    echo "Creating shops table...\n";
    
    $createShopsTable = "CREATE TABLE shops (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        owner_id INT NOT NULL,
        description TEXT,
        logo VARCHAR(255),
        delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 5.00,
        minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id)
    ) ENGINE=InnoDB";
    
    if ($conn->query($createShopsTable)) {
        echo "Shops table created successfully.\n";
    } else {
        echo "Error creating shops table: " . $conn->error . "\n";
        exit;
    }
}

// Check if products table has shop_id column
$checkShopIdColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'shop_id'");
if ($checkShopIdColumn->num_rows == 0) {
    echo "Adding shop_id column to products table...\n";
    
    // First create a default shop for existing products
    $createDefaultShop = "INSERT INTO shops (name, owner_id, description) VALUES ('Default Shop', 1, 'Default shop for existing products')";
    if ($conn->query($createDefaultShop)) {
        $defaultShopId = $conn->insert_id;
        echo "Default shop created with ID: $defaultShopId\n";
        
        // Add shop_id column to products
        $addShopIdColumn = "ALTER TABLE products ADD COLUMN shop_id INT NOT NULL DEFAULT $defaultShopId AFTER id";
        if ($conn->query($addShopIdColumn)) {
            echo "Added shop_id column to products table.\n";
            
            // Add foreign key constraint
            $addForeignKey = "ALTER TABLE products ADD FOREIGN KEY (shop_id) REFERENCES shops(id)";
            if ($conn->query($addForeignKey)) {
                echo "Added foreign key constraint to products table.\n";
            } else {
                echo "Error adding foreign key constraint: " . $conn->error . "\n";
            }
        } else {
            echo "Error adding shop_id column: " . $conn->error . "\n";
        }
    } else {
        echo "Error creating default shop: " . $conn->error . "\n";
    }
}

// Check if orders table has total_delivery_charge column
$checkDeliveryChargeColumn = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_delivery_charge'");
if ($checkDeliveryChargeColumn->num_rows == 0) {
    echo "Adding total_delivery_charge column to orders table...\n";
    
    $addDeliveryChargeColumn = "ALTER TABLE orders ADD COLUMN total_delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount";
    if ($conn->query($addDeliveryChargeColumn)) {
        echo "Added total_delivery_charge column to orders table.\n";
    } else {
        echo "Error adding total_delivery_charge column: " . $conn->error . "\n";
    }
}

// Check if shop_orders table exists
$checkShopOrdersTable = $conn->query("SHOW TABLES LIKE 'shop_orders'");
if ($checkShopOrdersTable->num_rows == 0) {
    echo "Creating shop_orders table...\n";
    
    $createShopOrdersTable = "CREATE TABLE shop_orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        shop_id INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        delivery_charge DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (shop_id) REFERENCES shops(id)
    )";
    
    if ($conn->query($createShopOrdersTable)) {
        echo "Shop orders table created successfully.\n";
    } else {
        echo "Error creating shop_orders table: " . $conn->error . "\n";
    }
}

// Check if order_items table has shop_order_id column
$checkShopOrderIdColumn = $conn->query("SHOW COLUMNS FROM order_items LIKE 'shop_order_id'");
if ($checkShopOrderIdColumn->num_rows == 0) {
    echo "Adding shop_order_id column to order_items table...\n";
    
    $addShopOrderIdColumn = "ALTER TABLE order_items ADD COLUMN shop_order_id INT AFTER order_id";
    if ($conn->query($addShopOrderIdColumn)) {
        echo "Added shop_order_id column to order_items table.\n";
        
        // Add foreign key constraint
        $addForeignKey = "ALTER TABLE order_items ADD FOREIGN KEY (shop_order_id) REFERENCES shop_orders(id)";
        if ($conn->query($addForeignKey)) {
            echo "Added foreign key constraint to order_items table.\n";
        } else {
            echo "Error adding foreign key constraint: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding shop_order_id column: " . $conn->error . "\n";
    }
}

echo "Database schema update for multi-shop functionality completed.\n";
