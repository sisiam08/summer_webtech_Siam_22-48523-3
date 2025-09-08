<?php
// Set an address as the default address
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

if (!isset($data['id']) || empty($data['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Address ID is required']);
    exit;
}

$address_id = intval($data['id']);

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // First, check if the address belongs to the customer
    $stmt = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE id = :address_id AND customer_id = :customer_id");
    $stmt->bindParam(':address_id', $address_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Address not found or you do not have permission to modify it']);
        exit;
    }
    
    // Reset all default addresses
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    // Set the new default address
    $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = :address_id");
    $stmt->bindParam(':address_id', $address_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Default address updated successfully'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
