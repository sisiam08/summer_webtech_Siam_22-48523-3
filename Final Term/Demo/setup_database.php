<?php
// Script to check if the database exists and set up the initial structure
require_once 'config/database.php';

echo "Checking database connection and structure...\n";

// Check if main tables exist
$requiredTables = ['users', 'products', 'categories'];
$existingTables = [];

$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $existingTables[] = $row[0];
}

$missingTables = array_diff($requiredTables, $existingTables);

if (!empty($missingTables)) {
    echo "Missing tables: " . implode(', ', $missingTables) . "\n";
    echo "Creating required tables...\n";
    
    // Create users table if not exists
    if (in_array('users', $missingTables)) {
        $createUsers = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(15),
            password VARCHAR(255) NOT NULL,
            address VARCHAR(255),
            city VARCHAR(100),
            role ENUM('admin', 'customer', 'shop_owner', 'delivery_man') NOT NULL DEFAULT 'customer',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($createUsers)) {
            echo "Users table created successfully.\n";
            
            // Create admin user
            $adminName = "Admin User";
            $adminEmail = "admin@example.com";
            $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
            $adminRole = "admin";
            $adminPhone = "1234567890";

            $insertAdmin = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertAdmin);
            $stmt->bind_param("sssss", $adminName, $adminEmail, $adminPhone, $adminPassword, $adminRole);

            if ($stmt->execute()) {
                echo "Admin user created (Email: admin@example.com, Password: admin123)\n";
            } else {
                echo "Error creating admin user: " . $stmt->error . "\n";
            }
        } else {
            echo "Error creating users table: " . $conn->error . "\n";
        }
    }
    
    // Create categories table if not exists
    if (in_array('categories', $missingTables)) {
        $createCategories = "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($createCategories)) {
            echo "Categories table created successfully.\n";
            
            // Insert default categories
            $defaultCategories = [
                ["Fruits", "Fresh fruits from local and international markets"],
                ["Vegetables", "Fresh vegetables sourced from local farms"],
                ["Bakery", "Freshly baked bread, cakes, and pastries"],
                ["Meat & Poultry", "Fresh meat and poultry products"],
                ["Dairy & Eggs", "Milk, cheese, yogurt, and eggs"],
                ["Beverages", "Soft drinks, juices, coffee, and tea"]
            ];
            
            $insertCategory = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($insertCategory);
            
            foreach ($defaultCategories as $category) {
                $stmt->bind_param("ss", $category[0], $category[1]);
                $stmt->execute();
            }
            
            echo "Default categories added.\n";
        } else {
            echo "Error creating categories table: " . $conn->error . "\n";
        }
    }
    
    // Create products table if not exists
    if (in_array('products', $missingTables)) {
        $createProducts = "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category_id INT,
            image VARCHAR(255),
            stock INT DEFAULT 100,
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )";
        
        if ($conn->query($createProducts)) {
            echo "Products table created successfully.\n";
        } else {
            echo "Error creating products table: " . $conn->error . "\n";
        }
    }
} else {
    echo "All required tables exist.\n";
}

echo "\nDatabase setup completed.\n";
