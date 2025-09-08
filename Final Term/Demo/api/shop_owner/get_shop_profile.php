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

try {
    // Get shop information
    $stmt = $pdo->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone
        FROM shops s
        JOIN users u ON s.owner_id = u.id
        WHERE s.owner_id = ?
    ");
    $stmt->execute([$shop_owner_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        // If no shop record exists yet, just get the user info
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$shop_owner_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'shop' => [
                'name' => $user['first_name'] . "'s Shop",
                'is_active' => true
            ],
            'owner' => $user
        ]);
    } else {
        // Return the shop and owner information
        echo json_encode([
            'shop' => [
                'id' => $data['id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'logo' => $data['logo'],
                'address' => $data['address'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
                'phone' => $data['phone'],
                'business_hours' => $data['business_hours'],
                'category_id' => $data['category_id'],
                'is_active' => $data['is_active'] == 1
            ],
            'owner' => [
                'id' => $shop_owner_id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone']
            ]
        ]);
    }
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
