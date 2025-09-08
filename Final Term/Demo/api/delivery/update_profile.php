<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate data
if (!isset($data['type']) || !isset($data['data'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

$update_type = $data['type'];
$update_data = $data['data'];

// Connect to database
require_once '../db_connect.php';

try {
    // Get delivery person ID
    $stmt = $conn->prepare("SELECT id FROM delivery_personnel WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Delivery personnel not found'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $delivery_id = $row['id'];
    
    // Handle different types of updates
    switch ($update_type) {
        case 'personal':
            updatePersonalInfo($conn, $_SESSION['user_id'], $delivery_id, $update_data);
            break;
        case 'vehicle':
            updateVehicleInfo($conn, $delivery_id, $update_data);
            break;
        case 'payment':
            updatePaymentInfo($conn, $delivery_id, $update_data);
            break;
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid update type'
            ]);
            exit;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();

// Function to update personal information
function updatePersonalInfo($conn, $user_id, $delivery_id, $data) {
    // Update user table
    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, phone = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $data['first_name'], $data['last_name'], $data['phone'], $user_id);
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update user information'
        ]);
        exit;
    }
    
    // Update delivery_personnel table
    $stmt = $conn->prepare("
        UPDATE delivery_personnel 
        SET address = ?, city = ?, postal_code = ?, dob = ?, id_number = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $data['address'], $data['city'], $data['postal_code'], $data['dob'], $data['id_number'], $delivery_id);
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update delivery personnel information'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Personal information updated successfully'
    ]);
}

// Function to update vehicle information
function updateVehicleInfo($conn, $delivery_id, $data) {
    // Check if vehicle record exists
    $stmt = $conn->prepare("SELECT id FROM delivery_vehicle WHERE delivery_personnel_id = ?");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Insert new vehicle record
        $stmt = $conn->prepare("
            INSERT INTO delivery_vehicle 
            (delivery_personnel_id, type, make, model, year, license_plate, color, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssssss", $delivery_id, $data['type'], $data['make'], $data['model'], $data['year'], $data['license_plate'], $data['color'], $data['notes']);
    } else {
        // Update existing vehicle record
        $vehicle_id = $result->fetch_assoc()['id'];
        $stmt = $conn->prepare("
            UPDATE delivery_vehicle 
            SET type = ?, make = ?, model = ?, year = ?, license_plate = ?, color = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssssssi", $data['type'], $data['make'], $data['model'], $data['year'], $data['license_plate'], $data['color'], $data['notes'], $vehicle_id);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update vehicle information'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle information updated successfully'
    ]);
}

// Function to update payment information
function updatePaymentInfo($conn, $delivery_id, $data) {
    // Check if payment record exists
    $stmt = $conn->prepare("SELECT id FROM delivery_payment WHERE delivery_personnel_id = ?");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Prepare parameters based on payment method
    $method = $data['method'];
    $bank_name = $account_name = $account_number = $routing_number = $paypal_email = $wallet_type = $wallet_id = null;
    
    if ($method === 'bank_transfer') {
        $bank_name = $data['bank_name'];
        $account_name = $data['account_name'];
        $account_number = $data['account_number'];
        $routing_number = $data['routing_number'];
    } elseif ($method === 'paypal') {
        $paypal_email = $data['paypal_email'];
    } elseif ($method === 'mobile_wallet') {
        $wallet_type = $data['wallet_type'];
        $wallet_id = $data['wallet_id'];
    }
    
    if ($result->num_rows === 0) {
        // Insert new payment record
        $stmt = $conn->prepare("
            INSERT INTO delivery_payment 
            (delivery_personnel_id, method, bank_name, account_name, account_number, routing_number, paypal_email, wallet_type, wallet_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssssss", $delivery_id, $method, $bank_name, $account_name, $account_number, $routing_number, $paypal_email, $wallet_type, $wallet_id);
    } else {
        // Update existing payment record
        $payment_id = $result->fetch_assoc()['id'];
        $stmt = $conn->prepare("
            UPDATE delivery_payment 
            SET method = ?, bank_name = ?, account_name = ?, account_number = ?, routing_number = ?, paypal_email = ?, wallet_type = ?, wallet_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssssi", $method, $bank_name, $account_name, $account_number, $routing_number, $paypal_email, $wallet_type, $wallet_id, $payment_id);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update payment information'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment information updated successfully'
    ]);
}
?>
