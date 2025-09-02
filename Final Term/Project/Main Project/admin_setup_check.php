<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'php/db_connection.php';

echo "<h1>Admin Account Check</h1>";

// Check database connection
if (!$conn) {
    die("<p style='color:red'>Database connection failed: " . mysqli_connect_error() . "</p>");
} else {
    echo "<p style='color:green'>Database connection successful!</p>";
}

// Check tables
echo "<h2>Tables in database:</h2>";
$tables_result = mysqli_query($conn, "SHOW TABLES");
if ($tables_result) {
    if (mysqli_num_rows($tables_result) > 0) {
        echo "<ul>";
        while ($table = mysqli_fetch_row($tables_result)) {
            echo "<li>" . $table[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found in the database.</p>";
    }
} else {
    echo "<p style='color:red'>Error listing tables: " . mysqli_error($conn) . "</p>";
}

// Check if users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<p style='color:red'>The 'users' table does not exist!</p>";
} else {
    echo "<p style='color:green'>The 'users' table exists.</p>";
    
    // Check admin users
    echo "<h2>Admin users in database:</h2>";
    $admin_check = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE role = 'admin'");
    
    if ($admin_check) {
        if (mysqli_num_rows($admin_check) > 0) {
            echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>";
                
            while ($row = mysqli_fetch_assoc($admin_check)) {
                echo "<tr>
                    <td>" . $row['id'] . "</td>
                    <td>" . $row['name'] . "</td>
                    <td>" . $row['email'] . "</td>
                    <td>" . $row['role'] . "</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No admin users found in the database.</p>";
        }
    } else {
        echo "<p style='color:red'>Error checking admin users: " . mysqli_error($conn) . "</p>";
    }
}

echo "<h2>Manual Admin Creation Form</h2>";
echo "<p>Use this form to manually create an admin account:</p>";

echo "<form method='post' action=''>
    <div>
        <label for='name'>Admin Name:</label>
        <input type='text' id='name' name='name' value='System Administrator' required>
    </div>
    <div>
        <label for='email'>Admin Email:</label>
        <input type='email' id='email' name='email' value='admin@grocerystore.com' required>
    </div>
    <div>
        <label for='password'>Admin Password:</label>
        <input type='password' id='password' name='password' value='Admin@123' required>
    </div>
    <div>
        <input type='submit' name='create_admin' value='Create Admin User'>
    </div>
</form>";

// Process form submission
if (isset($_POST['create_admin'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<p style='color:red'>An account with this email already exists!</p>";
    } else {
        // Create users table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'shop_owner', 'delivery', 'customer') NOT NULL DEFAULT 'customer',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($conn, $create_table)) {
            echo "<p style='color:red'>Error creating users table: " . mysqli_error($conn) . "</p>";
            exit;
        }
        
        // Insert admin user
        $insert_query = "INSERT INTO users (name, email, password, role, is_active) 
                         VALUES ('$name', '$email', '$password', 'admin', 1)";
        
        if (mysqli_query($conn, $insert_query)) {
            echo "<p style='color:green'>Admin account created successfully!<br>
                 Email: $email<br>
                 Password: [HIDDEN]</p>";
        } else {
            echo "<p style='color:red'>Error creating admin account: " . mysqli_error($conn) . "</p>";
        }
    }
}

mysqli_close($conn);
?>
