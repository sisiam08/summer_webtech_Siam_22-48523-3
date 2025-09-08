<?php
// Initialize session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'shop_owner') {
    // Already logged in, return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => 'index.php']);
    exit;
}

// Process JSON request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // If JSON data is null, try regular POST
    if ($data === null) {
        $data = $_POST;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $remember = isset($data['remember']) ? (bool)$data['remember'] : false;
    
    // Validate input
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Process login
    if (empty($errors)) {
        // Check if user exists
        $sql = "SELECT id, name, email, password, role, is_active FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = 'No account found with this email';
        } else {
            $user = $result->fetch_assoc();
            
            // Check if user is a shop owner
            if ($user['role'] !== 'shop_owner') {
                $errors[] = 'You do not have shop owner access';
            }
            // Check if account is active
            else if ($user['is_active'] == 0) {
                $errors[] = 'Your account is not active';
            }
            // Verify password
            else if (!password_verify($password, $user['password'])) {
                $errors[] = 'Invalid password';
            }
            else {
                // Get shop info
                $shopSql = "SELECT id, name FROM shops WHERE owner_id = ?";
                $stmt = $conn->prepare($shopSql);
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $shopResult = $stmt->get_result();
                
                if ($shopResult->num_rows === 0) {
                    // Create a new shop
                    $shopName = $user['name'] . "'s Shop";
                    $description = "Shop for " . $user['name'];
                    
                    $createShopSql = "INSERT INTO shops (name, owner_id, description, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt = $conn->prepare($createShopSql);
                    $stmt->bind_param('sis', $shopName, $user['id'], $description);
                    
                    if (!$stmt->execute()) {
                        $errors[] = 'Failed to create shop account';
                    } else {
                        $shopId = $conn->insert_id;
                        $shopName = $shopName;
                    }
                } else {
                    $shop = $shopResult->fetch_assoc();
                    $shopId = $shop['id'];
                    $shopName = $shop['name'];
                }
                
                // If no errors, set session and return success
                if (empty($errors)) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['shop_id'] = $shopId;
                    $_SESSION['shop_name'] = $shopName;
                    
                    // Set cookie if remember me is selected
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (86400 * 30); // 30 days
                        
                        // Store token in database
                        $tokenSql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))";
                        $stmt = $conn->prepare($tokenSql);
                        $stmt->bind_param('isi', $user['id'], $token, $expires);
                        $stmt->execute();
                        
                        // Set cookie
                        setcookie('remember_token', $token, $expires, '/');
                    }
                    
                    // Return success response
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => 'index.php']);
                    exit;
                }
            }
        }
    }
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => implode('. ', $errors)
    ]);
    exit;
}

// If not a POST request, redirect to login page
header('Location: login.html');
exit;
?>
