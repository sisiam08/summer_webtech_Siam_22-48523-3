<?php
// Cleanup script for Main Project folder
// This script removes temporary, unnecessary, and testing files

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define the project root directory
$projectRoot = __DIR__;

// Files to keep (essential for project functionality)
$filesToKeep = [
    // Core files
    'index.php',
    'index.html',
    'account.php',
    'account.html',
    'cart.php',
    'cart.html',
    'checkout.php',
    'checkout.html',
    'login.php',
    'login.html',
    'logout.php',
    'register.php',
    'register.html',
    'products.php',
    'products.html',
    'order_confirmation.php',
    'order_confirmation.html',
    'multi_shop_checkout.php',
    'helpers.php',
    'script.js',
    
    // Important directories (will check individually)
    'admin/',
    'api/',
    'assets/',
    'auth/',
    'config/',
    'css/',
    'customer/',
    'database/',
    'delivery/',
    'images/',
    'includes/',
    'js/',
    'php/',
    'shop_owner/',
    'uploads/',
    
    // Documentation files
    'README.md',
    
    // Essential scripts
    'setup_database.php',
    'cleanup.php',
    'cleanup.bat'
];

// Files to remove (temporary, testing, or unnecessary files)
$filesToRemove = [
    // Testing files
    'test.php',
    'test_db_connection.php',
    'test_shop_login.php',
    'login_test.php',
    'db_test.php',
    'php_test.php',
    'shop_owner_login_test.php',
    'test_shop_owner_login.php',
    
    // Checking/debugging files
    'check_cart_table.php',
    'check_database.php',
    'check_database_basic.php',
    'check_multi_shop.php',
    'check_shops.php',
    'check_shop_owners.php',
    'db_check.php',
    
    // Temporary or duplicate files
    'new_index.php',
    'new_login.html',
    'create_shop_owner_account.php',
    'create_test_account.php',
    'fix_shop_owner.php',
    'fix_shop_owner_comprehensive.php',
    'repair_shop_owner_system.php',
    'shop_owner_fix_result.php',
    'setup_database_with_test_account.php',
    
    // Temporary batch files
    'create_sample_shops.bat',
    'setup_shops_demo.bat',
    'update_multi_shop_db.bat'
];

// Temporary or unnecessary documentation files
$docsToRemove = [
    'admin_login_instructions.md',
    'MULTI_SHOP_TROUBLESHOOTING.md'
];

// Initialize counters
$removedCount = 0;
$failedCount = 0;
$removedFiles = [];
$failedFiles = [];

// Function to remove a file
function removeFile($path) {
    global $removedCount, $failedCount, $removedFiles, $failedFiles;
    
    if (file_exists($path)) {
        if (unlink($path)) {
            $removedCount++;
            $removedFiles[] = $path;
            return true;
        } else {
            $failedCount++;
            $failedFiles[] = $path;
            return false;
        }
    }
    return false;
}

// Start HTML output
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Project Cleanup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        ul {
            padding-left: 20px;
        }
        .actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Project Cleanup</h1>
        <p>This script removes temporary, testing, and unnecessary files to clean up the project directory.</p>
";

// Check if the user confirmed cleanup
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Remove files
    echo "<h2>Removing Files...</h2>";
    
    foreach ($filesToRemove as $file) {
        $fullPath = $projectRoot . '/' . $file;
        if (removeFile($fullPath)) {
            echo "<p class='success'>✓ Removed: $file</p>";
        } else {
            if (file_exists($fullPath)) {
                echo "<p class='error'>✗ Failed to remove: $file</p>";
            } else {
                echo "<p class='warning'>⚠ File not found: $file</p>";
            }
        }
    }
    
    // Remove documentation files
    foreach ($docsToRemove as $doc) {
        $fullPath = $projectRoot . '/' . $doc;
        if (removeFile($fullPath)) {
            echo "<p class='success'>✓ Removed doc: $doc</p>";
        }
    }
    
    // Summary
    echo "<h2>Cleanup Summary</h2>";
    echo "<p>Total files removed: <strong>$removedCount</strong></p>";
    if ($failedCount > 0) {
        echo "<p class='error'>Failed to remove $failedCount files.</p>";
    }
    
    echo "<div class='actions'>";
    echo "<a href='index.php' class='btn'>Go to Home Page</a>";
    echo "</div>";
} else {
    // Show confirmation page
    echo "<h2>Files to be Removed</h2>";
    echo "<p>The following files will be removed:</p>";
    
    // Testing files
    echo "<h3>Testing Files:</h3>";
    echo "<ul>";
    foreach ($filesToRemove as $file) {
        if (file_exists($projectRoot . '/' . $file)) {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
    
    // Documentation files
    echo "<h3>Documentation Files:</h3>";
    echo "<ul>";
    foreach ($docsToRemove as $doc) {
        if (file_exists($projectRoot . '/' . $doc)) {
            echo "<li>$doc</li>";
        }
    }
    echo "</ul>";
    
    echo "<div class='actions'>";
    echo "<a href='?confirm=yes' class='btn'>Confirm Cleanup</a> ";
    echo "<a href='index.php' class='btn' style='background-color: #6c757d;'>Cancel</a>";
    echo "</div>";
}

echo "
        <div class='footer'>
            <p>Project Cleanup Script &copy; " . date('Y') . "</p>
        </div>
    </div>
</body>
</html>";
?>
