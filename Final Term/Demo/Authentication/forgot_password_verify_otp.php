<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
    exit;
}

// Check if OTP session data exists
if (!isset($_SESSION['forgot_password_otp']) || !isset($_SESSION['forgot_password_email']) || !isset($_SESSION['otp_generated_time'])) {
    echo json_encode(['success' => false, 'message' => 'OTP session expired. Please request a new OTP']);
    exit;
}

// Check if OTP is still valid (10 minutes expiry)
$otpGeneratedTime = $_SESSION['otp_generated_time'];
$currentTime = time();
$otpExpiryTime = 10 * 60; // 10 minutes

if (($currentTime - $otpGeneratedTime) > $otpExpiryTime) {
    // Clear expired OTP session
    unset($_SESSION['forgot_password_otp']);
    unset($_SESSION['forgot_password_email']);
    unset($_SESSION['otp_generated_time']);
    
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new OTP']);
    exit;
}

// Verify OTP and email
if ($_SESSION['forgot_password_otp'] === $otp && $_SESSION['forgot_password_email'] === $email) {
    // Generate a secure token for password reset
    $resetToken = bin2hex(random_bytes(32));
    
    // Store reset token in session
    $_SESSION['password_reset_token'] = $resetToken;
    $_SESSION['password_reset_email'] = $email;
    $_SESSION['token_generated_time'] = time();
    
    // Clear OTP session data
    unset($_SESSION['forgot_password_otp']);
    unset($_SESSION['forgot_password_email']);
    unset($_SESSION['otp_generated_time']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP verified successfully',
        'token' => $resetToken
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP or email']);
}
?>