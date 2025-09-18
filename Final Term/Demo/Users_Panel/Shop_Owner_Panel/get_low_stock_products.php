<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Get shop ID for the logged-in shop owner
$shopId = getShopIdForOwner();
if (!$shopId) {
    echo json_encode(['error' => 'No shop found for this account']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get low stock products (less than or equal to 10 items) for this shop
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.price, p.stock, p.image, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.shop_id = ? AND p.stock <= 10
        ORDER BY p.stock ASC
        LIMIT 5
    ");
    $stmt->execute([$shopId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the products data
    echo json_encode([
        'products' => $products
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error in get_low_stock_products.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
