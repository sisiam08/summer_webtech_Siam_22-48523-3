<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    setcookie("user_login", "", time() - 3600, "/");
    setcookie("user_remember", "", time() - 3600, "/");
    setcookie("cookie_created", "", time() - 3600, "/");
    setcookie("cookie_expiry", "", time() - 3600, "/");

    session_destroy();

    header("location: login.php");
    exit;
}

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$cookie_username = isset($_COOKIE["user_login"]) ? $_COOKIE["user_login"] : "Not set";
$cookie_remember = isset($_COOKIE["user_remember"]) ? "Yes" : "No";
$cookie_created = isset($_COOKIE["cookie_created"]) ? $_COOKIE["cookie_created"] : "Not set";
$cookie_expiry = isset($_COOKIE["cookie_expiry"]) ? $_COOKIE["cookie_expiry"] : "Not set";

$session_start = isset($_SESSION["session_created"]) ? $_SESSION["session_created"] : $_SESSION["login_time"];
$session_duration = time() - strtotime($session_start);
$session_duration_formatted = sprintf(
    "%02d:%02d:%02d", 
    ($session_duration/3600), 
    ($session_duration/60%60), 
    $session_duration%60
);

include 'dashboard.html';
?>
