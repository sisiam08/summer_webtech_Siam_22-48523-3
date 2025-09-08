<?php
// Delete an address
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
    // First, check if the address belongs to the customer and is not the default address
    $stmt = $conn->prepare("SELECT is_default FROM addresses WHERE id = :address_id AND customer_id = :customer_id");
    $stmt->bindParam(':address_id', $address_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$address) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Address not found or you do not have permission to delete it']);
        exit;
    }
    
    if ($address['is_default']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cannot delete default address. Please set another address as default first.']);
        exit;
    }
    
    // Delete the address
    $stmt = $conn->prepare("DELETE FROM addresses WHERE id = :address_id");
    $stmt->bindParam(':address_id', $address_id);
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Address deleted successfully'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
