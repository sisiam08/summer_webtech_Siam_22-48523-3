<?php
// Database connection configuration helper

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Setup</h1>";

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '127.0.0.1';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    $database = $_POST['database'] ?? 'grocery_store';
    $port = $_POST['port'] ?? 3306;
    
    // Test connection
    try {
        $conn = mysqli_connect($host, $username, $password, '', $port);
        
        if ($conn) {
            echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
            echo "<strong>Success!</strong> Connected to MySQL server.";
            echo "</div>";
            
            // Check if database exists
            $db_selected = mysqli_select_db($conn, $database);
            
            if (!$db_selected) {
                echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
                echo "Database '$database' doesn't exist. You can create it automatically when running setup_database.php";
                echo "</div>";
            } else {
                echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
                echo "Database '$database' exists.";
                echo "</div>";
            }
            
            // Create or update database_connection.php
            $config_content = "<?php
// Database connection file - Simple procedural approach

// Database configuration
\$db_host = '$host';
\$db_user = '$username';
\$db_password = '$password';
\$db_name = '$database';
\$db_port = $port;

// Create connection with error handling
try {
    // First, try to connect without selecting a database (in case it doesn't exist yet)
    \$conn = mysqli_connect(\$db_host, \$db_user, \$db_password, '', \$db_port);
    
    // Check if the database exists, if not create it
    if (\$conn) {
        // Try to select the database
        \$db_selected = mysqli_select_db(\$conn, \$db_name);
        
        if (!\$db_selected) {
            // Create the database if it doesn't exist
            \$sql = \"CREATE DATABASE IF NOT EXISTS \$db_name\";
            mysqli_query(\$conn, \$sql);
            mysqli_select_db(\$conn, \$db_name);
        }
    }
} catch (Exception \$e) {
    die(\"<h1>Database Connection Error</h1>
         <p>Could not connect to MySQL. Please make sure:</p>
         <ol>
            <li>MySQL server is running</li>
            <li>The username and password are correct</li>
            <li>The server is accessible at \$db_host:\$db_port</li>
         </ol>
         <p>Error details: \" . \$e->getMessage() . \"</p>
         <p>If you're using XAMPP, please start MySQL from the XAMPP Control Panel.</p>\");
}

// Check connection
if (!\$conn) {
    die(\"<h1>Database Connection Error</h1>
         <p>Could not connect to MySQL. Please make sure:</p>
         <ol>
            <li>MySQL server is running</li>
            <li>The username and password are correct</li>
            <li>The server is accessible at \$db_host:\$db_port</li>
         </ol>
         <p>Error details: \" . mysqli_connect_error() . \"</p>
         <p>If you're using XAMPP, please start MySQL from the XAMPP Control Panel.</p>\");
}

// Function to execute queries
function executeQuery(\$sql) {
    global \$conn;
    \$result = mysqli_query(\$conn, \$sql);
    return \$result;
}

// Function to fetch all records
function fetchAll(\$sql) {
    global \$conn;
    \$result = mysqli_query(\$conn, \$sql);
    \$data = [];
    
    if (\$result && mysqli_num_rows(\$result) > 0) {
        while (\$row = mysqli_fetch_assoc(\$result)) {
            \$data[] = \$row;
        }
    }
    
    return \$data;
}

// Function to fetch a single record
function fetchOne(\$sql) {
    global \$conn;
    \$result = mysqli_query(\$conn, \$sql);
    
    if (\$result && mysqli_num_rows(\$result) > 0) {
        return mysqli_fetch_assoc(\$result);
    }
    
    return null;
}

// Function to safely prepare data for database operations
function sanitize(\$data) {
    global \$conn;
    return mysqli_real_escape_string(\$conn, \$data);
}
?>";
            
            // Write to file
            if (file_put_contents('database_connection.php', $config_content)) {
                echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
                echo "Successfully created database_connection.php with your settings.";
                echo "</div>";
                
                echo "<h3>Next Steps:</h3>";
                echo "<ol>";
                echo "<li><a href='setup_database.php'>Run Database Setup</a> to create tables and sample data</li>";
                echo "<li><a href='index.php'>Go to Homepage</a> to start using the application</li>";
                echo "</ol>";
            } else {
                echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
                echo "Failed to write to database_connection.php. Check file permissions.";
                echo "</div>";
            }
            
            mysqli_close($conn);
        } else {
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
            echo "<strong>Error:</strong> " . mysqli_connect_error();
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
        echo "<strong>Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}
?>

<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
    <p>Please enter your MySQL database connection details:</p>
    
    <form method="post" action="">
        <div style="margin-bottom: 15px;">
            <label for="host" style="display: block; margin-bottom: 5px; font-weight: bold;">Host:</label>
            <input type="text" id="host" name="host" value="127.0.0.1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #6c757d;">Usually 127.0.0.1 or localhost</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="username" style="display: block; margin-bottom: 5px; font-weight: bold;">Username:</label>
            <input type="text" id="username" name="username" value="root" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #6c757d;">Usually 'root' for local development</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Password:</label>
            <input type="password" id="password" name="password" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #6c757d;">Leave empty if no password is set</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="database" style="display: block; margin-bottom: 5px; font-weight: bold;">Database Name:</label>
            <input type="text" id="database" name="database" value="grocery_store" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #6c757d;">Will be created if it doesn't exist</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="port" style="display: block; margin-bottom: 5px; font-weight: bold;">Port:</label>
            <input type="text" id="port" name="port" value="3306" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #6c757d;">Usually 3306 for MySQL</small>
        </div>
        
        <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Test Connection & Save</button>
    </form>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <h3>Common MySQL Settings:</h3>
        <ul>
            <li><strong>XAMPP:</strong> Username: root, Password: (empty), Host: 127.0.0.1, Port: 3306</li>
            <li><strong>WAMP:</strong> Username: root, Password: (empty), Host: 127.0.0.1, Port: 3306</li>
            <li><strong>MAMP:</strong> Username: root, Password: root, Host: 127.0.0.1, Port: 8889</li>
            <li><strong>MySQL Default:</strong> Username: root, Host: 127.0.0.1, Port: 3306 (password varies)</li>
        </ul>
    </div>
</div>
