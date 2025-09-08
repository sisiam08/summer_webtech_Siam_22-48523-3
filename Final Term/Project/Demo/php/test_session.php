<?php
// Script to test session functionality
session_start();

echo "Session test script\n";
echo "-------------------\n";

// Display current session ID
echo "Session ID: " . session_id() . "\n";

// Check if any session data exists
echo "Current session data:\n";
print_r($_SESSION);

// Set some test data
$_SESSION['test_timestamp'] = time();
$_SESSION['test_value'] = 'Session test - ' . date('Y-m-d H:i:s');

echo "\nAfter setting test values:\n";
print_r($_SESSION);

// Display session configuration
echo "\nSession configuration:\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";

// Show cookie info
echo "\nCookie info:\n";
if (!empty($_COOKIE)) {
    foreach ($_COOKIE as $name => $value) {
        echo "$name: $value\n";
    }
} else {
    echo "No cookies set.\n";
}

echo "\nDone!\n";
?>
