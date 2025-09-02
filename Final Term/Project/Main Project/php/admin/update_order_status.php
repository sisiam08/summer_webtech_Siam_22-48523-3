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

// Check if order ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit;
}

$orderId = intval($data['id']);
$status = sanitize($data['status'] ?? '');
$paymentStatus = sanitize($data['payment_status'] ?? '');

// Validate status values
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];

if (!empty($status) && !in_array($status, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order status'
    ]);
    exit;
}

if (!empty($paymentStatus) && !in_array($paymentStatus, $validPaymentStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment status'
    ]);
    exit;
}

// Update order status
$updateFields = [];
$updateParams = [];
$updateTypes = '';

if (!empty($status)) {
    $updateFields[] = "status = ?";
    $updateParams[] = $status;
    $updateTypes .= 's';
}

if (!empty($paymentStatus)) {
    $updateFields[] = "payment_status = ?";
    $updateParams[] = $paymentStatus;
    $updateTypes .= 's';
}

if (empty($updateFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'No status values provided for update'
    ]);
    exit;
}

$sql = "UPDATE orders SET " . implode(", ", $updateFields) . " WHERE id = ?";
$updateParams[] = $orderId;
$updateTypes .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($updateTypes, ...$updateParams);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update order status: ' . $conn->error
    ]);
}
?>
