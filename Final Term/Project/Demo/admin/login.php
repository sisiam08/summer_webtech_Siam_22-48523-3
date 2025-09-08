<?php
// Start session
session_start();

// Include necessary files
require_once '../php/functions.php';
require_once '../php/admin/admin_auth.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Process login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Attempt to authenticate
        $admin = authenticateAdmin($email, $password);
        
        if ($admin) {
            // Set session variables
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_role'] = $admin['role'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            
            // Redirect to dashboard
            $success = 'Login successful! Redirecting...';
            header('Refresh: 1; URL=index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Online Grocery Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-login">
    <div class="admin-login-container">
        <div class="admin-login-box">
            <h1>Admin Login</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" value="1" class="btn btn-primary">Login</button>
            </form>
            
            <p><a href="../index.php">Return to Store</a></p>
        </div>
    </div>
</body>
</html>
