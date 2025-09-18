<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../../Database/database.php';
include '../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

try {
    // Get PDO connection
    $pdo = connectDB();
    
    // Get shop information with proper column names
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as user_name, u.email, u.phone
        FROM shops s
        JOIN users u ON s.owner_id = u.id
        WHERE s.owner_id = ?
    ");
    $stmt->execute([$shop_owner_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        // If no shop record exists yet, just get the user info
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$shop_owner_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'shop' => [
                'name' => $user['name'] . "'s Shop",
                'description' => '',
                'address' => '',
                'phone' => '',
                'email' => $user['email']
            ],
            'user' => [
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
    } else {
        // Return the shop and owner information
        echo json_encode([
            'success' => true,
            'shop' => [
                'id' => $data['id'],
                'name' => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? ''
            ],
            'user' => [
                'name' => $data['user_name'],
                'email' => $data['email']
            ]
        ]);
    }
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
