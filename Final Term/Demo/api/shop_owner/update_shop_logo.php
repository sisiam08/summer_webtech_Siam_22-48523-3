<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Check if logo file is uploaded
if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No logo file uploaded or upload error']);
    exit;
}

// Define allowed file types and max file size
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Check file type
if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
    exit;
}

// Check file size
if ($_FILES['logo']['size'] > $maxFileSize) {
    echo json_encode(['error' => 'File is too large. Maximum size is 5MB']);
    exit;
}

try {
    // Create uploads directory if it doesn't exist
    $uploadDir = '../../uploads/shops/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $fileName = 'shop_' . $shop_owner_id . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
        // Check if shop record already exists
        $stmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
        $stmt->execute([$shop_owner_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop) {
            // Update existing shop
            $stmt = $pdo->prepare("UPDATE shops SET logo = ? WHERE id = ?");
            $stmt->execute([$fileName, $shop['id']]);
        } else {
            // Insert new shop with default name and logo
            $stmt = $pdo->prepare("
                INSERT INTO shops (name, logo, owner_id) 
                VALUES (CONCAT((SELECT first_name FROM users WHERE id = ?), '''s Shop'), ?, ?)
            ");
            $stmt->execute([$shop_owner_id, $fileName, $shop_owner_id]);
        }
        
        // Return success message
        echo json_encode(['success' => true, 'logo' => $fileName]);
    } else {
        echo json_encode(['error' => 'Failed to move uploaded file']);
    }
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
