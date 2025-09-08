<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Handle different actions
if ($action === 'add_address') {
    // Add new address
    $label = $_POST['label'] ?? '';
    $line1 = $_POST['line1'] ?? '';
    $area = $_POST['area'] ?? '';
    $city = $_POST['city'] ?? '';
    $postalCode = $_POST['postal_code'] ?? '';
    $phone = $_POST['address_phone'] ?? '';
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate address data
    if (empty($label) || empty($line1) || empty($area) || empty($city) || empty($postalCode) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'All address fields are required']);
        exit;
    }
    
    try {
        $conn = connectDB();
        
        // If this is default, unset any existing default address
        if ($isDefault) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Add new address
        $query = "INSERT INTO addresses (user_id, label, line1, area, city, postal_code, phone, is_default) 
                  VALUES (:user_id, :label, :line1, :area, :city, :postal_code, :phone, :is_default)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':label', $label, PDO::PARAM_STR);
        $stmt->bindParam(':line1', $line1, PDO::PARAM_STR);
        $stmt->bindParam(':area', $area, PDO::PARAM_STR);
        $stmt->bindParam(':city', $city, PDO::PARAM_STR);
        $stmt->bindParam(':postal_code', $postalCode, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Address added successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} elseif ($action === 'delete_address') {
    // Delete address
    $addressId = $_POST['address_id'] ?? 0;
    
    if (empty($addressId)) {
        echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
        exit;
    }
    
    try {
        $conn = connectDB();
        
        // Check if address belongs to user
        $stmt = $conn->prepare("SELECT id, is_default FROM addresses WHERE id = :address_id AND user_id = :user_id");
        $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            exit;
        }
        
        // Delete address
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = :address_id");
        $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->execute();
        
        // If deleted address was default, set another address as default
        if ($address['is_default']) {
            $stmt = $conn->prepare("SELECT id FROM addresses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $newDefault = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newDefault) {
                $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = :address_id");
                $stmt->bindParam(':address_id', $newDefault['id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} elseif ($action === 'set_default_address') {
    // Set default address
    $addressId = $_POST['address_id'] ?? 0;
    
    if (empty($addressId)) {
        echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
        exit;
    }
    
    try {
        $conn = connectDB();
        
        // Check if address belongs to user
        $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = :address_id AND user_id = :user_id");
        $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Address not found']);
            exit;
        }
        
        // Unset any existing default address
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Set new default address
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = :address_id");
        $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
