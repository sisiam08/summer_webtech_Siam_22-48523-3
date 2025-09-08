<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if required fields are provided
if (!isset($_POST['id']) || !isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$shopId = intval($_POST['id']);
$userId = intval($_POST['user_id']);
$action = $_POST['action'];

// Validate action
$validActions = ['approve', 'reject', 'activate', 'deactivate'];
if (!in_array($action, $validActions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Process action
    switch ($action) {
        case 'approve':
            // Approve shop and activate user
            $stmt = $pdo->prepare("UPDATE vendors SET is_approved = 1, is_active = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$shopId]);
            
            // Activate user account
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Check if shop is already in shops table
            $stmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
            $stmt->execute([$userId]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shop) {
                // Get shop data from vendors table
                $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                $stmt->execute([$shopId]);
                $vendorData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Create shop record
                $stmt = $pdo->prepare("INSERT INTO shops (name, owner_id, description, delivery_charge, minimum_order, is_active, created_at) 
                                       VALUES (?, ?, ?, 5.00, 10.00, 1, NOW())");
                $stmt->execute([$vendorData['shop_name'], $userId, $vendorData['description']]);
            }
            
            $message = 'Shop has been approved successfully';
            break;
            
        case 'reject':
            // Reject shop application
            $stmt = $pdo->prepare("UPDATE vendors SET is_approved = 0, is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$shopId]);
            
            $message = 'Shop application has been rejected';
            break;
            
        case 'activate':
            // Activate shop
            $stmt = $pdo->prepare("UPDATE vendors SET is_active = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$shopId]);
            
            // Update shop in shops table
            $stmt = $pdo->prepare("UPDATE shops SET is_active = 1 WHERE owner_id = ?");
            $stmt->execute([$userId]);
            
            // Activate user account
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            $message = 'Shop has been activated successfully';
            break;
            
        case 'deactivate':
            // Deactivate shop
            $stmt = $pdo->prepare("UPDATE vendors SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$shopId]);
            
            // Update shop in shops table
            $stmt = $pdo->prepare("UPDATE shops SET is_active = 0 WHERE owner_id = ?");
            $stmt->execute([$userId]);
            
            $message = 'Shop has been deactivated successfully';
            break;
    }
    
    // Send notification email (implement later)
    // TODO: Add email notification
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
