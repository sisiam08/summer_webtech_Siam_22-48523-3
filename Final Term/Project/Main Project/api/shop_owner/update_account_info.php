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

// Check if required fields are provided
if (
    !isset($_POST['first_name']) || empty($_POST['first_name']) ||
    !isset($_POST['last_name']) || empty($_POST['last_name']) ||
    !isset($_POST['email']) || empty($_POST['email'])
) {
    echo json_encode(['error' => 'First name, last name, and email are required']);
    exit;
}

// Validate email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    // Check if email is already in use by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$_POST['email'], $shop_owner_id]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo json_encode(['error' => 'Email is already in use by another user']);
        exit;
    }
    
    // Update user information
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'] ?? null,
        $shop_owner_id
    ]);
    
    // Return success message
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
