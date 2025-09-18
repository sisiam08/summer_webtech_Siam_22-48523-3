<?php
/**
 * Main Configuration File
 * 
 * This file contains all global configuration settings for the application.
 * Including database credentials, site information, and other constants.
 */

// Define base paths
// define('BASE_PATH', dirname(__DIR__));
// define('UPLOADS_PATH', BASE_PATH . '/uploads');
// define('LOGS_PATH', BASE_PATH . '/logs');

// Site Information
$config = [
    // Platform Information
    'site' => [
        'name' => 'Nitto Proyojon',
        'title_separator' => ' | ',
        'tagline' => 'Fresh groceries delivered to your doorstep',
        'description' => 'Online grocery store with fresh products delivered directly to your home',
        'keywords' => 'grocery, online, fresh, delivery, food, vegetables, fruits',
        'url' => 'http://localhost:8000',
        'email' => 'support@grocerystore.com',
        'phone' => '+880123456789',
        'currency' => 'TK',
        'currency_symbol' => '৳',
        'date_format' => 'F j, Y',
        'time_format' => 'g:i a',
    ],
    
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'name' => 'grocery_store',
        'user' => 'root',
        'password' => 'Siam@MySQL2025',
        'charset' => 'utf8mb4',
        'port' => 3306,
    ],
    
    // Admin Configuration
    'admin' => [
        'email' => 'admin@example.com',
        'default_password' => 'admin123',
        // 'pagination_limit' => 10,
    ],
    
    // Shop Owner Configuration
    'shop_owner' => [
        // 'pagination_limit' => 10,
        // 'default_commission' => 10, // percentage
    ],
    
    // User Configuration
    'user' => [
        // 'default_profile_image' => 'default-user.png',
        // 'min_password_length' => 8,
    ],
    
    // Product Configuration
    'product' => [
        // 'default_image' => 'no-image.jpg',
        // 'image_max_size' => 2097152, // 2MB in bytes
        // 'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
        // 'thumbnails' => [
        //     'small' => ['width' => 100, 'height' => 100],
        //     'medium' => ['width' => 300, 'height' => 300],
        //     'large' => ['width' => 600, 'height' => 600],
        // ],
    ],
    
    // Cart Configuration
    'cart' => [
        // 'min_order_amount' => 100,
        // 'tax_percentage' => 0,
    ],
    
    // Delivery Configuration
    'delivery' => [
        // 'default_charge' => 60,
        // 'free_delivery_threshold' => 1000,
    ],
    
    // Payment Configuration
    'payment' => [
        // 'methods' => [
        //     'cash' => 'Cash on Delivery',
        //     'bkash' => 'bKash',
        //     'nagad' => 'Nagad',
        //     'rocket' => 'Rocket',
        // ],
        // 'default_method' => 'cash',
    ],
    
    // Email Configuration
    'email' => [
        // 'from_email' => 'noreply@example.com',
        // 'from_name' => 'Nitto Proyojon',
        // 'smtp_host' => 'smtp.mailtrap.io',
        // 'smtp_port' => 2525,
        // 'smtp_user' => 'your_mailtrap_user',
        // 'smtp_pass' => 'your_mailtrap_password',
        // 'smtp_secure' => 'tls',
    ],
    
    // Error Handling
    'errors' => [
        'display_errors' => true
    ],
    
    // Session Configuration
    'session' => [
        // 'name' => 'grocery_session',
        // 'lifetime' => 86400, // 24 hours in seconds
        // 'path' => '/',
        // 'domain' => '',
        // 'secure' => false,
        // 'httponly' => true,
    ],
];

// Function to get config values
// function config($key = null, $default = null) {
//     global $config;
    
//     if ($key === null) {
//         return $config;
//     }
    
//     $keys = explode('.', $key);
//     $value = $config;
    
//     foreach ($keys as $segment) {
//         if (!isset($value[$segment])) {
//             return $default;
//         }
//         $value = $value[$segment];
//     }
    
//     return $value;
// }

// // Set error reporting based on config
// if (config('errors.display_errors')) {
//     ini_set('display_errors', 1);
//     error_reporting(E_ALL);
// } else {
//     ini_set('display_errors', 0);
//     error_reporting(0);
// }

// if (config('errors.log_errors')) {
//     ini_set('log_errors', 1);
//     ini_set('error_log', config('errors.error_log_file'));
// }

// // Make sure the logs directory exists
// if (!file_exists(LOGS_PATH)) {
//     mkdir(LOGS_PATH, 0755, true);
// }

// Create a function to configure session - this will be called from outside
$config['configureSession'] = function() use ($config) {
    if (session_status() == PHP_SESSION_NONE) {
        session_name($config['session']['name']);
        session_set_cookie_params(
            $config['session']['lifetime'],
            $config['session']['path'],
            $config['session']['domain'],
            $config['session']['secure'],
            $config['session']['httponly']
        );
    }
};

// Return the config array
return $config;
