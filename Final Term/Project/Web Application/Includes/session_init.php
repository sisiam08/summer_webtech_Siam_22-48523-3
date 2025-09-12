<?php
/**
 * Session Initialization
 * 
 * This file initializes session settings and should be included before session_start()
 * is called in any file that needs session access.
 */

// Load configuration
$configPath = "../Config/config.php";
if (file_exists($configPath)) {
    $config = require $configPath;
    
    // Configure session
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
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
