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
    echo json_encode(null);
    exit;
}

// Include required files
require_once '../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(null);
    exit;
}

$productId = $_GET['id'];

try {
    // Get product data
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(null);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Ensure unit is properly formatted
    if (isset($product['unit'])) {
        // Make sure unit is lowercase to match option values
        $product['unit'] = strtolower(trim($product['unit']));
        
        // Handle common unit variations
        $unitMappings = [
            'kilograms' => 'kg',
            'kilogram' => 'kg',
            'grams' => 'g',
            'gram' => 'g',
            'liters' => 'l',
            'liter' => 'l',
            'milliliters' => 'ml',
            'milliliter' => 'ml',
            'pieces' => 'piece',
            'pcs' => 'piece',
            'pc' => 'piece',
            'packets' => 'packet',
            'pkt' => 'packet',
            'bottles' => 'bottle',
            'btl' => 'bottle',
            'dozens' => 'dozen',
            'dz' => 'dozen'
        ];
        
        if (array_key_exists($product['unit'], $unitMappings)) {
            $product['unit'] = $unitMappings[$product['unit']];
        }
    }
    
    echo json_encode($product);
    
} catch (Exception $e) {
    echo json_encode(null);
}
?>
