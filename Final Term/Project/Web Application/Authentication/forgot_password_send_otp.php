<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../Database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found in our records']);
        exit;
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", rand(100000, 999999));
    
    // Store OTP in session for verification
    $_SESSION['forgot_password_otp'] = $otp;
    $_SESSION['forgot_password_email'] = $email;
    $_SESSION['otp_generated_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP generated successfully',
        'otp' => $otp // In real application, you wouldn't send this back
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>