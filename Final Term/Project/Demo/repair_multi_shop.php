<?php
// Script to check all tables and run repair if needed

require_once 'config/database.php';

echo "Checking all tables for issues...\n";

// Check for users table
$result = $conn->query("DESCRIBE users");
if (!$result) {
    echo "Creating users table...\n";
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        role ENUM('admin', 'customer', 'delivery_man', 'shop_owner') NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "Users table created.\n";
        
        // Create default admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role) VALUES 
                ('Admin User', 'admin@example.com', '$adminPassword', 'admin')";
        $conn->query($sql);
        echo "Default admin user created.\n";
    } else {
        echo "Error creating users table: " . $conn->error . "\n";
    }
} else {
    echo "Users table exists.\n";
}

// Check for shops table
$result = $conn->query("DESCRIBE shops");
if (!$result) {
    echo "Creating shops table...\n";
    $sql = "CREATE TABLE shops (
        id INT AUTO_INCREMENT PRIMARY KEY,
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
    if ($conn->query($sql)) {
        echo "Shops table created.\n";
    } else {
        echo "Error creating shops table: " . $conn->error . "\n";
    }
} else {
    echo "Shops table exists.\n";
}

// Check for products table
$result = $conn->query("DESCRIBE products");
if (!$result) {
    echo "Creating products table...\n";
    $sql = "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shop_id INT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50),
        image VARCHAR(255) DEFAULT 'images/products/default.jpg',
        stock_quantity INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shop_id) REFERENCES shops(id)
    )";
    if ($conn->query($sql)) {
        echo "Products table created.\n";
    } else {
        echo "Error creating products table: " . $conn->error . "\n";
    }
} else {
    echo "Products table exists.\n";
    
    // Check if products table has shop_id column
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'shop_id'");
    if ($result->num_rows == 0) {
        echo "Adding shop_id column to products table...\n";
        
        // Create a default shop if needed
        $result = $conn->query("SELECT id FROM shops LIMIT 1");
        if ($result->num_rows == 0) {
            // Create a default shop
            $adminId = 1; // Assuming the admin user has ID 1
            $sql = "INSERT INTO shops (name, owner_id, description) 
                    VALUES ('Default Shop', $adminId, 'Default shop for existing products')";
            if ($conn->query($sql)) {
                $defaultShopId = $conn->insert_id;
                echo "Default shop created with ID: $defaultShopId\n";
            } else {
                echo "Error creating default shop: " . $conn->error . "\n";
                exit;
            }
        } else {
            $row = $result->fetch_assoc();
            $defaultShopId = $row['id'];
        }
        
        // Add shop_id column
        $sql = "ALTER TABLE products ADD COLUMN shop_id INT AFTER id, 
                ADD FOREIGN KEY (shop_id) REFERENCES shops(id)";
        if ($conn->query($sql)) {
            echo "Added shop_id column to products table.\n";
            
            // Update existing products to use the default shop
            $sql = "UPDATE products SET shop_id = $defaultShopId WHERE shop_id IS NULL";
            $conn->query($sql);
            echo "Updated existing products to use default shop.\n";
        } else {
            echo "Error adding shop_id column: " . $conn->error . "\n";
        }
    } else {
        echo "Products table already has shop_id column.\n";
    }
}

// Create a sample shop owner and shop if none exist
$result = $conn->query("SELECT COUNT(*) as count FROM shops");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    echo "No shops found. Creating a sample shop...\n";
    
    // Create shop owner
    $ownerPassword = password_hash('password123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, role) 
            VALUES ('Fresh Farms Owner', 'freshfarms@example.com', '$ownerPassword', 'shop_owner')";
    if ($conn->query($sql)) {
        $ownerId = $conn->insert_id;
        echo "Sample shop owner created with ID: $ownerId\n";
        
        // Create shop
        $sql = "INSERT INTO shops (name, owner_id, description, delivery_charge) 
                VALUES ('Fresh Farms', $ownerId, 'Fresh fruits and vegetables directly from local farms', 5.00)";
        if ($conn->query($sql)) {
            echo "Sample shop created: Fresh Farms\n";
        } else {
            echo "Error creating sample shop: " . $conn->error . "\n";
        }
    } else {
        echo "Error creating sample shop owner: " . $conn->error . "\n";
    }
} else {
    echo "Found " . $row['count'] . " shops in the database.\n";
}

echo "\nDatabase check and repair completed.\n";
echo "If multi-shop functionality is still not enabled, run the following commands:\n";
echo "1. php database/update_db_for_multi_shop.php\n";
echo "2. php database/create_sample_shops.php\n";
?>
