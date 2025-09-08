<?php
// Initialize session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate form data
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Attempt login if no errors
    if (empty($errors)) {
        if (loginUser($email, $password)) {
            // Redirect based on role
            $user = getCurrentUser();
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            } else if ($user['role'] === 'shop_owner') {
                redirect('shop_owner/index.html');
            } else {
                redirect('index.php');
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <h2>Login</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php" id="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
