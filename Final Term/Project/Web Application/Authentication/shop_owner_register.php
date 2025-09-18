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
$requiredFields = ['name', 'shop_name', 'email', 'phone', 'address', 'city', 'description', 'password'];
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
        'message' => 'This email is already registered'
    ]);
    exit;
}

// Check if vendor table exists, create if not
$checkVendorTable = "SHOW TABLES LIKE 'shops'";
$shopTableResult = $conn->query($checkVendorTable);
if ($shopTableResult->num_rows == 0) {
    // Create vendors table
    $createVendorTable = "CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_name VARCHAR(100) NOT NULL,
        description TEXT,
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        is_approved TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($createVendorTable);
}

// Hash password
$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

// Begin transaction
$conn->begin_transaction();

try {
    // Insert user
    $insertUserSql = "INSERT INTO users (name, email, password, phone, address, city, role, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, 'shop_owner', 0)";
    $stmt = $conn->prepare($insertUserSql);
    $stmt->bind_param('ssssss', 
        $data['name'], 
        $data['email'], 
        $hashedPassword, 
        $data['phone'], 
        $data['address'], 
        $data['city']
    );
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Insert vendor
    $insertVendorSql = "INSERT INTO shops (user_id, shop_name, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertVendorSql);
    $stmt->bind_param('iss', $userId, $data['shop_name'], $data['description']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Your shop owner application has been submitted. You will be notified once your account is approved.'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>
