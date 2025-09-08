<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Get top selling products (limited to 5)
$sql = "SELECT p.id, p.name, p.price, p.stock, c.name as category, 
        COALESCE(SUM(oi.quantity), 0) as sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN categories c ON p.category_id = c.id
        GROUP BY p.id
        ORDER BY sold DESC
        LIMIT 5";

$products = fetchAll($sql);

// Return top products
echo json_encode($products);
?>
