<?php
// Initialize session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Initialize variables
$errors = [];
$debug = [];
$email = '';
$loginAttempt = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginAttempt = true;
    
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $debug[] = "Login attempt with email: $email";
    
    // Validate form data
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Attempt login if no errors
    if (empty($errors)) {
        // Check if user exists
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $debug[] = "No user found with email: $email";
            $errors[] = 'Invalid credentials or you are not registered as a shop owner';
            
            // Display all users for debugging
            $allUsers = $conn->query("SELECT id, name, email, role FROM users");
            if ($allUsers->num_rows > 0) {
                $debug[] = "Users in database:";
                while ($user = $allUsers->fetch_assoc()) {
                    $debug[] = "ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}";
                }
            } else {
                $debug[] = "No users found in database";
            }
        } else {
            $user = $result->fetch_assoc();
            $debug[] = "User found: ID={$user['id']}, Role={$user['role']}";
            
            // Check if user is a shop owner
            if ($user['role'] !== 'shop_owner') {
                $debug[] = "User is not a shop owner. Role is: {$user['role']}";
                $errors[] = "Your account is not registered as a shop owner. Your role is: {$user['role']}";
            } else {
                // Verify password
                if (!password_verify($password, $user['password'])) {
                    $debug[] = "Password verification failed";
                    $errors[] = 'Invalid password';
                    $debug[] = "Stored password hash: {$user['password']}";
                    $debug[] = "New hash of provided password: " . password_hash($password, PASSWORD_DEFAULT);
                } else {
                    $debug[] = "Password verified successfully";
                    
                    // Get the shop associated with this owner
                    $checkShopSql = "SELECT id, name FROM shops WHERE owner_id = ?";
                    $stmt = $conn->prepare($checkShopSql);
                    $stmt->bind_param('i', $user['id']);
                    $stmt->execute();
                    $shopResult = $stmt->get_result();
                    
                    if ($shopResult->num_rows === 0) {
                        $debug[] = "No shop found for user ID: {$user['id']}";
                        $errors[] = 'No shop associated with this account. Please contact the administrator.';
                        
                        // Show all shops for debugging
                        $allShops = $conn->query("SELECT id, name, owner_id FROM shops");
                        if ($allShops->num_rows > 0) {
                            $debug[] = "Shops in database:";
                            while ($shop = $allShops->fetch_assoc()) {
                                $debug[] = "ID: {$shop['id']}, Name: {$shop['name']}, Owner ID: {$shop['owner_id']}";
                            }
                        } else {
                            $debug[] = "No shops found in database";
                        }
                    } else {
                        $shop = $shopResult->fetch_assoc();
                        $debug[] = "Shop found: ID={$shop['id']}, Name={$shop['name']}";
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['shop_id'] = $shop['id'];
                        $_SESSION['shop_name'] = $shop['name'];
                        
                        $debug[] = "Session variables set successfully";
                        $debug[] = "Login successful - would normally redirect to dashboard";
                        
                        // For debug purposes, we won't redirect
                        // redirect('index.html');
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Shop Owner Login</title>
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
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .debug {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Shop Owner Login</h1>
        
        <?php if ($loginAttempt && empty($errors)): ?>
            <div class="success">Login successful! Debug information is displayed below.</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>Errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?: 'shopowner@test.com'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="password123" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <?php if (!empty($debug)): ?>
            <h2>Debug Information</h2>
            <div class="debug">
                <?php foreach ($debug as $message): ?>
                    <?php echo htmlspecialchars($message) . "\n"; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>Actions</h2>
        <p><a href="../setup_database_with_test_account.php" target="_blank">Run Setup Script</a> to create test account and database tables</p>
        <p><a href="../check_database_basic.php" target="_blank">Check Database Connection</a> to verify database connection and tables</p>
        <p><a href="login.html">Go to Regular Shop Owner Login</a></p>
    </div>
</body>
</html>
