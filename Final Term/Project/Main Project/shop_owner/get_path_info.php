<?php
// Show the server path and suggest URLs
echo "<h1>Server Path Information</h1>";

// Current file path
echo "<p><strong>Current file path:</strong> " . __FILE__ . "</p>";

// Document root
echo "<p><strong>Document root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Suggested URLs
echo "<h2>Possible URLs to access this file:</h2>";
echo "<ul>";

// Remove document root from current path to get relative web path
$relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__);
$relativePath = str_replace('\\', '/', $relativePath); // Convert backslashes to forward slashes

// Option 1: Direct from document root
echo "<li><a href='" . $relativePath . "' target='_blank'>http://localhost" . $relativePath . "</a></li>";

// Check for common subdirectory setups
$pathParts = explode('/', trim($relativePath, '/'));
if (count($pathParts) > 1) {
    // Option 2: If project is in a subdirectory
    $subdirPath = '/' . $pathParts[0] . '/' . $pathParts[1];
    echo "<li><a href='" . $subdirPath . "' target='_blank'>http://localhost" . $subdirPath . "</a> (if in subdirectory)</li>";
}

echo "</ul>";

// List all files in the current directory
echo "<h2>Files in this directory:</h2>";
echo "<ul>";
$files = scandir(dirname(__FILE__));
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";

// Link to the db_test.php file
echo "<h2>Direct link to db_test.php:</h2>";
$dbTestPath = dirname(__FILE__) . '/db_test.php';
if (file_exists($dbTestPath)) {
    $dbTestRelative = str_replace($_SERVER['DOCUMENT_ROOT'], '', $dbTestPath);
    $dbTestRelative = str_replace('\\', '/', $dbTestRelative);
    echo "<p><a href='" . $dbTestRelative . "' target='_blank'>Click here to run the database test</a></p>";
} else {
    echo "<p style='color: red;'>db_test.php file not found in this directory!</p>";
}
?>
