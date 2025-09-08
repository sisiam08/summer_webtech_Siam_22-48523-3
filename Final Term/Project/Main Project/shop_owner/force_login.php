<?php
// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Set no-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Destroy session
session_destroy();

// Start a new session
session_start();

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Shop Owner';
$_SESSION['user_email'] = 'shop@example.com';
$_SESSION['user_role'] = 'shop_owner';
$_SESSION['shop_id'] = 1;
$_SESSION['shop_name'] = 'Test Shop';

// Output HTML with JavaScript redirect
echo '<!DOCTYPE html>
<html>
<head>
    <title>Login Bypass</title>
    <meta http-equiv="refresh" content="2;url=index.html">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f9ff;
            color: #333;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d63c8;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login Bypassed</h1>
        <p>You\'re being automatically logged in as a Shop Owner.</p>
        <div class="spinner"></div>
        <p>Redirecting to the dashboard...</p>
    </div>
    
    <script>
        // Log to console for debugging
        console.log("Session created:", '.json_encode($_SESSION).');
        
        // Redirect after a short delay
        setTimeout(function() {
            window.location.href = "index.html";
        }, 2000);
    </script>
</body>
</html>';

exit;
?>
