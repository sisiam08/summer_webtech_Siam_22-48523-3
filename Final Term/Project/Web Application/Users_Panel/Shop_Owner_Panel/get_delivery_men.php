<?php
session_start();
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access - not logged in as shop owner']);
    exit();
}

try {
    $conn = connectDB();
    
    // Get shop ID for the current shop owner (same logic as get_orders.php)
    $stmt = $conn->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode(['error' => 'No shop found for this account']);
        exit();
    }
    
    // Get shop owner's city from users table
    $stmt = $conn->prepare("SELECT city FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop_owner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop_owner || !$shop_owner['city']) {
        echo json_encode(['error' => 'Shop owner city not found']);
        exit();
    }
    
    $city = $shop_owner['city'];
    
    // Get delivery men from the same city (from users table)
    $stmt = $conn->prepare("SELECT id, name, email, phone, city 
                           FROM users 
                           WHERE city = ? AND role = 'delivery_man'");
    $stmt->execute([$city]);
    $delivery_men = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'city' => $city,
        'delivery_men' => $delivery_men
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_delivery_men.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in get_delivery_men.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>