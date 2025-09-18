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
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$productId = (int)$_GET['id'];

try {
    $pdo = connectDB();
    
    // Get shop ID for the current user to ensure they can only edit their own products
    $shopId = getShopIdForOwner();
    if (!$shopId) {
        echo json_encode(['success' => false, 'message' => 'No shop found for this account']);
        exit;
    }
    
    // Get product data with category name
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.shop_id = ?
    ");
    
    $stmt->execute([$productId, $shopId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
        exit;
    }
    
    // Format the product data for the frontend
    $product['formatted_price'] = number_format($product['price'], 2);
    if (!empty($product['cost'])) {
        $product['formatted_cost'] = number_format($product['cost'], 2);
    }
    
    // Handle image path
    if (!empty($product['image'])) {
        $product['image_url'] = '../../Uploads/products/' . $product['image'];
    }
    
    // Ensure unit is properly formatted for the dropdown
    if (isset($product['unit'])) {
        $product['unit'] = strtolower(trim($product['unit']));
    }
    
    echo json_encode(['success' => true, 'product' => $product]);
    
} catch (Exception $e) {
    error_log("Error loading product: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading product data']);
}
?>
