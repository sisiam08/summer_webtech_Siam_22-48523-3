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
if (!isset($_POST['shop_name']) || empty($_POST['shop_name'])) {
    echo json_encode(['error' => 'Shop name is required']);
    exit;
}

try {
    // Check if shop record already exists
    $stmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $stmt->execute([$shop_owner_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare shop data
    $shopData = [
        'name' => $_POST['shop_name'],
        'description' => $_POST['description'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'address' => $_POST['address'] ?? null,
        'city' => $_POST['city'] ?? null,
        'postal_code' => $_POST['postal_code'] ?? null,
        'country' => $_POST['country'] ?? null,
        'business_hours' => $_POST['business_hours'] ?? null,
        'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
        'owner_id' => $shop_owner_id
    ];
    
    if ($shop) {
        // Update existing shop
        $updateFields = [];
        $params = [];
        
        foreach ($shopData as $key => $value) {
            if ($key !== 'owner_id') {  // Skip owner_id
                $updateFields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        // Add shop_id to params
        $params[] = $shop['id'];
        
        $sql = "UPDATE shops SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        // Insert new shop
        $columns = implode(", ", array_keys($shopData));
        $placeholders = implode(", ", array_fill(0, count($shopData), "?"));
        
        $sql = "INSERT INTO shops ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($shopData));
    }
    
    // Return success message
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
