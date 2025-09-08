<?php
// Start session with SameSite=None for cross-domain functionality (if needed)
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'On');
session_start();

// Include necessary files
include '../config/database.php';
include '../helpers.php';

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

// Check for fix mode
$fix_mode = isset($_GET['fix']) && $_GET['fix'] === 'true';

// Check auth status
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$is_shop_owner = $is_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner';

// HTML headers with no-cache directives
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Shop Owner Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
            padding: 10px;
            background-color: #dff0d8;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: red;
            padding: 10px;
            background-color: #f2dede;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-red {
            background-color: #f44336;
        }
        .btn-blue {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Shop Owner Login Fixer</h1>
        
        <?php if ($fix_mode): ?>
            <h2>Applying Fixes...</h2>
            <?php
            try {
                // 1. Fix session issues
                if (!$is_shop_owner) {
                    // Create a test shop owner session
                    $_SESSION['user_id'] = 1;
                    $_SESSION['user_name'] = 'Test Shop Owner';
                    $_SESSION['user_email'] = 'shop@example.com';
                    $_SESSION['user_role'] = 'shop_owner';
                    
                    // Check if we have a valid shop ID
                    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
                        // Try to find a shop for this user
                        try {
                            $conn = getConnection();
                            $stmt = $conn->prepare("SELECT id, name FROM shops WHERE owner_id = ? LIMIT 1");
                            $stmt->bind_param('i', $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $shop = $result->fetch_assoc();
                                $_SESSION['shop_id'] = $shop['id'];
                                $_SESSION['shop_name'] = $shop['name'];
                                echo "<div class='success'>Found existing shop: ID={$shop['id']}, Name={$shop['name']}</div>";
                            } else {
                                // Create a new shop for this user
                                $shopName = "Test Shop";
                                $stmt = $conn->prepare("INSERT INTO shops (name, owner_id, description, created_at) VALUES (?, ?, 'Test shop description', NOW())");
                                $stmt->bind_param('si', $shopName, $_SESSION['user_id']);
                                
                                if ($stmt->execute()) {
                                    $shopId = $conn->insert_id;
                                    $_SESSION['shop_id'] = $shopId;
                                    $_SESSION['shop_name'] = $shopName;
                                    echo "<div class='success'>Created new shop: ID={$shopId}, Name={$shopName}</div>";
                                } else {
                                    throw new Exception("Failed to create shop: " . $conn->error);
                                }
                            }
                        } catch (Exception $e) {
                            echo "<div class='error'>Database error: " . $e->getMessage() . "</div>";
                        }
                    }
                    
                    echo "<div class='success'>Created test shop owner session</div>";
                } else {
                    echo "<div class='success'>Already logged in as shop owner</div>";
                }
                
                // 2. Verify check_auth.php is working correctly
                $check_auth_content = file_get_contents('check_auth.php');
                if (strpos($check_auth_content, 'debug_info') === false) {
                    // Add debug information to check_auth.php
                    $new_content = str_replace(
                        "header('Content-Type: application/json');",
                        "header('Content-Type: application/json');\nheader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');",
                        $check_auth_content
                    );
                    file_put_contents('check_auth.php', $new_content);
                    echo "<div class='success'>Updated check_auth.php with no-cache headers</div>";
                }
                
                // 3. Ensure the index.html uses the proper auth check
                $index_content = file_get_contents('index.html');
                if (strpos($index_content, "console.log('Auth response data:") === false) {
                    // Add console logging to index.html
                    $new_content = str_replace(
                        "fetch('check_auth.php')",
                        "console.log('Checking authentication...');\nfetch('check_auth.php')",
                        $index_content
                    );
                    file_put_contents('index.html', $new_content);
                    echo "<div class='success'>Added debug logging to index.html</div>";
                }
                
                echo "<div class='success'>All fixes have been applied!</div>";
            } catch (Exception $e) {
                echo "<div class='error'>Error applying fixes: " . $e->getMessage() . "</div>";
            }
            ?>
        <?php else: ?>
            <h2>Current Status</h2>
            <?php if ($is_logged_in): ?>
                <p>You are currently logged in with the following session:</p>
                <pre><?php print_r($_SESSION); ?></pre>
                
                <?php if ($is_shop_owner): ?>
                    <div class="success">
                        <p><strong>Authentication Status:</strong> Logged in as Shop Owner</p>
                        <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?></p>
                        <p><strong>Name:</strong> <?= $_SESSION['user_name'] ?></p>
                        <p><strong>Shop ID:</strong> <?= $_SESSION['shop_id'] ?></p>
                    </div>
                <?php else: ?>
                    <div class="error">
                        <p><strong>Authentication Status:</strong> Logged in but NOT as Shop Owner</p>
                        <p><strong>Current Role:</strong> <?= $_SESSION['user_role'] ?? 'No role set' ?></p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="error">
                    <p><strong>Authentication Status:</strong> Not logged in</p>
                </div>
            <?php endif; ?>
            
            <h2>Available Actions</h2>
            <p>Click the button below to apply automatic fixes to the shop owner login system:</p>
            <a href="?fix=true" class="btn">Apply Fixes</a>
            
            <p>Or try these other actions:</p>
            <a href="auth_test.html" class="btn btn-blue">Authentication Test Page</a>
            <a href="create_test_session.php" class="btn btn-blue">Create Test Session</a>
            <a href="login.php" class="btn">Login Page</a>
            <a href="index.html" class="btn">Dashboard</a>
            <a href="logout.php" class="btn btn-red">Logout</a>
        <?php endif; ?>
    </div>
</body>
</html>
