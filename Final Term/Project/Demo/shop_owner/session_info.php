<?php
// Start session
session_start();

// Set debug info
$sessionInfo = [
    'id' => session_id(),
    'name' => session_name(),
    'cookie_params' => session_get_cookie_params(),
    'session_data' => $_SESSION
];

// Set header to return JSON
header('Content-Type: application/json');
echo json_encode($sessionInfo, JSON_PRETTY_PRINT);
?>
