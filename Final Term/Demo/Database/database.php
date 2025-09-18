<?php
// Database connection file

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = 'Siam@MySQL2025';
$db_name = 'grocery_store';
$db_port = 3306;

// Create connection for global use
try {
    // First try to connect without database selection
    $conn = mysqli_connect($db_host, $db_user, $db_password, '', $db_port);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // Check if database exists, if not create it
    $db_selected = mysqli_select_db($conn, $db_name);
    if (!$db_selected) {
        $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
        mysqli_query($conn, $sql);
        mysqli_select_db($conn, $db_name);
    }
    
    // Now reconnect with the database
    $conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Function to get a PDO connection (for use with prepare statements)
function connectDB() {
    global $db_host, $db_user, $db_password, $db_name, $db_port;
    
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;port=$db_port";
        $pdo = new PDO($dsn, $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("PDO Connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Helper functions

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
    
    // Check if $conn is a PDO object
    if ($conn instanceof PDO) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("PDO fetchOne error: " . $e->getMessage());
            return null;
        }
    } else {
        // Use mysqli
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
    }
    
    return null;
}

// Function to sanitize data
function sanitize($data) {
    global $conn;
    
    if (is_array($data)) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = sanitize($value);
        }
        return $sanitized;
    } else {
        if ($conn) {
            return mysqli_real_escape_string($conn, trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
        } else {
            return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
        }
    }
}
?>
