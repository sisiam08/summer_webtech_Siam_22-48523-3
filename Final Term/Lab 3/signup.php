<?php
session_start();

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
 
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
        
        $usersFile = 'users.json';
        if(file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);
        } else {
            $users = [
                ['username' => 'admin', 'email' => 'admin@example.com', 'password' => 'password', 'created_at' => date('c')]
            ];
        }
        
        foreach($users as $user) {
            if(strtolower($user['username']) === strtolower($username)) {
                $username_err = "This username is already taken.";
                break;
            }
        }
    }
    
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            foreach($users as $user) {
                if(isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
                    $email_err = "This email is already registered.";
                    break;
                }
            }
        }
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        $newUser = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'created_at' => date("Y-m-d H:i:s")
        ];
        
    $users[] = $newUser;
    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
        
        $_SESSION['signup_success'] = true;
        header("location: login.php");
        exit();
    }
}

include 'signup.html';
?>
