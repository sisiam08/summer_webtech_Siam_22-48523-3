-- SQL file to add discounted_price column to products table

-- Add discounted_price column to products table
ALTER TABLE products 
ADD COLUMN discounted_price DECIMAL(10, 2) DEFAULT NULL AFTER price;

-- Add unit column to products table if it doesn't already exist
ALTER TABLE products 
ADD COLUMN unit VARCHAR(50) DEFAULT 'piece' AFTER stock;
