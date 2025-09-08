-- SQL file to update database schema for multi-shop functionality

-- Add shop_id to products table if it doesn't exist
ALTER TABLE products 
ADD COLUMN shop_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (shop_id) REFERENCES shops(id);

-- Create shops table if it doesn't exist
CREATE TABLE IF NOT EXISTS shops (
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
);

-- Update orders table to handle multi-shop orders
ALTER TABLE orders
ADD COLUMN total_delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount;

-- Create shop_orders table to track individual shop orders within a main order
CREATE TABLE IF NOT EXISTS shop_orders (
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
);

-- Update order_items to reference shop_orders
ALTER TABLE order_items
ADD COLUMN shop_order_id INT AFTER order_id,
ADD FOREIGN KEY (shop_order_id) REFERENCES shop_orders(id);
