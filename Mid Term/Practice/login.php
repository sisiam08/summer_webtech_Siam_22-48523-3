<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_system";

// Sample users (in a real application, these would be in a database)
$users = [
    [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT)
    ],
    [
        'id' => 2,
        'username' => 'user',
        'email' => 'user@example.com',
        'password' => password_hash('user123', PASSWORD_DEFAULT)
    ],
    [
        'id' => 3,
        'username' => 'demo',
        'email' => 'demo@example.com',
        'password' => password_hash('demo123', PASSWORD_DEFAULT)
    ]
];

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function findUser($identifier, $users) {
    foreach ($users as $user) {
        if ($user['username'] === $identifier || $user['email'] === $identifier) {
            return $user;
        }
    }
    return null;
}

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and validate input
    $username = validateInput($_POST['username'] ?? '');
    $password = validateInput($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username or email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // If validation fails
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    // Find user
    $user = findUser($username, $users);
    
    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        // Set remember me cookie if checked
        if ($remember) {
            $cookie_name = "remember_user";
            $cookie_value = base64_encode($user['username']);
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 30 days
        }
        
        // Log login attempt (in a real app, you'd log this to a file or database)
        error_log("Successful login: " . $user['username'] . " at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]);
        
    } else {
        // Login failed
        error_log("Failed login attempt: " . $username . " at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username/email or password'
        ]);
    }
    
} else {
    // If not POST request
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

/*
=== DATABASE SETUP (Optional) ===
If you want to use a MySQL database instead of the hardcoded users array,
uncomment and modify the code below:

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare statement to find user
    $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Login successful - same code as above
    } else {
        // Login failed - same code as above
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
}

=== SQL TABLE STRUCTURE ===
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('demo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
*/
?>
