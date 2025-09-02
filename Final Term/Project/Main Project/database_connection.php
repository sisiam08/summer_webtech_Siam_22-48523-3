<?php
// Database connection file - Simple procedural approach

// Database configuration
$db_host = '127.0.0.1'; // Using IP instead of 'localhost'
$db_user = 'root';      // Change this if your MySQL username is different
$db_password = 'Siam@MySQL2025';      // Add your MySQL password here if there is one
$db_name = 'grocery_store';
$db_port = 3306; // Default MySQL port

// Create connection with error handling
try {
    // First, try to connect without selecting a database (in case it doesn't exist yet)
    $conn = mysqli_connect($db_host, $db_user, $db_password, '', $db_port);
    
    // Check if the database exists, if not create it
    if ($conn) {
        // Try to select the database
        $db_selected = mysqli_select_db($conn, $db_name);
        
        if (!$db_selected) {
            // Create the database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
            mysqli_query($conn, $sql);
            mysqli_select_db($conn, $db_name);
        }
    }
} catch (Exception $e) {
    die("<h1>Database Connection Error</h1>
         <p>Could not connect to MySQL. Please make sure:</p>
         <ol>
            <li>MySQL server is running</li>
            <li>The username and password are correct</li>
            <li>The server is accessible at $db_host:$db_port</li>
         </ol>
         <p>Error details: " . $e->getMessage() . "</p>
         <p>If you're using XAMPP, please start MySQL from the XAMPP Control Panel.</p>");
}

// Check connection
if (!$conn) {
    die("<h1>Database Connection Error</h1>
         <p>Could not connect to MySQL. Please make sure:</p>
         <ol>
            <li>MySQL server is running</li>
            <li>The username and password are correct</li>
            <li>The server is accessible at $db_host:$db_port</li>
         </ol>
         <p>Error details: " . mysqli_connect_error() . "</p>
         <p>If you're using XAMPP, please start MySQL from the XAMPP Control Panel.</p>");
}

// Function to execute queries
function executeQuery($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Function to fetch all records
function fetchAll($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    $data = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to fetch a single record
function fetchOne($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Function to safely prepare data for database operations
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, $data);
}
?>
