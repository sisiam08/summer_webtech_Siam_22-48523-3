<?php
// Include necessary files
include '../config/database.php';
include '../helpers.php';

// Start session
ini_set('session.cookie_samesite', 'Lax');  // Changed from None to Lax
ini_set('session.cookie_secure', 'Off');    // Changed from On to Off for localhost
session_start();

// Process login form
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a shop owner session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test Shop Owner';
    $_SESSION['user_role'] = 'shop_owner';
    $_SESSION['shop_id'] = 1;
    $_SESSION['email'] = 'shopowner@example.com';
    
    $success = true;
    $message = 'Test session created successfully!';
}

// Check current session status
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$is_shop_owner = $is_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Login Session</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        pre {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .success {
            color: green;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #388E3C;
        }
        .session-status {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
        }
        .logged-in {
            background: #e8f5e9;
        }
        .logged-out {
            background: #ffebee;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Session Fixer Tool</h1>
        
        <?php if ($success): ?>
        <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="session-status <?php echo $is_logged_in ? 'logged-in' : 'logged-out'; ?>">
            <h3>Current Session Status:</h3>
            <p><strong>Logged in:</strong> <?php echo $is_logged_in ? 'Yes' : 'No'; ?></p>
            <p><strong>Is shop owner:</strong> <?php echo $is_shop_owner ? 'Yes' : 'No'; ?></p>
            <?php if ($is_logged_in): ?>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            <p><strong>User Name:</strong> <?php echo $_SESSION['user_name'] ?? 'Not set'; ?></p>
            <p><strong>User Role:</strong> <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
            <p><strong>Shop ID:</strong> <?php echo $_SESSION['shop_id'] ?? 'Not set'; ?></p>
            <?php endif; ?>
        </div>
        
        <form method="post" action="">
            <button type="submit" class="btn">Create Test Shop Owner Session</button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="session_debug.php" target="_blank">View Detailed Session Info</a> | 
            <a href="add-product.php" target="_blank">Go to Add Product Page</a>
        </div>
        
        <h3>Session Configuration:</h3>
        <pre>
session.save_path: <?php echo ini_get('session.save_path'); ?>

session.name: <?php echo ini_get('session.name'); ?>

session.cookie_lifetime: <?php echo ini_get('session.cookie_lifetime'); ?>

session.cookie_path: <?php echo ini_get('session.cookie_path'); ?>

session.cookie_domain: <?php echo ini_get('session.cookie_domain'); ?>

session.cookie_secure: <?php echo ini_get('session.cookie_secure'); ?>

session.cookie_httponly: <?php echo ini_get('session.cookie_httponly'); ?>

session.cookie_samesite: <?php echo ini_get('session.cookie_samesite'); ?>
        </pre>
        
        <h3>Server Info:</h3>
        <pre>
PHP Version: <?php echo phpversion(); ?>

Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?>

Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
        </pre>
    </div>
</body>
</html>
