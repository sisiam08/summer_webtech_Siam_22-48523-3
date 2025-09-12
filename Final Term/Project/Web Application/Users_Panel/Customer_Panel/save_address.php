<?php
// Save (create or update) an address
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Get customer ID
$customer_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['name', 'address_line1', 'city', 'state', 'postal_code', 'country', 'phone'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Field ' . $field . ' is required']);
        exit;
    }
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // If this is set as default address, reset all other default addresses
    if (isset($data['is_default']) && $data['is_default']) {
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = :customer_id");
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
    }
    
    // Check if this is an update or create
    if (isset($data['id']) && !empty($data['id'])) {
        // Update existing address
        $address_id = intval($data['id']);
        
        // First, verify that the address belongs to this customer
        $stmt = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE id = :address_id AND customer_id = :customer_id");
        $stmt->bindParam(':address_id', $address_id);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Address not found or you do not have permission to update it']);
            exit;
        }
        
        // Update the address
        $stmt = $conn->prepare("UPDATE addresses SET 
                               name = :name,
                               address_line1 = :address_line1,
                               address_line2 = :address_line2,
                               city = :city,
                               state = :state,
                               postal_code = :postal_code,
                               country = :country,
                               phone = :phone,
                               is_default = :is_default,
                               updated_at = NOW()
                               WHERE id = :address_id");
        
        $stmt->bindParam(':address_id', $address_id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address_line1', $data['address_line1']);
        $stmt->bindParam(':address_line2', $data['address_line2']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':phone', $data['phone']);
        $is_default = isset($data['is_default']) ? (bool)$data['is_default'] : false;
        $stmt->bindParam(':is_default', $is_default, PDO::PARAM_BOOL);
        
        $stmt->execute();
        
        $message = 'Address updated successfully';
    } else {
        // Create new address
        $stmt = $conn->prepare("INSERT INTO addresses 
                               (customer_id, name, address_line1, address_line2, city, state, postal_code, country, phone, is_default, created_at, updated_at)
                               VALUES
                               (:customer_id, :name, :address_line1, :address_line2, :city, :state, :postal_code, :country, :phone, :is_default, NOW(), NOW())");
        
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address_line1', $data['address_line1']);
        $stmt->bindParam(':address_line2', $data['address_line2']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':phone', $data['phone']);
        $is_default = isset($data['is_default']) ? (bool)$data['is_default'] : false;
        $stmt->bindParam(':is_default', $is_default, PDO::PARAM_BOOL);
        
        $stmt->execute();
        $address_id = $conn->lastInsertId();
        
        $message = 'Address created successfully';
    }
    
    // If no addresses were default and this is the first address, make it default
    if (!isset($data['is_default']) || !$data['is_default']) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE customer_id = :customer_id AND is_default = 1");
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = :address_id");
            $stmt->bindParam(':address_id', $address_id);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'id' => $address_id
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
