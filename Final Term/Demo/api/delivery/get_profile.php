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

// Connect to database
require_once '../db_connect.php';

try {
    // Get user and delivery person information
    $stmt = $conn->prepare("
        SELECT u.*, d.*, 
               v.type as vehicle_type, v.make as vehicle_make, v.model as vehicle_model, v.year as vehicle_year, 
               v.license_plate, v.color as vehicle_color, v.notes as vehicle_notes,
               p.method as payment_method, p.bank_name, p.account_name, p.account_number, p.routing_number,
               p.paypal_email, p.wallet_type, p.wallet_id
        FROM users u
        JOIN delivery_personnel d ON u.id = d.user_id
        LEFT JOIN delivery_vehicle v ON d.id = v.delivery_personnel_id
        LEFT JOIN delivery_payment p ON d.id = p.delivery_personnel_id
        WHERE u.id = ?
    ");
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'User not found'
        ]);
        exit;
    }
    
    $profile = $result->fetch_assoc();
    
    // Format vehicle information
    $vehicle = null;
    if ($profile['vehicle_type']) {
        $vehicle = [
            'type' => $profile['vehicle_type'],
            'make' => $profile['vehicle_make'],
            'model' => $profile['vehicle_model'],
            'year' => $profile['vehicle_year'],
            'license_plate' => $profile['license_plate'],
            'color' => $profile['vehicle_color'],
            'notes' => $profile['vehicle_notes']
        ];
    }
    
    // Format payment information
    $payment = null;
    if ($profile['payment_method']) {
        $payment = [
            'method' => $profile['payment_method']
        ];
        
        if ($profile['payment_method'] === 'bank_transfer') {
            $payment['bank_name'] = $profile['bank_name'];
            $payment['account_name'] = $profile['account_name'];
            $payment['account_number'] = $profile['account_number'];
            $payment['routing_number'] = $profile['routing_number'];
        } elseif ($profile['payment_method'] === 'paypal') {
            $payment['paypal_email'] = $profile['paypal_email'];
        } elseif ($profile['payment_method'] === 'mobile_wallet') {
            $payment['wallet_type'] = $profile['wallet_type'];
            $payment['wallet_id'] = $profile['wallet_id'];
        }
    }
    
    // Prepare response data
    $response = [
        'user_id' => $profile['user_id'],
        'email' => $profile['email'],
        'first_name' => $profile['first_name'],
        'last_name' => $profile['last_name'],
        'phone' => $profile['phone'],
        'address' => $profile['address'],
        'city' => $profile['city'],
        'postal_code' => $profile['postal_code'],
        'dob' => $profile['dob'],
        'id_number' => $profile['id_number'],
        'status' => $profile['status'],
        'vehicle' => $vehicle,
        'payment' => $payment
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
