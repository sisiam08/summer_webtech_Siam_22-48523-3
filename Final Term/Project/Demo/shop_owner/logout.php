<?php
// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// If a session cookie is used, clear it too
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Set no-cache headers to ensure the browser doesn't cache the logged-out state
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.html");
exit;
?>
