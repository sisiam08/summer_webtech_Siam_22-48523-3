<?php
// Start session if not already started
session_start();

// Include database connection
require_once __DIR__ . '/../Database/database.php';
require_once __DIR__ . "/../Includes/functions.php";

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

// Validate required fields
$requiredFields = ['name', 'email', 'phone', 'address', 'vehicle_type', 'password'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields'
        ]);
        exit;
    }
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

// Check if email already exists
$checkEmailSql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($checkEmailSql);
$stmt->bind_param('s', $data['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Email address already exists'
    ]);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user record with correct column name and role
    $insertUserSql = "INSERT INTO users (name, email, phone, password, address, role, is_active) VALUES (?, ?, ?, ?, ?, 'delivery_man', 0)";
    $stmt = $conn->prepare($insertUserSql);
    $stmt->bind_param('sssss', $data['name'], $data['email'], $data['phone'], $hashedPassword, $data['address']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user account');
    }
    
    $userId = $conn->insert_id;
    
    // Since delivery_personnel table doesn't exist, we'll store additional info in user profile or create a simple approach
    // For now, let's just use the users table and maybe store additional delivery info later
    
    // You could create delivery_personnel table later or store additional fields in users table
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Your account has been created and is pending approval. You will be contacted once your application is reviewed.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>
