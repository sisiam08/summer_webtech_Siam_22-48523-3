<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['isAuthenticated' => false]);
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$shop_id = $_SESSION['shop_id'] ?? 0;

// Verify shop ownership
$stmt = $conn->prepare("SELECT id, name FROM shops WHERE id = ? AND owner_id = ?");
$stmt->bind_param('ii', $shop_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['isAuthenticated' => false]);
    exit;
}

$shop = $result->fetch_assoc();

echo json_encode([
    'isAuthenticated' => true,
    'role' => 'shop_owner',
    'name' => $user_name,
    'shop' => [
        'id' => $shop['id'],
        'name' => $shop['name']
    ]
]);
?>
?>
