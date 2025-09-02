<?php
session_start();

if(!isset($_SESSION["loggedin"]) && isset($_COOKIE["user_login"]) && isset($_COOKIE["user_remember"])) 
{
    $_SESSION["loggedin"] = true;
    $_SESSION["username"] = $_COOKIE["user_login"];
    $_SESSION["login_time"] = date("Y-m-d H:i:s") . " (Auto-login from cookie)";
    
    header("location: dashboard.php");
    exit;
}

header("location: login.php");
exit;
?>
