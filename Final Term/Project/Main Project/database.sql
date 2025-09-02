-- Database structure for Online Grocery Store

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS grocery_store;

-- Use the database
USE grocery_store;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(50),
    role ENUM('customer', 'shop_owner', 'delivery_man', 'admin') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_amount DECIMAL(10, 2) DEFAULT 0,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_email VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_postal_code VARCHAR(20) NOT NULL,
    shipping_country VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Fruits', 'Fresh fruits from local farms'),
('Vegetables', 'Fresh vegetables from local farms'),
('Dairy', 'Milk, cheese, and other dairy products'),
('Bakery', 'Fresh bread and pastries'),
('Meat', 'Fresh meat and poultry'),
('Beverages', 'Soft drinks, juices, and other beverages');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock, image, is_featured, is_active) VALUES
(1, 'Apple', 'Fresh red apples', 1.99, 100, 'apple.jpg', 1, 1),
(1, 'Banana', 'Fresh yellow bananas', 0.99, 150, 'banana.jpg', 1, 1),
(1, 'Orange', 'Fresh juicy oranges', 1.49, 120, 'orange.jpg', 0, 1),
(2, 'Carrot', 'Fresh orange carrots', 0.79, 80, 'carrot.jpg', 1, 1),
(2, 'Broccoli', 'Fresh green broccoli', 1.29, 70, 'broccoli.jpg', 0, 1),
(2, 'Tomato', 'Fresh red tomatoes', 0.99, 90, 'tomato.jpg', 1, 1),
(3, 'Milk', 'Fresh whole milk', 2.49, 50, 'milk.jpg', 1, 1),
(3, 'Cheese', 'Cheddar cheese', 3.99, 40, 'cheese.jpg', 0, 1),
(3, 'Yogurt', 'Plain yogurt', 1.99, 60, 'yogurt.jpg', 0, 1),
(4, 'Bread', 'Fresh white bread', 2.29, 30, 'bread.jpg', 1, 1),
(4, 'Croissant', 'Fresh butter croissant', 1.49, 25, 'croissant.jpg', 0, 1),
(4, 'Muffin', 'Blueberry muffin', 1.99, 35, 'muffin.jpg', 0, 1),
(5, 'Chicken', 'Fresh chicken breast', 5.99, 20, 'chicken.jpg', 1, 1),
(5, 'Beef', 'Fresh ground beef', 6.99, 15, 'beef.jpg', 0, 1),
(5, 'Pork', 'Fresh pork chops', 7.99, 10, 'pork.jpg', 0, 1),
(6, 'Water', 'Bottled water', 0.99, 200, 'water.jpg', 1, 1),
(6, 'Soda', 'Cola soda', 1.49, 150, 'soda.jpg', 0, 1),
(6, 'Juice', 'Orange juice', 2.99, 100, 'juice.jpg', 0, 1);

-- Insert a test admin user (password: admin123)
INSERT INTO users (name, email, password, is_admin) VALUES 
('Admin User', 'admin@example.com', '$2y$10$9b5nXWb3VqK6xDRtYT4/2OT9AHpFp1BoRMG5uuHVqxMo5nA8jlYBa', 1);

-- Insert a test regular user (password: user123)
INSERT INTO users (name, email, password, phone, address, city, postal_code, country) VALUES 
('Regular User', 'user@example.com', '$2y$10$8xkr7dTOZLK/CRCFHWv.m.XWQWNrZ2vr5trhVY.gQ22MjEHm8jOAu', '1234567890', '123 Main St', 'New York', '10001', 'USA');
