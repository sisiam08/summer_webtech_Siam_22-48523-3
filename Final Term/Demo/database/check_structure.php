<?php
// Connect to database directly without session checks
$host = 'localhost';
$user = 'root'; // Default XAMPP user
$pass = 'Siam@MySQL2025'; // Your actual database password
$db = 'grocery_store'; // Your database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get table structure
$tables = [];
$sql = "SHOW TABLES";
$result = $conn->query($sql);

while ($row = $result->fetch_row()) {
    $tableName = $row[0];
    $tables[$tableName] = [];
    
    $columnSql = "SHOW COLUMNS FROM $tableName";
    $columnResult = $conn->query($columnSql);
    
    while ($columnRow = $columnResult->fetch_assoc()) {
        $tables[$tableName][] = $columnRow;
    }
}

// Output as JSON
header('Content-Type: application/json');
echo json_encode($tables, JSON_PRETTY_PRINT);
?>
