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

// Validate required fields
$requiredFields = ['name', 'email', 'role', 'password', 'status'];
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
$password = $_POST['password'];
$status = sanitize($_POST['status']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Check if email already exists
$checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
$checkEmailStmt = $conn->prepare($checkEmailSql);
$checkEmailStmt->bind_param('s', $email);
$checkEmailStmt->execute();
$checkEmailResult = $checkEmailStmt->get_result();
$emailExists = $checkEmailResult->fetch_assoc()['count'] > 0;

if ($emailExists) {
    echo json_encode([
        'success' => false,
        'message' => 'Email is already in use'
    ]);
    exit;
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

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$sql = "INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssss', $name, $email, $phone, $hashedPassword, $role, $status);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'User added successfully',
        'user_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add user: ' . $conn->error
    ]);
}
?>
