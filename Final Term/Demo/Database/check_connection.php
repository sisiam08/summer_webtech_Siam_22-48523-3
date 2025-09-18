<?php
// Check database connection
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $user = 'root'; // Default XAMPP user
    $pass = 'Siam@MySQL2025'; // Your actual database password
    $db = 'grocery_store'; // Your database name
    
    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get server info
    $serverInfo = $conn->server_info;
    $hostInfo = $conn->host_info;
    
    echo json_encode([
        'success' => true,
        'message' => 'Connected successfully',
        'server_info' => $serverInfo,
        'host_info' => $hostInfo,
        'database' => $db
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
