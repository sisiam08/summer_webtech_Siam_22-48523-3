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
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor ID is required'
    ]);
    exit;
}

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
$id = intval($data['id']);
$name = trim($data['name']);
$email = trim($data['email']);
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$address = isset($data['address']) ? trim($data['address']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$commissionRate = floatval($data['commission_rate']);
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Check if vendor exists
$checkSql = "SELECT id FROM vendors WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor not found'
    ]);
    exit;
}

// Check if another vendor with the same email exists
$checkEmailSql = "SELECT id FROM vendors WHERE email = ? AND id != ?";
$stmt = $conn->prepare($checkEmailSql);
$stmt->bind_param('si', $email, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Another vendor with this email already exists'
    ]);
    exit;
}

// Update vendor
$sql = "UPDATE vendors SET name = ?, email = ?, phone = ?, address = ?, description = ?, commission_rate = ?, is_active = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssdi', $name, $email, $phone, $address, $description, $commissionRate, $isActive, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Vendor updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update vendor: ' . $conn->error
    ]);
}
?>
