<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get shop ID from session
$shop_id = $_SESSION['shop_id'] ?? 0;

// Get low stock products (stock < 10) for this shop
$sql = "SELECT id, name, price, stock, image FROM products 
        WHERE shop_id = ? AND stock < 10 AND stock > 0 
        ORDER BY stock ASC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // Format price
    $row['formatted_price'] = '$' . number_format($row['price'], 2);
    
    $products[] = $row;
}

// Return low stock products
echo json_encode(['products' => $products]);
?>
