<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate status
if (!isset($data['status']) || !in_array($data['status'], ['online', 'offline'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid status'
    ]);
    exit;
}

$status = $data['status'];

// Connect to database
require_once '../db_connect.php';

try {
    // Update delivery person status
    $stmt = $conn->prepare("UPDATE delivery_personnel SET status = ?, last_status_change = NOW() WHERE user_id = ?");
    $stmt->bind_param("si", $status, $_SESSION['user_id']);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update status'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
