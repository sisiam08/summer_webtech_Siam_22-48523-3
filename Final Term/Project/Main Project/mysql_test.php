<?php
// Check MySQL connectivity

echo "<h1>MySQL Connection Test</h1>";

// Test different connection methods
echo "<h2>Testing MySQL Connection</h2>";

// Method 1: Default localhost
echo "<h3>Method 1: localhost</h3>";
try {
    $conn1 = @mysqli_connect('localhost', 'root', '');
    if ($conn1) {
        echo "<p style='color:green'>✓ Successfully connected to MySQL at localhost</p>";
        mysqli_close($conn1);
    } else {
        echo "<p style='color:red'>✗ Failed to connect to MySQL at localhost</p>";
        echo "<p>Error: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Method 2: IP address
echo "<h3>Method 2: 127.0.0.1</h3>";
try {
    $conn2 = @mysqli_connect('127.0.0.1', 'root', '');
    if ($conn2) {
        echo "<p style='color:green'>✓ Successfully connected to MySQL at 127.0.0.1</p>";
        mysqli_close($conn2);
    } else {
        echo "<p style='color:red'>✗ Failed to connect to MySQL at 127.0.0.1</p>";
        echo "<p>Error: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Method 3: With port specification
echo "<h3>Method 3: localhost:3306</h3>";
try {
    $conn3 = @mysqli_connect('localhost', 'root', '', '', 3306);
    if ($conn3) {
        echo "<p style='color:green'>✓ Successfully connected to MySQL at localhost:3306</p>";
        mysqli_close($conn3);
    } else {
        echo "<p style='color:red'>✗ Failed to connect to MySQL at localhost:3306</p>";
        echo "<p>Error: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Method 4: With port specification on IP
echo "<h3>Method 4: 127.0.0.1:3306</h3>";
try {
    $conn4 = @mysqli_connect('127.0.0.1', 'root', '', '', 3306);
    if ($conn4) {
        echo "<p style='color:green'>✓ Successfully connected to MySQL at 127.0.0.1:3306</p>";
        mysqli_close($conn4);
    } else {
        echo "<p style='color:red'>✗ Failed to connect to MySQL at 127.0.0.1:3306</p>";
        echo "<p>Error: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Check MySQL service status on Windows
echo "<h2>MySQL Service Status (Windows)</h2>";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // We're on Windows
    echo "<pre>";
    echo shell_exec('sc query mysql');
    echo shell_exec('sc query "mysql80"');  // For MySQL 8.0
    echo shell_exec('sc query "MySQL"');    // Another possible name
    echo "</pre>";
} else {
    echo "<p>Not running on Windows. Cannot check service status.</p>";
}

// System information
echo "<h2>System Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Operating System: " . PHP_OS . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Installed PHP extensions
echo "<h2>Installed PHP Extensions</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

// Connection settings suggestions
echo "<h2>Suggested Next Steps</h2>";
echo "<ol>";
echo "<li>Install MySQL if not already installed</li>";
echo "<li>Start MySQL service from Control Panel or command line</li>";
echo "<li>Make sure MySQL is listening on the default port (3306)</li>";
echo "<li>Check if MySQL username and password are correct</li>";
echo "<li>Try connecting with a database client to verify connectivity</li>";
echo "</ol>";
?>
