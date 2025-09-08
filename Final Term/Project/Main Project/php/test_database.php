<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    $conn = getDbConnection();
    if ($conn) {
        echo "<p style='color:green;'>Database connection successful!</p>";
        echo "<p>Connection Info: " . $conn->host_info . "</p>";
        echo "<p>Server Info: " . $conn->server_info . "</p>";
    } else {
        echo "<p style='color:red;'>Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Test users table
echo "<h2>Testing Users Table</h2>";
try {
    $conn = getDbConnection();
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green;'>Users table exists!</p>";
        
        // Get table structure
        $result = $conn->query("DESCRIBE users");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Count users
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<p>Total users in database: " . $row['count'] . "</p>";
        
        // Check for admin users
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $row = $result->fetch_assoc();
        echo "<p>Admin users in database: " . $row['count'] . "</p>";
        
        if ($row['count'] > 0) {
            // Show admin users (omit passwords)
            $result = $conn->query("SELECT id, name, email, role, created_at, last_login FROM users WHERE role = 'admin'");
            echo "<h3>Admin Users:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created At</th><th>Last Login</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['role'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "<td>" . $row['last_login'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color:red;'>No admin users found! This may cause login issues.</p>";
        }
        
    } else {
        echo "<p style='color:red;'>Users table does not exist!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Test session table if it exists
echo "<h2>Testing Session Storage</h2>";
try {
    $conn = getDbConnection();
    
    // Check if sessions table exists
    $result = $conn->query("SHOW TABLES LIKE 'sessions'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green;'>Sessions table exists!</p>";
        
        // Get table structure
        $result = $conn->query("DESCRIBE sessions");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No dedicated sessions table found. PHP is likely using default session storage.</p>";
    }
    
    // Check user tokens table for "remember me" functionality
    $result = $conn->query("SHOW TABLES LIKE 'user_tokens'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green;'>User tokens table exists for 'remember me' functionality!</p>";
        
        // Get table structure
        $result = $conn->query("DESCRIBE user_tokens");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>No user_tokens table found. 'Remember me' functionality may not work.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Test PHP session configuration
echo "<h2>PHP Session Configuration</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>session.save_handler</td><td>" . ini_get('session.save_handler') . "</td></tr>";
echo "<tr><td>session.save_path</td><td>" . ini_get('session.save_path') . "</td></tr>";
echo "<tr><td>session.use_cookies</td><td>" . ini_get('session.use_cookies') . "</td></tr>";
echo "<tr><td>session.use_only_cookies</td><td>" . ini_get('session.use_only_cookies') . "</td></tr>";
echo "<tr><td>session.name</td><td>" . ini_get('session.name') . "</td></tr>";
echo "<tr><td>session.auto_start</td><td>" . ini_get('session.auto_start') . "</td></tr>";
echo "<tr><td>session.cookie_lifetime</td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
echo "<tr><td>session.cookie_path</td><td>" . ini_get('session.cookie_path') . "</td></tr>";
echo "<tr><td>session.cookie_domain</td><td>" . ini_get('session.cookie_domain') . "</td></tr>";
echo "<tr><td>session.cookie_secure</td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
echo "<tr><td>session.cookie_httponly</td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
echo "<tr><td>session.gc_maxlifetime</td><td>" . ini_get('session.gc_maxlifetime') . "</td></tr>";
echo "</table>";

// Current session info
echo "<h2>Current Session Information</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>Session Status: " . session_status() . " (1=Disabled, 2=None, 3=Active)</p>";
echo "<p>Session Data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Path information
echo "<h2>Path Information</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Path</th><th>Value</th></tr>";
echo "<tr><td>Document Root</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td>Script Filename</td><td>" . $_SERVER['SCRIPT_FILENAME'] . "</td></tr>";
echo "<tr><td>PHP_SELF</td><td>" . $_SERVER['PHP_SELF'] . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . $_SERVER['REQUEST_URI'] . "</td></tr>";
echo "</table>";

// Fix suggestions
echo "<h2>Suggestions for Fixing Login Issues</h2>";
echo "<ol>";
echo "<li>Check that passwords in the database match the expected format (hashed or plain).</li>";
echo "<li>Ensure session.save_path is writable by the web server.</li>";
echo "<li>Verify that session cookies are being set correctly.</li>";
echo "<li>Check for any session_regenerate_id() calls that might be invalidating sessions.</li>";
echo "<li>Make sure headers are not being sent before session_start().</li>";
echo "<li>Check for path issues in redirects.</li>";
echo "<li>Ensure proper user role checking.</li>";
echo "<li>Look for output buffering issues.</li>";
echo "</ol>";

// Close any open database connections
if (isset($conn) && $conn) {
    $conn->close();
}
?>
