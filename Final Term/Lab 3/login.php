<?php
// Start session
session_start();

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
$remember_me = false;

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check for remember me
    if(isset($_POST["remember_me"])) {
        $remember_me = true;
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // For demo purposes, using hardcoded values
        // In a real application, you would check against a database
        $valid_username = "admin";
        $valid_password = "password";
        
        if($username === $valid_username && $password === $valid_password) {
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username;
            $_SESSION["login_time"] = date("Y-m-d H:i:s");
            
            // Set cookies if remember me is checked
            if($remember_me) {
                setcookie("user_login", $username, time() + 86400 * 30, "/"); // 30 days
                setcookie("user_remember", "1", time() + 86400 * 30, "/"); // 30 days
            }
            
            // Redirect user to dashboard
            header("location: dashboard.php");
            exit;
        } else {
            // Username or password is invalid
            $login_err = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Session and Cookies Demo</title>
</head>
<body>
    <div>
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div style="color: red;">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Username</label>
                <input type="text" name="username" value="<?php echo $username; ?>">
                <span style="color: red;"><?php echo $username_err; ?></span>
            </div>    
            <div>
                <label>Password</label>
                <input type="password" name="password">
                <span style="color: red;"><?php echo $password_err; ?></span>
            </div>
            <div>
                <input type="checkbox" name="remember_me" <?php echo $remember_me ? 'checked' : ''; ?>> Remember Me
            </div>
            <div>
                <input type="submit" value="Login">
            </div>
            <p><b>Note:</b> For demo, use:<br>Username: admin<br>Password: password</p>
        </form>
    </div>
</body>
</html>
