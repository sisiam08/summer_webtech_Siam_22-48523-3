<?php
// Script to clean up debugging and testing files in the shop_owner directory

// Files to remove
$filesToRemove = [
    'login_debug.php'
];

// Check if the user has confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Remove files
    $removed = 0;
    $failed = 0;
    
    foreach ($filesToRemove as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            if (unlink($path)) {
                echo "<p style='color:green'>✓ Removed: $file</p>";
                $removed++;
            } else {
                echo "<p style='color:red'>✗ Failed to remove: $file</p>";
                $failed++;
            }
        } else {
            echo "<p style='color:orange'>⚠ File not found: $file</p>";
        }
    }
    
    echo "<p>Cleanup completed. Removed $removed files. Failed to remove $failed files.</p>";
    echo "<p><a href='index.html'>Return to Shop Owner Dashboard</a></p>";
} else {
    // Show confirmation page
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Shop Owner Directory Cleanup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            h1 { color: #333; }
            ul { padding-left: 20px; }
            .btn { 
                display: inline-block;
                padding: 8px 15px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-right: 10px;
            }
            .btn-danger {
                background-color: #dc3545;
            }
            .btn:hover {
                opacity: 0.9;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Shop Owner Directory Cleanup</h1>
            <p>This will remove the following debugging and test files from the shop_owner directory:</p>
            <ul>";
    
    foreach ($filesToRemove as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "<li>$file</li>";
        }
    }
    
    echo "</ul>
            <p>Are you sure you want to proceed?</p>
            <a href='?confirm=yes' class='btn btn-danger'>Yes, Remove Files</a>
            <a href='index.html' class='btn'>Cancel</a>
        </div>
    </body>
    </html>";
}
?>
