<?php
// Initialize session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Redirect if already logged in as shop owner
if (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner') {
    redirect('index.html');
    exit;
}

// Handle form submission
$errors = [];
$debug = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $sql = "SELECT id, name, email, password, role, is_active FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $debug[] = "No user found with email: $email";
            $errors[] = 'Invalid credentials or you are not registered as a shop owner';
        } else {
            $user = $result->fetch_assoc();
            $debug[] = "User found: ID={$user['id']}, Role={$user['role']}";
            
            // Check if user is a shop owner
            if ($user['role'] !== 'shop_owner') {
                $debug[] = "User is not a shop owner. Role is: {$user['role']}";
                $errors[] = "Your account is not registered as a shop owner. Your role is: {$user['role']}";
            } 
            // Check if user is active
            else if ($user['is_active'] == 0) {
                $debug[] = "User account is not active";
                $errors[] = "Your account is not active. Please wait for admin approval or contact administrator.";
            }
            else {
                // Verify password
                if (!password_verify($password, $user['password'])) {
                    $debug[] = "Password verification failed";
                    $errors[] = 'Invalid password';
                } else {
                    $debug[] = "Password verified successfully";
                    
                    // Get the shop associated with this owner
                    $checkShopSql = "SELECT id, name FROM shops WHERE owner_id = ?";
                    $stmt = $conn->prepare($checkShopSql);
                    $stmt->bind_param('i', $user['id']);
                    $stmt->execute();
                    $shopResult = $stmt->get_result();
                    
                    if ($shopResult->num_rows === 0) {
                        // If no shop exists, automatically create one for this shop owner
                        $shopName = $user['name'] . "'s Shop";
                        $description = "Shop managed by " . $user['name'];
                        
                        $createShopSql = "INSERT INTO shops (name, owner_id, description, created_at) VALUES (?, ?, ?, NOW())";
                        $stmt = $conn->prepare($createShopSql);
                        $stmt->bind_param("sis", $shopName, $user['id'], $description);
                        
                        if ($stmt->execute()) {
                            $shopId = $conn->insert_id;
                            $debug[] = "Created new shop for user: Shop ID=$shopId, Name=$shopName";
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['shop_id'] = $shopId;
                            $_SESSION['shop_name'] = $shopName;
                            
                            // Ensure session is written
                            session_write_close();
                            session_start();
                            
                            $debug[] = "Session variables set successfully";
                            
                            // Redirect to dashboard
                            redirect('index.html');
                            exit;
                        } else {
                            $debug[] = "Failed to create shop: " . $conn->error;
                            $errors[] = 'Failed to create shop for your account. Please contact the administrator.';
                        }} else {
                        $shop = $shopResult->fetch_assoc();
                        $debug[] = "Shop found: ID={$shop['id']}, Name={$shop['name']}";
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['shop_id'] = $shop['id'];
                        $_SESSION['shop_name'] = $shop['name'];
                        
                        // Ensure session is written
                        session_write_close();
                        session_start();
                        
                        $debug[] = "Session variables set successfully";
                        
                        // Redirect to dashboard
                        redirect('index.html');
                        exit;
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
    <title>Shop Owner Login - Online Grocery Store</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <style>
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
        }
        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="login-header">
                <h2>Shop Owner Login</h2>
                <p>Access your shop management panel</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn primary">Login</button>
                </div>
            </form>
            
            <div class="login-footer">
                <p>Don't have a shop owner account? <a href="register.html">Apply here</a></p>
                <p><a href="../index.html">Back to Homepage</a></p>
            </div>
        </div>
    </div>
</body>
</html>
