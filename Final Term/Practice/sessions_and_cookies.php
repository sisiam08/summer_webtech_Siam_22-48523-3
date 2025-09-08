<?php
// Start session
session_start();

// Set a cookie if not already set
if (!isset($_COOKIE['user_visit'])) {
    setcookie('user_visit', '1', time() + (86400 * 30), "/"); // 30 days
} else {
    $visits = $_COOKIE['user_visit'] + 1;
    setcookie('user_visit', $visits, time() + (86400 * 30), "/");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Sessions and Cookies</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1, h2 {
            color: #333;
        }
        .box {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .session-data {
            background-color: #e9f7ef;
            border-left: 4px solid #2ecc71;
        }
        .cookie-data {
            background-color: #fef5e7;
            border-left: 4px solid #f39c12;
        }
        input[type="text"],
        input[type="submit"] {
            padding: 8px;
            margin: 5px 0;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Sessions and Cookies</h1>
        
        <div class="box">
            <h2>What are Sessions?</h2>
            <p>Sessions are a way to store information (in variables) to be used across multiple pages. Unlike cookies, session data is stored on the server.</p>
            <p>Session information is stored in a temporary directory on the server. A session creates a file where registered session variables and their values are stored.</p>
        </div>
        
        <div class="box">
            <h2>What are Cookies?</h2>
            <p>Cookies are small files that the server embeds on the user's computer. Each time the same computer requests a page with a browser, it will send the cookie too.</p>
            <p>Cookies are stored on the client's computer, while sessions use cookies to store a session ID that identifies the user, but the actual data is stored on the server.</p>
        </div>
        
        <!-- Session Example -->
        <div class="box session-data">
            <h2>Session Example</h2>
            
            <?php
            // Check if form is submitted for session
            if(isset($_POST['sessionName'])) {
                // Store session data
                $_SESSION['username'] = $_POST['sessionName'];
                $_SESSION['time'] = date('Y-m-d H:i:s');
            }
            
            // Display session data
            if(isset($_SESSION['username'])) {
                echo "<p>Welcome back, <strong>{$_SESSION['username']}</strong>!</p>";
                echo "<p>Your session started at: <strong>{$_SESSION['time']}</strong></p>";
                echo "<p><a href='?action=logout' class='btn'>Logout (Clear Session)</a></p>";
            } else {
                // Show session form
                echo "
                <form method='post' action=''>
                    <label for='sessionName'>Enter your name for session:</label><br>
                    <input type='text' id='sessionName' name='sessionName' required>
                    <input type='submit' value='Save in Session'>
                </form>";
            }
            
            // Logout action
            if(isset($_GET['action']) && $_GET['action'] == 'logout') {
                // Remove all session variables
                session_unset();
                
                // Destroy the session
                session_destroy();
                
                // Redirect to the same page
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            ?>
        </div>
        
        <!-- Cookie Example -->
        <div class="box cookie-data">
            <h2>Cookie Example</h2>
            
            <?php
            // Check if form is submitted for cookie
            if(isset($_POST['cookieName'])) {
                // Store cookie data (30 days expiry)
                setcookie('user_name', $_POST['cookieName'], time() + (86400 * 30), "/");
                
                // Refresh page to show the cookie
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            
            // Display cookie data
            if(isset($_COOKIE['user_name'])) {
                echo "<p>Cookie remembers you as: <strong>{$_COOKIE['user_name']}</strong></p>";
                
                if(isset($_COOKIE['user_visit'])) {
                    echo "<p>You have visited this page <strong>{$_COOKIE['user_visit']}</strong> times according to cookie.</p>";
                }
                
                echo "<p><a href='?action=clearcookie' class='btn'>Clear Cookies</a></p>";
            } else {
                // Show cookie form
                echo "
                <form method='post' action=''>
                    <label for='cookieName'>Enter your name for cookie:</label><br>
                    <input type='text' id='cookieName' name='cookieName' required>
                    <input type='submit' value='Save in Cookie'>
                </form>";
            }
            
            // Clear cookie action
            if(isset($_GET['action']) && $_GET['action'] == 'clearcookie') {
                // Delete cookies by setting expiration in the past
                setcookie('user_name', '', time() - 3600, "/");
                setcookie('user_visit', '', time() - 3600, "/");
                
                // Redirect to the same page
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            ?>
        </div>
        
        <div class="box">
            <h2>Differences Between Sessions and Cookies</h2>
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <th style="padding: 8px; text-align: left;">Sessions</th>
                    <th style="padding: 8px; text-align: left;">Cookies</th>
                </tr>
                <tr>
                    <td style="padding: 8px;">Stored on the server</td>
                    <td style="padding: 8px;">Stored on the client's computer</td>
                </tr>
                <tr>
                    <td style="padding: 8px;">More secure</td>
                    <td style="padding: 8px;">Less secure</td>
                </tr>
                <tr>
                    <td style="padding: 8px;">Cannot be disabled by user</td>
                    <td style="padding: 8px;">Can be disabled by user</td>
                </tr>
                <tr>
                    <td style="padding: 8px;">Deleted when browser is closed</td>
                    <td style="padding: 8px;">Can persist even after browser is closed</td>
                </tr>
                <tr>
                    <td style="padding: 8px;">Can store more data</td>
                    <td style="padding: 8px;">Limited to about 4KB</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
