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

// Get recent orders (limited to 5)
$sql = "SELECT o.id, o.order_date, o.total, o.status, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC 
        LIMIT 5";

$orders = fetchAll($sql);

// Return recent orders
echo json_encode($orders);
?>
