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

// Validate required fields
if (!isset($data['name']) || empty($data['name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor name is required'
    ]);
    exit;
}

if (!isset($data['email']) || empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid vendor email is required'
    ]);
    exit;
}

if (!isset($data['commission_rate']) || $data['commission_rate'] < 0 || $data['commission_rate'] > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid commission rate between 0 and 100 is required'
    ]);
    exit;
}

// Sanitize inputs
$name = trim($data['name']);
$email = trim($data['email']);
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$address = isset($data['address']) ? trim($data['address']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$commissionRate = floatval($data['commission_rate']);
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Check if vendor with same email already exists
$checkSql = "SELECT id FROM vendors WHERE email = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'A vendor with this email already exists'
    ]);
    exit;
}

// Insert new vendor
$sql = "INSERT INTO vendors (name, email, phone, address, description, commission_rate, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssdi', $name, $email, $phone, $address, $description, $commissionRate, $isActive);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Vendor added successfully',
        'id' => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add vendor: ' . $conn->error
    ]);
}
?>
