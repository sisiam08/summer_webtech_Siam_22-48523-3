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

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if user ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$userId = intval($_POST['id']);

// Get current user info
$currentUserSql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($currentUserSql);
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

$currentUser = $result->fetch_assoc();

// Validate required fields
$requiredFields = ['name', 'email', 'role', 'status'];
$errors = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Sanitize input
$name = sanitize($_POST['name']);
$email = sanitize($_POST['email']);
$phone = sanitize($_POST['phone'] ?? '');
$role = sanitize($_POST['role']);
$password = $_POST['password'] ?? '';
$status = sanitize($_POST['status']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Check if email already exists for a different user
if ($email !== $currentUser['email']) {
    $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?";
    $checkEmailStmt = $conn->prepare($checkEmailSql);
    $checkEmailStmt->bind_param('si', $email, $userId);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    $emailExists = $checkEmailResult->fetch_assoc()['count'] > 0;

    if ($emailExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is already in use by another user'
        ]);
        exit;
    }
}

// Validate role
$validRoles = ['customer', 'vendor', 'admin'];
if (!in_array($role, $validRoles)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid role'
    ]);
    exit;
}

// Validate status
$validStatuses = ['active', 'inactive'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status'
    ]);
    exit;
}

// Update user
if (!empty($password)) {
    // Hash new password if provided
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ?, role = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssi', $name, $email, $phone, $hashedPassword, $role, $status, $userId);
} else {
    // Keep existing password
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssi', $name, $email, $phone, $role, $status, $userId);
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update user: ' . $conn->error
    ]);
}
?>
