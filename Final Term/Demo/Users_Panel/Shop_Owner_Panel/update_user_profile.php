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
require_once __DIR__ . '/../../Database/database.php';

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
    $email = $input['email'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email)) {
        throw new Exception("Name and email are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
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
        // If using first_name, check if there's also a last_name
        $hasLastName = in_array('last_name', $userColumns);
    }
    
    // Log user table structure for debugging
    error_log("User table columns: " . implode(', ', $userColumns));
    error_log("Using name column: $nameColumn");
    
    // Check if email is already in use by another user
    $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('si', $email, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already in use by another account");
    }
    
    // Update user profile
    if ($nameColumn === 'first_name' && isset($hasLastName) && $hasLastName) {
        // If using first_name and last_name, split the name
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sssi', $firstName, $lastName, $email, $userId);
    } else {
        // Otherwise just update the single name column
        $updateQuery = "UPDATE users SET $nameColumn = ?, email = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ssi', $name, $email, $userId);
    }
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update account information: " . $conn->error);
    }
    
    // Update session with new name
    $_SESSION['user_name'] = $name;
    
    echo json_encode([
        'success' => true,
        'message' => 'Account information updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
