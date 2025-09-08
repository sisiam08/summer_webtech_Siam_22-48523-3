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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Check if user ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$userId = intval($data['id']);

// Check if user is the current logged-in admin
if ($userId === $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'You cannot delete your own account'
    ]);
    exit;
}

// Check if user exists
$checkSql = "SELECT id, role FROM users WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Check if user has orders
$orderCheckSql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($orderCheckSql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orderCount = $result->fetch_assoc()['count'];

if ($orderCount > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete user with existing orders. Consider setting the account to inactive instead.'
    ]);
    exit;
}

// Begin transaction to handle related data
$conn->begin_transaction();

try {
    // Delete user's addresses
    $deleteAddressesSql = "DELETE FROM addresses WHERE user_id = ?";
    $stmt = $conn->prepare($deleteAddressesSql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    // Delete user
    $deleteUserSql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteUserSql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}
?>
