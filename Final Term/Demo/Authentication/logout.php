<?php
session_start();

// Clear database cart if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../Database/database.php';
        $conn = connectDB();
        
        // Delete all cart items for this user
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error clearing database cart during logout: " . $e->getMessage());
    }
}

// Clear all session data including cart
$_SESSION = array();

// Clear cart-related session variables specifically
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}
if (isset($_SESSION['cart_items'])) {
    unset($_SESSION['cart_items']);
}
if (isset($_SESSION['cart_count'])) {
    unset($_SESSION['cart_count']);
}

// If it's the session cookie that needs to be deleted
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => 'login.html'
    ]);
} else {
    // Regular redirect for non-AJAX requests
    header("Location: login.html");
}
exit;
?>