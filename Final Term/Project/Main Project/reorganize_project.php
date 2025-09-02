<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Project Reorganization Tool</h1>";

// Define the project root directory
$root_dir = __DIR__;

// Create new directory structure if not exists
$directories = [
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'auth',
    'config',
    'database/migrations',
    'database/seeds',
    'includes',
    'uploads',
    'api',
    'api/admin',
    'api/customer',
    'api/delivery',
    'api/shop_owner'
];

echo "<h2>Creating Directory Structure</h2>";
echo "<ul>";

foreach ($directories as $dir) {
    $path = $root_dir . '/' . $dir;
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            echo "<li>Created directory: $dir</li>";
        } else {
            echo "<li style='color:red'>Failed to create directory: $dir</li>";
        }
    } else {
        echo "<li>Directory already exists: $dir</li>";
    }
}

echo "</ul>";

// Define file mappings [source => destination]
$file_mappings = [
    // Core files
    'php/db_connection.php' => 'config/database.php',
    'php/functions.php' => 'includes/functions.php',
    'helpers.php' => 'includes/helpers.php',
    
    // Authentication files
    'php/login_process.php' => 'auth/login_process.php',
    'php/register_process.php' => 'auth/register_process.php',
    'php/logout.php' => 'auth/logout.php',
    'php/check_login.php' => 'auth/check_login.php',
    
    // CSS and JS files
    'css/style.css' => 'assets/css/style.css',
    'js/script.js' => 'assets/js/script.js',
    
    // Database files
    'database.sql' => 'database/database.sql',
    'database/migration.php' => 'database/migrations/initial_migration.php'
];

echo "<h2>Copying Core Files</h2>";
echo "<ul>";

foreach ($file_mappings as $source => $destination) {
    $source_path = $root_dir . '/' . $source;
    $dest_path = $root_dir . '/' . $destination;
    
    if (file_exists($source_path)) {
        if (copy($source_path, $dest_path)) {
            echo "<li>Copied: $source → $destination</li>";
        } else {
            echo "<li style='color:red'>Failed to copy: $source → $destination</li>";
        }
    } else {
        echo "<li style='color:orange'>Source file does not exist: $source</li>";
    }
}

echo "</ul>";

// Copy API files for each role
$roles = ['admin', 'customer', 'delivery', 'shop_owner'];

echo "<h2>Copying API Files</h2>";

foreach ($roles as $role) {
    $source_dir = $root_dir . '/php/' . $role;
    $dest_dir = $root_dir . '/api/' . $role;
    
    if (file_exists($source_dir) && is_dir($source_dir)) {
        echo "<h3>$role API Files</h3>";
        echo "<ul>";
        
        $files = scandir($source_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_file($source_dir . '/' . $file)) {
                $source_file = $source_dir . '/' . $file;
                $dest_file = $dest_dir . '/' . $file;
                
                if (copy($source_file, $dest_file)) {
                    echo "<li>Copied: php/$role/$file → api/$role/$file</li>";
                } else {
                    echo "<li style='color:red'>Failed to copy: php/$role/$file → api/$role/$file</li>";
                }
            }
        }
        
        echo "</ul>";
    } else {
        echo "<p>No $role directory found</p>";
    }
}

// Create new session.php file
$session_content = '<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION[\'user_id\']);
}

// Function to check user role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION[\'user_role\']) && $_SESSION[\'user_role\'] === $role;
}

// Role-specific check functions
function isAdmin() {
    return hasRole(\'admin\');
}

function isShopOwner() {
    return hasRole(\'shop_owner\');
}

function isDelivery() {
    return hasRole(\'delivery\');
}

function isCustomer() {
    return hasRole(\'customer\');
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION[\'user_id\'] ?? null;
}

// Function to get current user role
function getCurrentUserRole() {
    return $_SESSION[\'user_role\'] ?? null;
}
?>';

$session_file = $root_dir . '/includes/session.php';
if (file_put_contents($session_file, $session_content)) {
    echo "<p>Created new session.php file</p>";
} else {
    echo "<p style='color:red'>Failed to create session.php file</p>";
}

// Create constants.php file
$constants_content = '<?php
// Application constants

// Site information
define(\'SITE_NAME\', \'Online Grocery Store\');
define(\'SITE_VERSION\', \'1.0.0\');

// Path constants
define(\'ROOT_PATH\', dirname(__DIR__));
define(\'ASSETS_PATH\', ROOT_PATH . \'/assets\');
define(\'UPLOADS_PATH\', ROOT_PATH . \'/uploads\');

// User roles
define(\'ROLE_ADMIN\', \'admin\');
define(\'ROLE_SHOP_OWNER\', \'shop_owner\');
define(\'ROLE_DELIVERY\', \'delivery\');
define(\'ROLE_CUSTOMER\', \'customer\');

// Order statuses
define(\'ORDER_PENDING\', \'pending\');
define(\'ORDER_PROCESSING\', \'processing\');
define(\'ORDER_SHIPPED\', \'shipped\');
define(\'ORDER_DELIVERED\', \'delivered\');
define(\'ORDER_CANCELLED\', \'cancelled\');
?>';

$constants_file = $root_dir . '/config/constants.php';
if (file_put_contents($constants_file, $constants_content)) {
    echo "<p>Created new constants.php file</p>";
} else {
    echo "<p style='color:red'>Failed to create constants.php file</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Update include/require paths in all files</li>";
echo "<li>Test each panel functionality</li>";
echo "<li>Remove duplicate/temporary files</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> This script only copies files to the new structure. The original files are still intact. After confirming everything works, you can remove the redundant files.</p>";
?>
