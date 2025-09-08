<?php
// Display PHP information
echo "<h1>PHP is working!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Name: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Link to project files
echo "<h2>Links to your project files:</h2>";
echo "<ul>";
echo "<li><a href='/test.php'>Test Path Info File</a></li>";
echo "<li><a href='/project/shop_owner/get_path_info.php'>Project Path Info (if symlink works)</a></li>";
echo "</ul>";

// Test MySQL connection
echo "<h2>Testing MySQL Connection</h2>";
try {
    $conn = new mysqli('127.0.0.1', 'root', 'Siam@MySQL2025', 'grocery_store');
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>MySQL Connection Failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>MySQL Connection Successful!</p>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
