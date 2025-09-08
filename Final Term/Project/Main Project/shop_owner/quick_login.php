<?php
// Start session
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../helpers.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to check if the email exists in the database
function userExists($email) {
    global $conn;
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to create a test user if they don't exist
function createTestUser($email, $password, $name) {
    global $conn;
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create the user
    $sql = "INSERT INTO users (name, email, password, role, is_active, created_at) 
            VALUES (?, ?, ?, 'shop_owner', 1, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $name, $email, $hashedPassword);
    return $stmt->execute();
}

// Function to create a shop for a user
function createShopForUser($userId, $shopName) {
    global $conn;
    $description = "Shop managed by owner ID: $userId";
    
    $sql = "INSERT INTO shops (name, owner_id, description, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sis', $shopName, $userId, $description);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

// Process the login attempt
$message = '';
$success = false;
$debug = [];

// Test user credentials
$testEmail = 'test@example.com';
$testPassword = 'password123';
$testName = 'Test Shop Owner';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the test user exists, create if not
    if (!userExists($testEmail)) {
        $debug[] = "Test user doesn't exist, creating...";
        if (createTestUser($testEmail, $testPassword, $testName)) {
            $debug[] = "Test user created successfully.";
        } else {
            $debug[] = "Failed to create test user: " . $conn->error;
        }
    } else {
        $debug[] = "Test user already exists.";
    }
    
    // Attempt login
    $sql = "SELECT id, name, email, password, role, is_active FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $testEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "User not found.";
    } else {
        $user = $result->fetch_assoc();
        $debug[] = "User found: ID={$user['id']}, Role={$user['role']}";
        
        // Check shop
        $checkShopSql = "SELECT id, name FROM shops WHERE owner_id = ?";
        $stmt = $conn->prepare($checkShopSql);
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $shopResult = $stmt->get_result();
        
        $shopId = null;
        $shopName = null;
        
        if ($shopResult->num_rows === 0) {
            $debug[] = "No shop found for user, creating one...";
            $shopName = $user['name'] . "'s Shop";
            $shopId = createShopForUser($user['id'], $shopName);
            
            if (!$shopId) {
                $message = "Failed to create shop: " . $conn->error;
                $debug[] = $message;
            } else {
                $debug[] = "Shop created: ID=$shopId, Name=$shopName";
            }
        } else {
            $shop = $shopResult->fetch_assoc();
            $shopId = $shop['id'];
            $shopName = $shop['name'];
            $debug[] = "Shop found: ID=$shopId, Name=$shopName";
        }
        
        if ($shopId) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['shop_id'] = $shopId;
            $_SESSION['shop_name'] = $shopName;
            
            $success = true;
            $message = "Login successful! You've been logged in as a shop owner.";
            $debug[] = "Session variables set: " . json_encode($_SESSION);
            
            // Redirect after a short delay
            header("Refresh: 3; URL=index.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Login - Shop Owner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #333;
            margin-top: 0;
        }
        
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .debug-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .debug-info h3 {
            margin-top: 0;
            color: #6c757d;
        }
        
        pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quick Shop Owner Login</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p>You are now logged in. You will be redirected to the dashboard in a few seconds.</p>
            <p>If you are not redirected, <a href="index.php">click here</a>.</p>
        <?php else: ?>
            <p>This page will help you log in as a shop owner quickly.</p>
            <p>It will create a test account if one doesn't exist and automatically log you in.</p>
            
            <form method="post" action="">
                <p>Login will use these credentials:</p>
                <ul>
                    <li><strong>Email:</strong> <?php echo $testEmail; ?></li>
                    <li><strong>Password:</strong> <?php echo $testPassword; ?></li>
                </ul>
                
                <button type="submit" class="btn">Login as Shop Owner</button>
                <a href="login.php" class="btn btn-secondary">Go to Regular Login</a>
            </form>
        <?php endif; ?>
        
        <div class="debug-info">
            <h3>Debug Information</h3>
            <pre><?php echo implode("\n", $debug); ?></pre>
            
            <h3>Session Information</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    </div>
</body>
</html>
