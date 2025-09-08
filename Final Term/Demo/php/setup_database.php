<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config.php';

echo "<h1>Database Setup Script</h1>";
echo "<p>This script will create or update the necessary tables for the Online Grocery Store application.</p>";

// Connect to database
$conn = getDbConnection();
if (!$conn) {
    die("<p style='color:red;'>Database connection failed!</p>");
}

echo "<p style='color:green;'>Connected to database successfully.</p>";

// Function to run a query and display result
function runQuery($conn, $query, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$query</pre>";
    
    if ($conn->query($query) === TRUE) {
        echo "<p style='color:green;'>Success!</p>";
        return true;
    } else {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        return false;
    }
}

// Create users table if not exists
$usersTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user', 'vendor') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $usersTable, "Creating Users Table");

// Create user_tokens table for "remember me" functionality
$userTokensTable = "CREATE TABLE IF NOT EXISTS `user_tokens` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expiry` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $userTokensTable, "Creating User Tokens Table");

// Create admin_logs table for audit trails
$adminLogsTable = "CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $adminLogsTable, "Creating Admin Logs Table");

// Create categories table if not exists
$categoriesTable = "CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `parent_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $categoriesTable, "Creating Categories Table");

// Create products table if not exists
$productsTable = "CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `discount_price` DECIMAL(10,2) DEFAULT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `stock` INT(11) DEFAULT 0,
    `image` VARCHAR(255),
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $productsTable, "Creating Products Table");

// Create orders table if not exists
$ordersTable = "CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    `shipping_address` TEXT NOT NULL,
    `billing_address` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $ordersTable, "Creating Orders Table");

// Create order_items table if not exists
$orderItemsTable = "CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $orderItemsTable, "Creating Order Items Table");

// Create cart items table if not exists
$cartItemsTable = "CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

runQuery($conn, $cartItemsTable, "Creating Cart Items Table");

// Check if admin user exists
$result = $conn->query("SELECT * FROM users WHERE role = 'admin'");
if ($result->num_rows === 0) {
    // No admin user found, create one
    echo "<h3>Creating Default Admin User</h3>";
    
    // Generate a secure password hash
    $adminPassword = "admin123"; // Default password
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
    $adminName = "Admin User";
    $adminEmail = "admin@example.com";
    $stmt->bind_param("sss", $adminName, $adminEmail, $hashedPassword);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Default admin user created successfully!</p>";
        echo "<p>Email: admin@example.com</p>";
        echo "<p>Password: admin123</p>";
        echo "<p style='color:red;'>Please change this password after your first login!</p>";
    } else {
        echo "<p style='color:red;'>Error creating admin user: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
} else {
    echo "<h3>Admin User Check</h3>";
    echo "<p style='color:green;'>Admin user already exists. No need to create default admin.</p>";
}

// Check for sample data
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();
if ($row['count'] === '0') {
    echo "<h3>No products found. Would you like to create sample data?</h3>";
    echo "<form method='post'>";
    echo "<input type='submit' name='create_sample_data' value='Create Sample Data' style='padding: 10px; background-color: #4caf50; color: white; border: none; cursor: pointer;'>";
    echo "</form>";
}

// Handle sample data creation
if (isset($_POST['create_sample_data'])) {
    echo "<h3>Creating Sample Data</h3>";
    
    // Create sample categories
    $categories = [
        ['Fruits & Vegetables', 'Fresh produce including fruits and vegetables'],
        ['Dairy & Eggs', 'Milk, cheese, butter, yogurt and eggs'],
        ['Bakery', 'Fresh bread, pastries, and baked goods'],
        ['Meat & Seafood', 'Fresh and frozen meat and seafood'],
        ['Beverages', 'Drinks including water, juice, soda, and more']
    ];
    
    foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<p style='color:green;'>Sample categories created successfully!</p>";
    
    // Get category IDs
    $result = $conn->query("SELECT id, name FROM categories");
    $categoryIds = [];
    while ($row = $result->fetch_assoc()) {
        $categoryIds[$row['name']] = $row['id'];
    }
    
    // Create sample products
    $products = [
        ['Red Apple', 'Fresh and crisp red apples', 1.99, $categoryIds['Fruits & Vegetables'], 100, 'assets/images/products/apple.jpg'],
        ['Banana', 'Ripe yellow bananas', 0.99, $categoryIds['Fruits & Vegetables'], 150, 'assets/images/products/banana.jpg'],
        ['Milk', 'Fresh whole milk', 2.49, $categoryIds['Dairy & Eggs'], 50, 'assets/images/products/milk.jpg'],
        ['Eggs', 'Farm fresh eggs', 3.49, $categoryIds['Dairy & Eggs'], 40, 'assets/images/products/eggs.jpg'],
        ['Bread', 'Freshly baked white bread', 2.29, $categoryIds['Bakery'], 30, 'assets/images/products/bread.jpg'],
        ['Chicken', 'Fresh boneless chicken breast', 5.99, $categoryIds['Meat & Seafood'], 25, 'assets/images/products/chicken.jpg'],
        ['Water', 'Bottled mineral water', 0.99, $categoryIds['Beverages'], 200, 'assets/images/products/water.jpg'],
        ['Orange Juice', 'Fresh squeezed orange juice', 3.99, $categoryIds['Beverages'], 35, 'assets/images/products/orange-juice.jpg']
    ];
    
    foreach ($products as $product) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssdiis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<p style='color:green;'>Sample products created successfully!</p>";
}

// Close connection
$conn->close();

echo "<h2>Database Setup Completed</h2>";
echo "<p>The database has been set up successfully. You can now use the application.</p>";
echo "<p><a href='../index.php' style='color: #4caf50;'>Go to Home Page</a> | <a href='../admin/login.html' style='color: #4caf50;'>Go to Admin Login</a></p>";
?>
