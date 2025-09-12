<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '../../../Database/database.php';
require_once __DIR__ . '../../../Includes/functions.php';

// Set header to return JSON
// header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['isAuthenticated' => false]);
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Check if role is customer
    if ($role === 'customer') {
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'isAuthenticated' => true,
                'role' => $role,
                'name' => $user['name'],
                'email' => $user['email']
            ]);
        } else {
            echo json_encode(['isAuthenticated' => false]);
        }
    } else {
        echo json_encode([
            'isAuthenticated' => true,
            'role' => $role
        ]);
    }
} catch (PDOException $e) {
    // Log the error and return an error message
    // error_log('Database error: ' . $e->getMessage());
    echo json_encode(['isAuthenticated' => false, 'error' => 'Database error occurred']);
}
?>
