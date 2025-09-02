<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get default address
    $stmt = $pdo->prepare("
        SELECT * FROM addresses 
        WHERE user_id = ? AND is_default = 1
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return the address data
    echo json_encode([
        'address' => $address
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
