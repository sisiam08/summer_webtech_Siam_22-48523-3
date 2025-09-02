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

// Check if recipient is provided
if (!isset($data['recipient']) || empty($data['recipient']) || !filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid recipient email is required'
    ]);
    exit;
}

$recipient = $data['recipient'];

// Get email settings
$sql = "SELECT * FROM settings WHERE setting_type = 'email'";
$result = $conn->query($sql);

$emailSettings = [];
while ($row = $result->fetch_assoc()) {
    $emailSettings[$row['setting_key']] = $row['setting_value'];
}

// Get store name
$storeNameSql = "SELECT setting_value FROM settings WHERE setting_key = 'site_title'";
$storeNameResult = $conn->query($storeNameSql);
$storeName = $storeNameResult->fetch_assoc()['setting_value'] ?? 'Online Grocery Store';

// Set email headers
$fromName = $emailSettings['email_from_name'] ?? $storeName;
$fromEmail = $emailSettings['email_from'] ?? 'noreply@example.com';
$headers = "From: $fromName <$fromEmail>\r\n";
$headers .= "Reply-To: $fromEmail\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Email subject and message
$subject = "Test Email from $storeName";
$message = "
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <h2>Test Email from $storeName</h2>
    <p>This is a test email to verify that your email settings are working correctly.</p>
    <p>If you received this email, your email settings are configured properly.</p>
    <hr>
    <p>Sent from the Admin Panel of $storeName</p>
</body>
</html>
";

// Send email
$emailSent = mail($recipient, $subject, $message, $headers);

if ($emailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . $recipient
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send test email. Please check your email settings.'
    ]);
}
?>
