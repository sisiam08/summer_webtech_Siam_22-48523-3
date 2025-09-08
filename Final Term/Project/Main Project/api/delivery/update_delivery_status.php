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
if (!isset($data['delivery_id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

$delivery_id = $data['delivery_id'];
$new_status = $data['status'];

// Validate status
$allowed_statuses = ['picked_up', 'delivered'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid status'
    ]);
    exit;
}

// Connect to database
require_once '../db_connect.php';

try {
    // Get delivery person ID to ensure they can only update their own deliveries
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
    $delivery_person_id = $row['id'];
    
    // Get current delivery status to ensure proper transition
    $stmt = $conn->prepare("SELECT status FROM deliveries WHERE id = ? AND delivery_person_id = ?");
    $stmt->bind_param("ii", $delivery_id, $delivery_person_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Delivery not found or not assigned to you'
        ]);
        exit;
    }
    
    $current_status = $result->fetch_assoc()['status'];
    
    // Validate status transition
    $valid_transition = false;
    switch ($current_status) {
        case 'assigned':
            $valid_transition = ($new_status === 'picked_up');
            break;
        case 'picked_up':
            $valid_transition = ($new_status === 'delivered');
            break;
        default:
            $valid_transition = false;
    }
    
    if (!$valid_transition) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status transition from ' . $current_status . ' to ' . $new_status
        ]);
        exit;
    }
    
    // Update delivery status
    $stmt = $conn->prepare("UPDATE deliveries SET status = ? WHERE id = ? AND delivery_person_id = ?");
    $stmt->bind_param("sii", $new_status, $delivery_id, $delivery_person_id);
    $result = $stmt->execute();
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update delivery status'
        ]);
        exit;
    }
    
    // Update timestamp based on status
    if ($new_status === 'picked_up') {
        $stmt = $conn->prepare("UPDATE deliveries SET picked_up_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
        
        // Also update order status
        $stmt = $conn->prepare("UPDATE orders SET status = 'out_for_delivery' WHERE id = (SELECT order_id FROM deliveries WHERE id = ?)");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
    } elseif ($new_status === 'delivered') {
        $stmt = $conn->prepare("UPDATE deliveries SET delivered_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
        
        // Also update order status
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = (SELECT order_id FROM deliveries WHERE id = ?)");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery status updated successfully',
        'status' => $new_status
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
