/**
 * Session management script to be included in all dashboard pages.
 * This script checks if the user is authenticated and ensures the session is maintained.
 * It also provides utility functions for session handling.
 */

// Start the session check if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    // Start session with proper settings for better security
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', 'On');
    ini_set('session.cookie_httponly', 'On');
    session_start();
}

// Set headers to prevent caching for all pages that include this script
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/**
 * Check if the current user is authenticated as a shop owner
 * 
 * @return bool True if authenticated, false otherwise
 */
function isShopOwnerAuthenticated() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'shop_owner' &&
           isset($_SESSION['shop_id']);
}

/**
 * Redirects to login page if not authenticated
 */
function requireShopOwnerAuth() {
    if (!isShopOwnerAuthenticated()) {
        // Not authenticated, redirect to login
        header("Location: login.html");
        exit;
    }
}

/**
 * Get shop owner information from session
 * 
 * @return array Shop owner information
 */
function getShopOwnerInfo() {
    if (!isShopOwnerAuthenticated()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'Shop Owner',
        'user_email' => $_SESSION['user_email'] ?? '',
        'shop_id' => $_SESSION['shop_id'],
        'shop_name' => $_SESSION['shop_name'] ?? 'Shop'
    ];
}

// Fix for cross-origin issues with AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
