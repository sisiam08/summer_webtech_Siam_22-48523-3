<?php
// Check if PHP is running correctly and if the file is accessible
echo "PHP is working correctly!\n";

// Check if the required files exist
$files = [
    'config/database.php',
    'includes/functions.php',
    'includes/shop_functions.php',
    'includes/cart_utils.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "File exists: $file\n";
    } else {
        echo "File DOES NOT exist: $file\n";
    }
}

// Try to include each file and report any errors
foreach ($files as $file) {
    echo "Attempting to include $file...\n";
    try {
        @include_once $file;
        echo "Successfully included $file\n";
    } catch (Exception $e) {
        echo "Error including $file: " . $e->getMessage() . "\n";
    }
}

// Check if key functions exist
$functions = [
    'getProductById',
    'normalizeCartStructure',
    'getCartItemsByShop'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "Function exists: $function\n";
    } else {
        echo "Function DOES NOT exist: $function\n";
    }
}
