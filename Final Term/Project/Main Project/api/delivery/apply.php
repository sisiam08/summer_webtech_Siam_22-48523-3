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

// Validate required fields
$requiredFields = ['name', 'email', 'phone', 'address', 'vehicle_type', 'license_number', 'password'];
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
    
    // Insert user record
    $insertUserSql = "INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'delivery', 'pending')";
    $stmt = $conn->prepare($insertUserSql);
    $stmt->bind_param('ssss', $data['name'], $data['email'], $data['phone'], $hashedPassword);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user account');
    }
    
    $userId = $conn->insert_id;
    
    // Insert delivery personnel record
    $insertDeliverySql = "INSERT INTO delivery_personnel (user_id, address, vehicle_type, license_number, emergency_contact, experience) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertDeliverySql);
    $emergencyContact = $data['emergency_contact'] ?? '';
    $experience = $data['experience'] ?? '';
    $stmt->bind_param('isssss', $userId, $data['address'], $data['vehicle_type'], $data['license_number'], $emergencyContact, $experience);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create delivery personnel profile');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! You will be contacted once your application is reviewed.'
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
