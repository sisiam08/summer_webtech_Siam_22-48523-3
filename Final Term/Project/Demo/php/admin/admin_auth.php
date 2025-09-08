<?php
/**
 * Admin Authentication Helper Functions
 */

/**
 * Check if an admin is logged in
 * @return bool True if admin is logged in, false otherwise
 */
function isAdminLoggedIn() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // Check if user is an admin
    if ($_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    // Store admin ID for convenience
    $_SESSION['admin_id'] = $_SESSION['user_id'];
    
    return true;
}

/**
 * Authenticate admin login
 * @param string $email Admin email
 * @param string $password Admin password
 * @return array|bool User data on success, false on failure
 */
function authenticateAdmin($email, $password) {
    global $conn; // Use the global connection
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT id, name, email, password, role, is_active 
        FROM users 
        WHERE email = ? AND role = 'admin'
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    
    // Check if admin exists
    if ($result->num_rows !== 1) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Get admin data
    $admin = $result->fetch_assoc();
    
    // Check if admin is active
    if ($admin['is_active'] != 1) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $admin['password'])) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Close statement
    $stmt->close();
    
    // Close connection
    $conn->close();
    
    // Remove password from user data
    unset($admin['password']);
    
    return $admin;
}

/**
 * Log out admin
 * @return void
 */
function logoutAdmin() {
    // Unset admin session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
    unset($_SESSION['admin_id']);
    
    // Destroy session
    session_destroy();
}

/**
 * Create new admin account
 * @param array $data Admin data
 * @return int|bool New admin ID on success, false on failure
 */
function createAdmin($data) {
    // Hash password
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $conn = getDbConnection();
    
    // Check if email already exists
    $checkStmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE email = ?
    ");
    
    $checkStmt->bind_param("s", $data['email']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($count > 0) {
        $conn->close();
        return false;
    }
    
    // Prepare statement
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, role, status) 
        VALUES (?, ?, ?, 'admin', 'active')
    ");
    
    $stmt->bind_param("sss", $data['name'], $data['email'], $password);
    $stmt->execute();
    
    $adminId = $stmt->insert_id;
    
    // Close connection
    $stmt->close();
    $conn->close();
    
    return $adminId;
}
?>
