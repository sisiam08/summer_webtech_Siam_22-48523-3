<?php
// Start session
session_start();

// Include necessary files
include '../config/database.php';
include '../helpers.php';

// Function to check cookie path settings
function get_cookie_info() {
    $cookies = [];
    foreach ($_COOKIE as $name => $value) {
        $cookies[] = "$name: $value";
    }
    return $cookies;
}

// Get session info
function get_session_info() {
    return [
        'session_id' => session_id(),
        'session_name' => session_name(),
        'session_status' => session_status(),
        'session_vars' => $_SESSION
    ];
}

// Output format
$format = isset($_GET['format']) && $_GET['format'] == 'json' ? 'json' : 'html';

// Function to get database connection
function getConnection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        return $conn;
    }
    
    // Database configuration
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_password = 'Siam@MySQL2025'; // Your MySQL password
    $db_name = 'grocery_store';
    $db_port = 3306;
    
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Check database connection
$db_status = [
    'connection' => false,
    'error' => ''
];

try {
    $conn = getConnection();
    $db_status['connection'] = true;
} catch (Exception $e) {
    $db_status['error'] = $e->getMessage();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? 'none';
$shop_id = $_SESSION['shop_id'] ?? 'none';
$shop_status = [];

// If logged in as shop owner, check shop status
if ($is_logged_in && $user_role == 'shop_owner' && $shop_id != 'none') {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $shop = $result->fetch_assoc();
            $shop_status = $shop;
        } else {
            $shop_status = ['error' => 'Shop not found'];
        }
    } catch (Exception $e) {
        $shop_status = ['error' => $e->getMessage()];
    }
}

// Prepare data
$data = [
    'php_info' => [
        'version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'session_path' => session_save_path()
    ],
    'session' => get_session_info(),
    'cookies' => get_cookie_info(),
    'auth_status' => [
        'is_logged_in' => $is_logged_in,
        'user_role' => $user_role,
        'user_id' => $_SESSION['user_id'] ?? 'none',
        'user_name' => $_SESSION['user_name'] ?? 'none',
        'user_email' => $_SESSION['user_email'] ?? 'none',
        'shop_id' => $shop_id
    ],
    'shop_status' => $shop_status,
    'database' => $db_status,
    'server' => [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'php_self' => $_SERVER['PHP_SELF'],
        'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]
];

// Output data
if ($format == 'json') {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            flex: 1;
            min-width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow: auto;
        }
        .actions {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-primary {
            background-color: #2196F3;
        }
        form {
            margin-top: 15px;
        }
        input, select {
            padding: 8px;
            margin: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <h1>Authentication Test Dashboard</h1>
    
    <div class="actions">
        <h2>Actions</h2>
        <a href="auth_test_enhanced.php" class="btn">Refresh</a>
        <a href="auth_test_enhanced.php?format=json" class="btn btn-primary">View as JSON</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
        <a href="index.html" class="btn">Go to Dashboard</a>
        <a href="debug.html" class="btn">Debug Tool</a>
        
        <form action="set_test_session.php" method="post">
            <h3>Set Test Session</h3>
            <input type="text" name="user_id" placeholder="User ID" value="<?= $_SESSION['user_id'] ?? '1' ?>">
            <input type="text" name="user_name" placeholder="User Name" value="<?= $_SESSION['user_name'] ?? 'Test Shop Owner' ?>">
            <input type="email" name="user_email" placeholder="Email" value="<?= $_SESSION['user_email'] ?? 'test@example.com' ?>">
            <select name="user_role">
                <option value="shop_owner" <?= ($user_role == 'shop_owner') ? 'selected' : '' ?>>Shop Owner</option>
                <option value="admin" <?= ($user_role == 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="delivery_man" <?= ($user_role == 'delivery_man') ? 'selected' : '' ?>>Delivery Man</option>
            </select>
            <input type="text" name="shop_id" placeholder="Shop ID" value="<?= $_SESSION['shop_id'] ?? '1' ?>">
            <input type="text" name="shop_name" placeholder="Shop Name" value="<?= $_SESSION['shop_name'] ?? 'Test Shop' ?>">
            <button type="submit" class="btn">Set Session</button>
        </form>
    </div>

    <div class="container">
        <div class="card">
            <h3>Authentication Status</h3>
            <p><strong>Logged In:</strong> <?= $is_logged_in ? 'Yes' : 'No' ?></p>
            <p><strong>User Role:</strong> <?= $user_role ?></p>
            <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'Not set' ?></p>
            <p><strong>User Name:</strong> <?= $_SESSION['user_name'] ?? 'Not set' ?></p>
            <p><strong>User Email:</strong> <?= $_SESSION['user_email'] ?? 'Not set' ?></p>
            <p><strong>Shop ID:</strong> <?= $shop_id ?></p>
            <p><strong>Shop Name:</strong> <?= $_SESSION['shop_name'] ?? 'Not set' ?></p>
        </div>

        <div class="card">
            <h3>Session Information</h3>
            <p><strong>Session ID:</strong> <?= session_id() ?></p>
            <p><strong>Session Name:</strong> <?= session_name() ?></p>
            <p><strong>Session Status:</strong> <?= session_status() ?></p>
            <p><strong>Session Save Path:</strong> <?= session_save_path() ?></p>
            <h4>Session Variables:</h4>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>

        <div class="card">
            <h3>Cookie Information</h3>
            <pre><?php print_r(get_cookie_info()); ?></pre>
        </div>

        <?php if ($is_logged_in && $user_role == 'shop_owner'): ?>
        <div class="card">
            <h3>Shop Information</h3>
            <?php if (isset($shop_status['error'])): ?>
                <p class="error">Error: <?= $shop_status['error'] ?></p>
            <?php else: ?>
                <pre><?php print_r($shop_status); ?></pre>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>Database Status</h3>
            <p><strong>Connection:</strong> <?= $db_status['connection'] ? 'Success' : 'Failed' ?></p>
            <?php if (!$db_status['connection']): ?>
                <p><strong>Error:</strong> <?= $db_status['error'] ?></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Server Information</h3>
            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            <p><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
            <p><strong>Request Method:</strong> <?= $_SERVER['REQUEST_METHOD'] ?></p>
            <p><strong>Request URI:</strong> <?= $_SERVER['REQUEST_URI'] ?></p>
            <p><strong>PHP Self:</strong> <?= $_SERVER['PHP_SELF'] ?></p>
            <p><strong>HTTP Referer:</strong> <?= $_SERVER['HTTP_REFERER'] ?? 'None' ?></p>
        </div>
    </div>

    <div class="card">
        <h3>Check Auth API Response</h3>
        <div id="authResponse">Loading...</div>
        <script>
            // Fetch the check_auth.php response
            fetch('check_auth.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('authResponse').innerHTML = 
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('authResponse').innerHTML = 
                        '<p>Error: ' + error.message + '</p>';
                });
        </script>
    </div>
</body>
</html>
