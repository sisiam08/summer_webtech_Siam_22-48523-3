<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get cookies data
$cookie_username = isset($_COOKIE["user_login"]) ? $_COOKIE["user_login"] : "Not set";
$cookie_remember = isset($_COOKIE["user_remember"]) ? "Yes" : "No";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Session and Cookies Demo</title>
</head>
<body>
    <div>
        <h2>Welcome to Dashboard</h2>
        <p>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to the dashboard.</p>
        
        <h3>Session Information</h3>
        <table border="1">
            <tr>
                <th>Session Variable</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><?php echo session_id(); ?></td>
            </tr>
            <tr>
                <td>Username</td>
                <td><?php echo htmlspecialchars($_SESSION["username"]); ?></td>
            </tr>
            <tr>
                <td>Login Time</td>
                <td><?php echo htmlspecialchars($_SESSION["login_time"]); ?></td>
            </tr>
        </table>
        
        <h3>Cookie Information</h3>
        <table border="1">
            <tr>
                <th>Cookie Variable</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Username Cookie</td>
                <td><?php echo htmlspecialchars($cookie_username); ?></td>
            </tr>
            <tr>
                <td>Remember Me</td>
                <td><?php echo $cookie_remember; ?></td>
            </tr>
        </table>
        
        <p>
            <a href="logout.php">Sign Out</a>
        </p>
    </div>
</body>
</html>
