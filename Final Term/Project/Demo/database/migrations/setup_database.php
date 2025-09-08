<?php
// Database setup script
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Setup</h1>";

// Database configuration
$db_host = '127.0.0.1';
$db_user = 'root';
$db_password = 'Siam@MySQL2025';  // Use the same password you used in database_connection.php
$db_name = 'grocery_store';
$db_port = 3306;

// Create connection without database selection
try {
    echo "<p>Connecting to MySQL server...</p>";
    $conn = mysqli_connect($db_host, $db_user, $db_password, '', $db_port);

    // Check connection
    if (!$conn) {
        die("<p style='color:red'>Connection failed: " . mysqli_connect_error() . "</p>");
    }
    
    echo "<p style='color:green'>Connected to MySQL server successfully!</p>";

    // Create database if it doesn't exist
    echo "<p>Creating database '$db_name' if it doesn't exist...</p>";
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green'>Database created successfully or already exists</p>";
    } else {
        die("<p style='color:red'>Error creating database: " . mysqli_error($conn) . "</p>");
    }

    // Select the database
    echo "<p>Selecting database '$db_name'...</p>";
    if (!mysqli_select_db($conn, $db_name)) {
        die("<p style='color:red'>Error selecting database: " . mysqli_error($conn) . "</p>");
    }
    
    echo "<p style='color:green'>Database selected successfully!</p>";
} catch (Exception $e) {
    die("<p style='color:red'>Exception: " . $e->getMessage() . "</p>");
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'users' created successfully or already exists<br>";
} else {
    echo "Error creating table 'users': " . mysqli_error($conn) . "<br>";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'categories' created successfully or already exists<br>";
} else {
    echo "Error creating table 'categories': " . mysqli_error($conn) . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.jpg',
    category_id INT,
    stock INT NOT NULL DEFAULT 0,
    featured BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'products' created successfully or already exists<br>";
} else {
    echo "Error creating table 'products': " . mysqli_error($conn) . "<br>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'orders' created successfully or already exists<br>";
} else {
    echo "Error creating table 'orders': " . mysqli_error($conn) . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'order_items' created successfully or already exists<br>";
} else {
    echo "Error creating table 'order_items': " . mysqli_error($conn) . "<br>";
}

// Insert default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, role) 
        SELECT 'Admin User', 'admin@example.com', '$admin_password', 'admin'
        FROM dual
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com' LIMIT 1)";

if (mysqli_query($conn, $sql)) {
    echo "Default admin user created successfully or already exists<br>";
} else {
    echo "Error creating default admin user: " . mysqli_error($conn) . "<br>";
}

// Insert sample categories
$categories = [
    ['Fruits', 'Fresh fruits from local farms'],
    ['Vegetables', 'Fresh vegetables from local farms'],
    ['Dairy', 'Milk, cheese, and other dairy products'],
    ['Bakery', 'Fresh bread and bakery items'],
    ['Beverages', 'Soft drinks, juices, and other beverages']
];

foreach ($categories as $category) {
    $name = mysqli_real_escape_string($conn, $category[0]);
    $description = mysqli_real_escape_string($conn, $category[1]);
    
    $sql = "INSERT INTO categories (name, description) 
            SELECT '$name', '$description'
            FROM dual
            WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = '$name' LIMIT 1)";
    
    if (mysqli_query($conn, $sql)) {
        echo "Category '$name' created successfully or already exists<br>";
    } else {
        echo "Error creating category '$name': " . mysqli_error($conn) . "<br>";
    }
}

// Insert sample products
$products = [
    ['Apple', 'Fresh red apples', 1.99, 'images/apple.jpg', 1, 100, 1],
    ['Banana', 'Fresh yellow bananas', 0.99, 'images/banana.jpg', 1, 150, 1],
    ['Orange', 'Fresh juicy oranges', 1.49, 'images/orange.jpg', 1, 120, 0],
    ['Carrot', 'Fresh orange carrots', 1.29, 'images/carrot.jpg', 2, 80, 0],
    ['Potato', 'Fresh brown potatoes', 0.89, 'images/potato.jpg', 2, 200, 1],
    ['Milk', 'Fresh cow milk', 2.49, 'images/milk.jpg', 3, 50, 1],
    ['Cheese', 'Cheddar cheese', 3.99, 'images/cheese.jpg', 3, 40, 0],
    ['Bread', 'Freshly baked bread', 2.29, 'images/bread.jpg', 4, 30, 0],
    ['Cookies', 'Chocolate chip cookies', 3.49, 'images/cookies.jpg', 4, 60, 0],
    ['Cola', 'Refreshing cola', 1.79, 'images/cola.jpg', 5, 100, 0]
];

foreach ($products as $product) {
    $name = mysqli_real_escape_string($conn, $product[0]);
    $description = mysqli_real_escape_string($conn, $product[1]);
    $price = $product[2];
    $image = mysqli_real_escape_string($conn, $product[3]);
    $category_id = $product[4];
    $stock = $product[5];
    $featured = $product[6];
    
    $sql = "INSERT INTO products (name, description, price, image, category_id, stock, featured) 
            SELECT '$name', '$description', $price, '$image', $category_id, $stock, $featured
            FROM dual
            WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = '$name' LIMIT 1)";
    
    if (mysqli_query($conn, $sql)) {
        echo "Product '$name' created successfully or already exists<br>";
    } else {
        echo "Error creating product '$name': " . mysqli_error($conn) . "<br>";
    }
}

// Close connection
mysqli_close($conn);

echo "<br>Database setup completed successfully!";
?>
