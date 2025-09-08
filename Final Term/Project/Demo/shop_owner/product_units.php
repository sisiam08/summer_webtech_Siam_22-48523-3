<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    // Redirect to login page if not authenticated
    header("Location: login.html");
    exit;
}

// Database connection
$host = '127.0.0.1';
$username = 'root';
$password = 'Siam@MySQL2025';
$database = 'grocery_store';
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get all products with their unit values
$sql = "SELECT id, name, unit FROM products ORDER BY id DESC";
$result = $conn->query($sql);

echo "<h1>Product Units Manager</h1>";
echo "<p>This page shows all products in the database with their unit values. You can use this to identify and standardize unit values across your products.</p>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Unit</th><th>Unit Length</th><th>Unicode Analysis</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['unit']) . "</td>";
        echo "<td>" . strlen($row['unit']) . "</td>";
        
        // Analyze each character in the unit string for Unicode issues
        $unit = $row['unit'];
        $charAnalysis = "";
        for ($i = 0; $i < strlen($unit); $i++) {
            $char = $unit[$i];
            $charAnalysis .= "Position $i: '" . $char . "' (ASCII: " . ord($char) . ")<br>";
        }
        
        echo "<td>" . $charAnalysis . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

echo "<h2>Available Unit Options in Form</h2>";
echo "<ul>";
echo "<li>kg - Kilogram</li>";
echo "<li>g - Gram</li>";
echo "<li>l - Liter</li>";
echo "<li>ml - Milliliter</li>";
echo "<li>piece - Piece</li>";
echo "<li>packet - Packet</li>";
echo "<li>box - Box</li>";
echo "<li>dozen - Dozen</li>";
echo "<li>bottle - Bottle</li>";
echo "</ul>";

$conn->close();
?>
