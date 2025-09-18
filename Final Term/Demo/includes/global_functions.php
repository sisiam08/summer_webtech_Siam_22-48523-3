<?php
/**
 * Global Functions
 * 
 * This file contains globally accessible functions that can be used across the application.
 */

// Make sure we have access to the config
if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation
     * 
     * @param string|null $key The configuration key to get using dot notation (e.g., 'site.name')
     * @param mixed $default The default value to return if the key is not found
     * @return mixed The configuration value or the default
     */
    function config($key = null, $default = null) {
        static $config = null;
        
        if ($config === null) {
            $configPath = __DIR__ . '/../config/config.php';
            
            // Check if file exists to prevent errors
            if (file_exists($configPath)) {
                $config = require $configPath;
            } else {
                // Fallback config with minimal settings if file doesn't exist
                $config = [
                    'site' => [
                        'name' => 'Online Grocery Store',
                        'currency_symbol' => '৳',
                    ],
                    'database' => [
                        'host' => 'localhost',
                        'name' => 'grocery_store',
                        'user' => 'root',
                        'password' => 'Siam@MySQL2025',
                    ]
                ];
            }
        }
        
        if ($key === null) {
            return $config;
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
}

/**
 * Get the site name
 * 
 * @return string The site name
 */
function getSiteName() {
    return config('site.name');
}

/**
 * Format a page title with the site name
 * 
 * @param string $title The page title
 * @return string The formatted title
 */
function formatTitle($title) {
    $siteName = config('site.name');
    $separator = config('site.title_separator');
    
    return $title . $separator . $siteName;
}

/**
 * Format a price with the currency symbol
 * 
 * @param float $price The price to format
 * @return string The formatted price with currency symbol
 */
// function formatPrice($price) {
//     // Get currency symbol from config if available
//     if (function_exists('config')) {
//         $symbol = config('site.currency_symbol', '৳');
//     } else {
//         // Fallback for backward compatibility
//         $symbol = '৳';
//     }
//     return $symbol . ' ' . number_format($price, 2);
// }

/**
 * Get the site URL
 * 
 * @param string $path The path to append to the URL
 * @return string The complete URL
 */
function siteUrl($path = '') {
    $baseUrl = config('site.url');
    $path = ltrim($path, '/');
    
    return $baseUrl . ($path ? "/{$path}" : '');
}

/**
 * Get the upload path for a specific type
 * 
 * @param string $type The type of upload (e.g., 'products', 'users')
 * @return string The upload path
 */
function uploadPath($type = '') {
    $uploadsPath = UPLOADS_PATH;
    $type = trim($type, '/');
    
    return $uploadsPath . ($type ? "/{$type}" : '');
}

/**
 * Check if the current user is an admin
 * 
 * @return bool True if the user is an admin, false otherwise
 * 
 * Note: This function has been moved to session.php to avoid duplication
 */
// function isAdmin() {
//     return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
// }

/**
 * Check if the current user is a shop owner
 * 
 * @return bool True if the user is a shop owner, false otherwise
 * 
 * Note: This function has been moved to session.php to avoid duplication
 */
// function isShopOwner() {
//     return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';
// }

/**
 * Get default delivery charge
 * 
 * @return float The default delivery charge
 */
function getDefaultDeliveryCharge() {
    return config('delivery.default_charge', 60);
}

/**
 * Get the free delivery threshold
 * 
 * @return float The free delivery threshold
 */
function getFreeDeliveryThreshold() {
    return config('delivery.free_delivery_threshold', 1500);
}

/**
 * Get available payment methods
 * 
 * @return array The payment methods
 */
function getPaymentMethods() {
    return config('payment.methods', [
        'cash' => 'Cash on Delivery'
    ]);
}

/**
 * Get default payment method
 * 
 * @return string The default payment method
 */
function getDefaultPaymentMethod() {
    return config('payment.default_method', 'cash');
}

/**
 * Get admin email
 * 
 * @return string The admin email
 */
function getAdminEmail() {
    return config('admin.email', 'admin@grocerystore.com');
}

/**
 * Get site support email
 * 
 * @return string The support email
 */
function getSupportEmail() {
    return config('site.email', 'support@grocerystore.com');
}

/**
 * Get site support phone
 * 
 * @return string The support phone number
 */
function getSupportPhone() {
    return config('site.phone', '+1234567890');
}

/**
 * Get tax percentage
 * 
 * @return float The tax percentage
 */
function getTaxPercentage() {
    return config('cart.tax_percentage', 5);
}

/**
 * Get minimum order amount
 * 
 * @return float The minimum order amount
 */
function getMinOrderAmount() {
    return config('cart.min_order_amount', 500);
}

/**
 * Get default shop owner commission
 * 
 * @return float The default shop owner commission percentage
 */
function getDefaultCommission() {
    return config('shop_owner.default_commission', 10);
}

/**
 * Format a date according to the site's date format
 * 
 * @param string|int $date The date to format (timestamp or date string)
 * @return string The formatted date
 */
function formatDate($date) {
    $format = config('site.date_format', 'F j, Y');
    
    if (is_numeric($date)) {
        return date($format, $date);
    }
    
    return date($format, strtotime($date));
}

/**
 * Format a time according to the site's time format
 * 
 * @param string|int $time The time to format (timestamp or time string)
 * @return string The formatted time
 */
function formatTime($time) {
    $format = config('site.time_format', 'g:i a');
    
    if (is_numeric($time)) {
        return date($format, $time);
    }
    
    return date($format, strtotime($time));
}

/**
 * Format a datetime according to the site's date and time format
 * 
 * @param string|int $datetime The datetime to format (timestamp or datetime string)
 * @return string The formatted datetime
 */
function formatDateTime($datetime) {
    $dateFormat = config('site.date_format', 'F j, Y');
    $timeFormat = config('site.time_format', 'g:i a');
    $format = $dateFormat . ' ' . $timeFormat;
    
    if (is_numeric($datetime)) {
        return date($format, $datetime);
    }
    
    return date($format, strtotime($datetime));
}
