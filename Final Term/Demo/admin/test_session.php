<?php
// Start the session
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get database connection
function getDbConnection() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'online_grocery_store';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Output headers for readability
echo "<h2>Session Test Page</h2>";
echo "<p>Use this page to test session functionality and debug login issues.</p>";

// Output session information
echo "<h3>Session Information:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (1=Disabled, 2=None, 3=Active)\n";
echo "Session Name: " . session_name() . "\n";

// Check if session is working
echo "\nSetting test variable in session...\n";
$_SESSION['test_var'] = "This is a test value set at " . date('Y-m-d H:i:s');

// Output all session variables
echo "\nCurrent Session Variables:\n";
print_r($_SESSION);
echo "</pre>";

// Check cookies
echo "<h3>Cookies Information:</h3>";
echo "<pre>";
echo "Cookie Settings:\n";
print_r(session_get_cookie_params());

echo "\nCurrent Cookies:\n";
print_r($_COOKIE);
echo "</pre>";

// Check if user is logged in
echo "<h3>Login Status:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green;'>User is logged in.</p>";
    echo "<pre>";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "User Role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
    echo "Login Time: " . ($_SESSION['login_time'] ?? 'Not set') . "\n";
    echo "Last Activity: " . ($_SESSION['last_activity'] ?? 'Not set') . "\n";
    
    // Fetch user details from database
    if (function_exists('getDbConnection')) {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            echo "\nUser Database Record:\n";
            print_r($result->fetch_assoc());
        } else {
            echo "\nUser not found in database!\n";
        }
        
        $stmt->close();
        $conn->close();
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>User is not logged in.</p>";
}

// Server information
echo "<h3>Server Information:</h3>";
echo "<pre>";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'On' : 'Off') . "\n";
echo "</pre>";

// Provide test links
echo "<h3>Test Links:</h3>";
echo "<ul>";
echo "<li><a href='login.html'>Go to Login Page</a></li>";
echo "<li><a href='../php/logout.php'>Logout</a></li>";
echo "<li><a href='index.php'>Go to Admin Dashboard</a></li>";
echo "<li><a href='../index.php'>Go to Main Site</a></li>";
echo "</ul>";

// Test form to set a session variable
echo "<h3>Test Session Variable:</h3>";
echo "<form method='post' action='test_session.php'>";
echo "<input type='text' name='session_key' placeholder='Session Variable Name'>";
echo "<input type='text' name='session_value' placeholder='Session Variable Value'>";
echo "<input type='submit' value='Set Session Variable'>";
echo "</form>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['session_key']) && 
    isset($_POST['session_value'])) {
    
    $key = $_POST['session_key'];
    $value = $_POST['session_value'];
    
    $_SESSION[$key] = $value;
    
    echo "<p style='color:green;'>Session variable set: {$key} = {$value}</p>";
    echo "<p><a href='test_session.php'>Refresh page</a> to see updated session data.</p>";
}

// Session destruction option
echo "<h3>Destroy Session:</h3>";
echo "<form method='post' action='test_session.php'>";
echo "<input type='hidden' name='destroy_session' value='1'>";
echo "<input type='submit' value='Destroy Current Session' style='background-color:#ff6666;'>";
echo "</form>";

// Process session destruction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destroy_session'])) {
    // Clear session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    echo "<p style='color:green;'>Session destroyed successfully.</p>";
    echo "<p><a href='test_session.php'>Refresh page</a> to start a new session.</p>";
}
?>
