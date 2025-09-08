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
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include database connection
require_once '../config/database.php';

try {
    $userId = $_SESSION['user_id'];
    
    // Get shop information
    // First check if the shops table exists and what columns it has
    $tableQuery = "SHOW TABLES LIKE 'shops'";
    $tableResult = $conn->query($tableQuery);
    
    if ($tableResult->num_rows === 0) {
        // Shops table doesn't exist, create it
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS shops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                address TEXT,
                phone VARCHAR(50),
                email VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES users(id)
            )
        ";
        $conn->query($createTableQuery);
    } else {
        // Check columns in shops table
        $columnsQuery = "SHOW COLUMNS FROM shops";
        $columnsResult = $conn->query($columnsQuery);
        $columns = [];
        while ($column = $columnsResult->fetch_assoc()) {
            $columns[] = $column['Field'];
        }
        
        // Determine which owner ID column to use
        $ownerIdColumn = in_array('shop_owner_id', $columns) ? 'shop_owner_id' : 'owner_id';
    }
    
    // Now query the shops table with the correct column
    $ownerIdColumn = isset($ownerIdColumn) ? $ownerIdColumn : 'owner_id';
    
    $shopQuery = "
        SELECT * FROM shops 
        WHERE $ownerIdColumn = ?
    ";
    $shopStmt = $conn->prepare($shopQuery);
    $shopStmt->bind_param('i', $userId);
    $shopStmt->execute();
    $shopResult = $shopStmt->get_result();
    
    // Check users table structure to determine column names
    $userColumnsQuery = "SHOW COLUMNS FROM users";
    $userColumnsResult = $conn->query($userColumnsQuery);
    $userColumns = [];
    while ($column = $userColumnsResult->fetch_assoc()) {
        $userColumns[] = $column['Field'];
    }
    
    // Determine which name column to use (username, name, or first_name)
    $nameColumn = 'name';
    if (in_array('username', $userColumns)) {
        $nameColumn = 'username';
    } elseif (in_array('first_name', $userColumns)) {
        $nameColumn = 'first_name';
    }
    
    // Log user table structure for debugging
    error_log("User table columns: " . implode(', ', $userColumns));
    error_log("Using name column: $nameColumn");
    
    // Get user information
    $userQuery = "
        SELECT id, $nameColumn as name, email 
        FROM users 
        WHERE id = ?
    ";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    // Log for debugging
    error_log("Profile fetch: User ID = $userId, Owner column = $ownerIdColumn");
    
    $userData = $userResult->fetch_assoc();
    
    // If shop exists, get its data, otherwise create default values
    if ($shopResult->num_rows > 0) {
        $shopData = $shopResult->fetch_assoc();
    } else {
        // No shop record yet, create default values
        $shopData = [
            'name' => $userData['name'] . "'s Shop",
            'description' => '',
            'address' => '',
            'phone' => '',
            'email' => $userData['email']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'shop' => $shopData,
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
