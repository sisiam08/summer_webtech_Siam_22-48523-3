<?php
// Start session if not already started
session_start();

// Clear all session variables
$_SESSION = [];

// If a session cookie is used, clear it
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: ../index.php');
exit;
?>
