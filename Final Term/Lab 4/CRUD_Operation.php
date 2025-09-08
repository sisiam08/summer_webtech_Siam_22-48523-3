<html>
<head>
    <title>CRUD Operation</title>
</head>
<body>
    <form action="CRUD_Operation.php" method="post">
        <input type="text" name="name" placeholder="Enter name" required>
        <button type="submit">Submit</button>
    </form>
</body>
</html>


<?php
$servername = "localhost";
$username = "root";
$password = "Siam@MySQL2025";
$database_name = "WebTech_Database_Practice";

$conn = new mysqli($servername, $username, $password);

if($conn->connect_error)
    die("Connection failed: ".$conn->connect_error);


$sql = "CREATE DATABASE if not exists $database_name";

$conn->select_db($database_name);

// Create table
$sql = "CREATE TABLE if not exists users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)";
$conn->query($sql);


// Insert data
if(isset($_POST['name']))
{
    $name = $_POST['name'];
    $insert_sql = "INSERT INTO users (name) VALUES ('$name')";
    $conn->query($insert_sql);
}

// Update data
if(isset($_POST['update_name']) && isset($_POST['update_id']))
{
    $update_name = $_POST['update_name'];
    $update_id = intval($_POST['update_id']);
    $update_sql = "UPDATE users SET name='$update_name' WHERE id=$update_id";
    $conn->query($update_sql);
}

// Delete data
if(isset($_GET['delete_id']))
{
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM users WHERE id=$delete_id";
    $conn->query($delete_sql);
}

// Select data
$select_sql = "SELECT * FROM users";
$result = $conn->query($select_sql);


// Display users with Delete and Update buttons
if($result->num_rows > 0)
{
    // Display as a table for better layout
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Action</th></tr>";
    // Reset result pointer and fetch again
    while($row = $result->fetch_assoc())
    {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['name']."</td>";
        echo "<td>
            <form style='display:inline;' method='get' action='CRUD_Operation.php'>
            <button type='submit' name='delete_id' value='".$row['id']."'>Delete</button>
            </form>
            <form style='display:inline;' method='post' action='CRUD_Operation.php'>
            <input type='hidden' name='update_id' value='".$row['id']."'>
            <input type='text' name='update_name' value='".$row['name']."' required>
            <button type='submit'>Update</button>
            </form>
            </td>";
        echo "</tr>";
        
    }
    echo "</table>";
}

?>
