<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Not authenticated
    echo json_encode([]);
    exit;
}

// Include required files
require_once __DIR__ . '/../../Database/database.php';

try {
    // Get database connection
    $pdo = connectDB();
    
    // Create categories table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->query($createTableQuery);
    
    // Insert default categories if table is empty
    $countStmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $count = $countStmt->fetchColumn();
    
    if ($count == 0) {
        $defaultCategories = [
            'Fruits & Vegetables',
            'Dairy & Eggs',
            'Meat & Seafood', 
            'Bakery & Bread',
            'Pantry Staples',
            'Beverages',
            'Snacks & Sweets',
            'Frozen Foods',
            'Personal Care',
            'Household Items'
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        foreach ($defaultCategories as $category) {
            $insertStmt->execute([$category]);
        }
    }
    
    // Get all categories
    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading categories',
        'categories' => []
    ]);
}
?>
