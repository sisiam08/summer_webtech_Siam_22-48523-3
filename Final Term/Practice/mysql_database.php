<?php
// PHP MySQL Database Operations

// Database connection settings
$servername = "localhost";
$username = "root";           // Default username for XAMPP
$password = "";               // Default empty password for XAMPP
$dbname = "php_practice_db";  // Database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to create the database if it doesn't exist
function createDatabase($conn, $dbname) {
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Database created successfully or already exists.</p>";
    } else {
        echo "<p>Error creating database: " . $conn->error . "</p>";
    }
}

// Function to create the users table
function createUsersTable($conn, $dbname) {
    // Select database
    $conn->select_db($dbname);
    
    // SQL to create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(30) NOT NULL,
        lastname VARCHAR(30) NOT NULL,
        email VARCHAR(50) UNIQUE,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'users' created successfully or already exists.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Function to insert sample data into users table
function insertSampleData($conn) {
    // Check if data already exists
    $check = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $check->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "<p>Sample data already exists in the users table.</p>";
        return;
    }
    
    // Sample data
    $users = [
        ["John", "Doe", "john@example.com"],
        ["Jane", "Smith", "jane@example.com"],
        ["Michael", "Johnson", "michael@example.com"]
    ];
    
    // Insert data
    $firstname = "";
    $lastname = "";
    $email = "";
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $firstname, $lastname, $email);
    
    // Insert data
    foreach ($users as $user) {
        $firstname = $user[0];
        $lastname = $user[1];
        $email = $user[2];
        $stmt->execute();
    }
    
    echo "<p>Sample data inserted successfully.</p>";
    $stmt->close();
}

// Function to retrieve and display user data
function displayUsers($conn) {
    $sql = "SELECT id, firstname, lastname, email, reg_date FROM users";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<h3>Users List</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Registration Date</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["firstname"] . " " . $row["lastname"] . "</td>";
            echo "<td>" . $row["email"] . "</td>";
            echo "<td>" . $row["reg_date"] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in the database.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP MySQL Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .section {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP MySQL Database Operations</h1>
        
        <div class="section">
            <h2>Database Setup</h2>
            <?php
                // Create database and table
                createDatabase($conn, $dbname);
                createUsersTable($conn, $dbname);
                insertSampleData($conn);
            ?>
        </div>
        
        <div class="section">
            <h2>Add New User</h2>
            <?php
                // Process form submission
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firstname'])) {
                    // Select database
                    $conn->select_db($dbname);
                    
                    // Validate and sanitize input
                    $firstname = htmlspecialchars($_POST['firstname']);
                    $lastname = htmlspecialchars($_POST['lastname']);
                    $email = htmlspecialchars($_POST['email']);
                    
                    // Validate email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo "<p class='error'>Invalid email format!</p>";
                    } else {
                        // Check if email already exists
                        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $check->bind_param("s", $email);
                        $check->execute();
                        $check->store_result();
                        
                        if ($check->num_rows > 0) {
                            echo "<p class='error'>This email is already registered!</p>";
                        } else {
                            // Prepare statement
                            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email) VALUES (?, ?, ?)");
                            $stmt->bind_param("sss", $firstname, $lastname, $email);
                            
                            // Execute statement
                            if ($stmt->execute()) {
                                echo "<p class='success'>New user added successfully!</p>";
                            } else {
                                echo "<p class='error'>Error: " . $stmt->error . "</p>";
                            }
                            
                            $stmt->close();
                        }
                        $check->close();
                    }
                }
            ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <input type="submit" value="Add User">
            </form>
        </div>
        
        <div class="section">
            <h2>Users in Database</h2>
            <?php
                // Select database
                $conn->select_db($dbname);
                
                // Display users
                displayUsers($conn);
            ?>
        </div>
        
        <div class="section">
            <h2>Search User by Email</h2>
            <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_email'])) {
                    // Select database
                    $conn->select_db($dbname);
                    
                    // Sanitize input
                    $search_email = htmlspecialchars($_POST['search_email']);
                    
                    // Prepare statement
                    $stmt = $conn->prepare("SELECT id, firstname, lastname, email, reg_date FROM users WHERE email LIKE ?");
                    $search_term = "%" . $search_email . "%";
                    $stmt->bind_param("s", $search_term);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        echo "<h3>Search Results</h3>";
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Registration Date</th></tr>";
                        
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td>" . $row["firstname"] . " " . $row["lastname"] . "</td>";
                            echo "<td>" . $row["email"] . "</td>";
                            echo "<td>" . $row["reg_date"] . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>No users found with that email.</p>";
                    }
                    
                    $stmt->close();
                }
            ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="search_email">Search by Email:</label>
                    <input type="text" id="search_email" name="search_email" required>
                </div>
                <input type="submit" value="Search">
            </form>
        </div>
        
        <div class="section">
            <h2>Database Operations Guide</h2>
            <p>This page demonstrates common PHP MySQL operations:</p>
            <ol>
                <li><strong>Creating a Database:</strong> Using CREATE DATABASE statement</li>
                <li><strong>Creating Tables:</strong> Using CREATE TABLE statement</li>
                <li><strong>Inserting Data:</strong> Using INSERT INTO statement with prepared statements</li>
                <li><strong>Reading Data:</strong> Using SELECT statement</li>
                <li><strong>Searching Data:</strong> Using WHERE clause with LIKE operator</li>
                <li><strong>Form Handling:</strong> Processing form submissions</li>
                <li><strong>Data Validation:</strong> Validating user input</li>
                <li><strong>Security:</strong> Using prepared statements to prevent SQL injection</li>
            </ol>
            <p><em>Note: This is a simplified example for learning purposes. Real-world applications would include more robust error handling, security measures, and optimized database queries.</em></p>
        </div>
    </div>

<?php
// Close connection
$conn->close();
?>
</body>
</html>
