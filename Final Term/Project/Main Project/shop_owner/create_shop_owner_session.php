<?php
// This script is a workaround to inject session variables
// This is only for debugging/development and bypasses authentication
// DO NOT use in production environments

// Ensure error reporting is enabled for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Set session variables for shop owner
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Shop Owner';
$_SESSION['user_email'] = 'shop@example.com';
$_SESSION['user_role'] = 'shop_owner';
$_SESSION['shop_id'] = 1;
$_SESSION['shop_name'] = 'Test Shop';

// Output the result
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    // Return JSON for API testing
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Shop owner session successfully created',
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]);
} else {
    // Output HTML with JavaScript redirect
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Session Created</title>
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
            <h1>Shop Owner Session Created</h1>
            <p>Successfully created session for Shop Owner.</p>
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
}
?>
