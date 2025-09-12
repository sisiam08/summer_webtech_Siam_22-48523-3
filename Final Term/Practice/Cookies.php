<?php
if($_SERVER["REQUEST_METHOD"]=="POST")
{
    if(isset($_POST['setCookies']))
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        setcookie("username", $username, time() + 86400, "/");
        setcookie("password", $password, time() + 86400, "/");
        echo "Cookies set successfully.";
    }
    elseif(isset($_POST['displayCookies']))
    {
        if(isset($_COOKIE['username']) && isset($_COOKIE['password']))
        {
            echo "Username: " . $_COOKIE["username"];
            echo "<br>";
            echo "Password: ". $_COOKIE['password'];
        }
    }
    elseif(isset($_POST['deleteCookies']))
    {
        setcookie('username', '', -1, "/");
        setcookie('password', '', -1, "/");
        echo "Cookies deleted successfully.";
    }
}
?>