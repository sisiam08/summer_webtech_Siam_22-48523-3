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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'error' => 'User ID is required'
    ]);
    exit;
}

$userId = $_GET['id'];

// Get user details
$userSql = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode([
        'error' => 'User not found'
    ]);
    exit;
}

$user = $userResult->fetch_assoc();

// Get user addresses
$addressesSql = "SELECT * FROM addresses WHERE user_id = ?";
$addressesStmt = $conn->prepare($addressesSql);
$addressesStmt->bind_param('i', $userId);
$addressesStmt->execute();
$addressesResult = $addressesStmt->get_result();
$addresses = [];

while ($address = $addressesResult->fetch_assoc()) {
    $addresses[] = $address;
}

// Get user orders (limited to 5 most recent)
$ordersSql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5";
$ordersStmt = $conn->prepare($ordersSql);
$ordersStmt->bind_param('i', $userId);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();
$orders = [];

while ($order = $ordersResult->fetch_assoc()) {
    $orders[] = $order;
}

// Return user details
echo json_encode([
    'user' => $user,
    'addresses' => $addresses,
    'orders' => $orders
]);
?>
