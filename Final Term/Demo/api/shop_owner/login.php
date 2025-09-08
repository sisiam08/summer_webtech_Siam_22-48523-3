<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

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

// Validate email and password
if (!isset($data['email']) || empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

if (!isset($data['password']) || empty($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your password'
    ]);
    exit;
}

$email = $data['email'];
$password = $data['password'];
$remember = isset($data['remember']) ? $data['remember'] : false;

// Check if user exists and is a shop owner
$sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'shop_owner'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid credentials or you are not registered as a shop owner'
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid credentials'
    ]);
    exit;
}

// Check if user is active
$checkActiveSql = "SELECT is_active FROM users WHERE id = ?";
$stmt = $conn->prepare($checkActiveSql);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$activeResult = $stmt->get_result();
$isActive = $activeResult->fetch_assoc()['is_active'];

if ($isActive != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Your account is inactive. Please contact the administrator.'
    ]);
    exit;
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// Set remember me cookie if requested
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    // Store token in database
    $storeTokenSql = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))";
    $stmt = $conn->prepare($storeTokenSql);
    $stmt->bind_param('isi', $user['id'], $token, $expiry);
    $stmt->execute();
    
    // Set cookie
    setcookie('remember_token', $token, $expiry, '/', '', false, true);
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
?>
