<?php
// Start session
session_start();

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard_process.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
$remember_me = false;

// Users storage (JSON file). If missing, seed with a default admin user.
$usersFile = __DIR__ . DIRECTORY_SEPARATOR . 'users.json';
if (!file_exists($usersFile)) {
    $seed = [
        [
            'username' => 'admin',
            'password' => 'password',
            'created_at' => date('c')
        ]
    ];
    file_put_contents($usersFile, json_encode($seed, JSON_PRETTY_PRINT));
}

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
        // Load users and attempt to authenticate
        $users = json_decode(@file_get_contents($usersFile), true) ?: [];
        $auth = false;
        foreach ($users as $u) {
            if (isset($u['username']) && strtolower($u['username']) === strtolower($username)) {
                if (isset($u['password']) && $u['password'] === $password) {
                    $auth = true;
                }
                break;
            }
        }

        if($auth) {
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username;
            $_SESSION["login_time"] = date("Y-m-d H:i:s");
            $_SESSION["session_created"] = date("Y-m-d H:i:s");
            
            // Set cookies if remember me is checked
            if($remember_me) {
                $cookie_expiry = time() + 86400 * 30; // 30 days
                setcookie("user_login", $username, $cookie_expiry, "/");
                setcookie("user_remember", "1", $cookie_expiry, "/");
                setcookie("cookie_created", date("Y-m-d H:i:s"), $cookie_expiry, "/");
                setcookie("cookie_expiry", date("Y-m-d H:i:s", $cookie_expiry), $cookie_expiry, "/");
            }
            
            // Redirect user to dashboard
            header("location: dashboard_process.php");
            exit;
        } else {
            // Username or password is invalid
            $login_err = "Invalid username or password.";
        }
    }
}

// Include the HTML template for login
include 'login_template.html';
?>
