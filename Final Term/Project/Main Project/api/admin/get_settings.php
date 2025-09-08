<?php
// First, let's create the settings table if it doesn't exist

// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if the settings table exists
$checkTable = "SHOW TABLES LIKE 'settings'";
$result = $conn->query($checkTable);

if ($result->num_rows == 0) {
    // Create settings table
    $createTable = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable) === TRUE) {
        // Insert default settings
        $defaultSettings = [
            // General Settings
            ['site_title', 'Online Grocery Store', 'general'],
            ['site_description', 'Your one-stop shop for fresh groceries.', 'general'],
            ['contact_email', 'contact@example.com', 'general'],
            ['contact_phone', '+1 (123) 456-7890', 'general'],
            ['store_address', '123 Main Street, City, State, 12345', 'general'],
            ['currency', 'USD', 'general'],
            ['timezone', 'UTC', 'general'],
            
            // Shipping Settings
            ['enable_shipping', '1', 'shipping'],
            ['shipping_method', 'flat_rate', 'shipping'],
            ['flat_rate_fee', '5.00', 'shipping'],
            ['free_shipping_min', '50.00', 'shipping'],
            ['shipping_countries', 'US,CA,MX,GB,FR,DE,JP,AU', 'shipping'],
            
            // Payment Settings
            ['payment_methods', 'cash,bank,card', 'payment'],
            ['currency_format', 'symbol_left', 'payment'],
            ['tax_rate', '7.5', 'payment'],
            ['enable_tax', '1', 'payment'],
            
            // Email Settings
            ['email_from', 'noreply@example.com', 'email'],
            ['email_from_name', 'Online Grocery Store', 'email'],
            ['email_method', 'mail', 'email'],
            ['smtp_host', 'smtp.example.com', 'email'],
            ['smtp_port', '587', 'email'],
            ['smtp_user', '', 'email'],
            ['smtp_pass', '', 'email'],
            ['smtp_encryption', 'tls', 'email'],
            ['notify_new_order', '1', 'email'],
            ['notify_order_status', '1', 'email'],
            ['notify_new_account', '1', 'email'],
            ['notify_low_stock', '1', 'email'],
            
            // Appearance Settings
            ['logo_path', 'img/logo.png', 'appearance'],
            ['favicon_path', 'img/favicon.ico', 'appearance'],
            ['primary_color', '#4CAF50', 'appearance'],
            ['secondary_color', '#2196F3', 'appearance'],
            ['featured_limit', '8', 'appearance'],
            ['products_per_page', '12', 'appearance']
        ];
        
        $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $insertStmt->bind_param('sss', $setting[0], $setting[1], $setting[2]);
            $insertStmt->execute();
        }
    }
}

// Get all settings grouped by type
$sql = "SELECT * FROM settings ORDER BY setting_type, setting_key";
$result = $conn->query($sql);

$settings = [];

while ($row = $result->fetch_assoc()) {
    $type = $row['setting_type'];
    $key = $row['setting_key'];
    $value = $row['setting_value'];
    
    if (!isset($settings[$type])) {
        $settings[$type] = [];
    }
    
    $settings[$type][$key] = $value;
}

// Return settings
echo json_encode($settings);
?>
