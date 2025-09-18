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
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include database connection
include '../../Database/database.php';

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
    
    // Validate required fields
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Shop name is required'
        ]);
        exit;
    }
    
    // Get database connection
    $pdo = connectDB();
    
    // Create shops table if it doesn't exist
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
            INDEX(owner_id)
        )
    ";
    $pdo->query($createTableQuery);
    
    // Check if shop exists for this user
    $checkStmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $checkStmt->execute([$userId]);
    $existingShop = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingShop) {
        // Update existing shop
        $shopId = $existingShop['id'];
        
        $updateStmt = $pdo->prepare("
            UPDATE shops SET
                name = ?,
                description = ?,
                address = ?,
                phone = ?,
                email = ?
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$name, $description, $address, $phone, $email, $shopId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Shop profile updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update shop profile'
            ]);
        }
    } else {
        // Insert new shop
        $insertStmt = $pdo->prepare("
            INSERT INTO shops (owner_id, name, description, address, phone, email)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($insertStmt->execute([$userId, $name, $description, $address, $phone, $email])) {
            echo json_encode([
                'success' => true,
                'message' => 'Shop profile created successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create shop profile'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log('Shop Profile Update Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating profile. Please try again.'
    ]);
}
?>
