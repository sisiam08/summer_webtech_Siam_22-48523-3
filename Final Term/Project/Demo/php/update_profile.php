<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get form data
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate form data
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

// If changing password
if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    }
    
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
}

// If there are errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $conn = connectDB();
    
    // Get current user data
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Verify current password if changing password
    if (!empty($currentPassword)) {
        if (!password_verify($currentPassword, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
    }
    
    // Start with basic update
    $query = "UPDATE users SET name = :name, phone = :phone";
    $params = [
        ':name' => $name,
        ':phone' => $phone,
        ':user_id' => $userId
    ];
    
    // Add password update if necessary
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query .= ", password = :password";
        $params[':password'] = $hashedPassword;
    }
    
    $query .= " WHERE id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Get updated user data
    $query = "SELECT id, name, email, phone FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully', 
        'user' => $updatedUser
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
