-- SQL to update products table structure
-- Run this SQL directly in your database administration tool (e.g., phpMyAdmin)

-- First check if needed columns exist, if not add them
-- Add shop_id column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS shop_id INT NOT NULL DEFAULT 1 AFTER id;

-- Add discounted_price column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS discounted_price DECIMAL(10, 2) DEFAULT NULL AFTER price;

-- Add unit column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT 'piece' AFTER stock;

-- Add is_active column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER image;

-- Add is_featured column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0 AFTER is_active;

-- Fix any NULL values in required columns
UPDATE products SET shop_id = 1 WHERE shop_id IS NULL;
UPDATE products SET is_active = 1 WHERE is_active IS NULL;
UPDATE products SET is_featured = 0 WHERE is_featured IS NULL;
UPDATE products SET unit = 'piece' WHERE unit IS NULL;

-- Remove redundant columns if they exist
-- Note: Enable these lines if you want to remove the featured column
-- UPDATE products SET is_featured = featured WHERE featured = 1 AND is_featured = 0;
-- ALTER TABLE products DROP COLUMN IF EXISTS featured;

-- Ensure all columns have proper defaults
ALTER TABLE products MODIFY COLUMN shop_id INT NOT NULL DEFAULT 1;
ALTER TABLE products MODIFY COLUMN is_active TINYINT(1) DEFAULT 1;
ALTER TABLE products MODIFY COLUMN is_featured TINYINT(1) DEFAULT 0;
ALTER TABLE products MODIFY COLUMN unit VARCHAR(50) DEFAULT 'piece';
