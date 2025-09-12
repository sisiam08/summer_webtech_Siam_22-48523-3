<?php
session_start();

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";
$remember_me = false;

$usersFile = 'users.json';
if(file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
} else {
    $users = [
        [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'created_at' => date('c')
        ]
    ];
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

if($_POST) {
 
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(isset($_POST["remember_me"])) {
        $remember_me = true;
    }
    
    if(empty($username_err) && empty($password_err)) {
        $usersFile = 'users.json';
        if(file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);
        } else {
            $users = [];
        }
        
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
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username;
            $_SESSION["login_time"] = date("Y-m-d H:i:s");
            $_SESSION["session_created"] = date("Y-m-d H:i:s");
            
            if($remember_me) {
                $cookie_expiry = time() + 86400 * 30; // 30 days
                setcookie("user_login", $username, $cookie_expiry, "/");
                setcookie("user_remember", "1", $cookie_expiry, "/");
                setcookie("cookie_created", date("Y-m-d H:i:s"), $cookie_expiry, "/");
                setcookie("cookie_expiry", date("Y-m-d H:i:s", $cookie_expiry), $cookie_expiry, "/");
            }
            
            header("location: dashboard.php");
            exit;
        } else {
            $login_err = "Invalid username or password.";
        }
    }
}

include 'login.html';
?>
