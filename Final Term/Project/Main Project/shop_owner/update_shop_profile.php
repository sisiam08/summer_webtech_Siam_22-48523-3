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

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $address = $input['address'] ?? '';
    $phone = $input['phone'] ?? '';
    $email = $input['email'] ?? '';
    
    // Check if shops table exists and its structure
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
        $ownerIdColumn = 'owner_id';
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
    
    // Check if shop exists
    $checkQuery = "SELECT id FROM shops WHERE $ownerIdColumn = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing shop
        $shopId = $result->fetch_assoc()['id'];
        
        $updateQuery = "
            UPDATE shops SET
                name = ?,
                description = ?,
                address = ?,
                phone = ?,
                email = ?
            WHERE id = ?
        ";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sssssi', $name, $description, $address, $phone, $email, $shopId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update shop profile: " . $conn->error);
        }
    } else {
        // Insert new shop
        $insertQuery = "
            INSERT INTO shops ($ownerIdColumn, name, description, address, phone, email)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('isssss', $userId, $name, $description, $address, $phone, $email);
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to create shop profile: " . $conn->error);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Shop profile updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
