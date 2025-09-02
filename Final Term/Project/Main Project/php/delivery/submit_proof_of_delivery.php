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

// Validate required fields
if (!isset($_POST['delivery_id']) || empty($_POST['delivery_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Delivery ID is required'
    ]);
    exit;
}

// Check if image is uploaded
if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] != 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Proof image is required'
    ]);
    exit;
}

$delivery_id = $_POST['delivery_id'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

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
    $stmt = $conn->prepare("SELECT status, order_id FROM deliveries WHERE id = ? AND delivery_person_id = ?");
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
    
    $delivery = $result->fetch_assoc();
    $current_status = $delivery['status'];
    $order_id = $delivery['order_id'];
    
    // Validate status transition
    if ($current_status !== 'picked_up') {
        echo json_encode([
            'success' => false,
            'error' => 'Cannot submit proof for delivery that is not in picked up status'
        ]);
        exit;
    }
    
    // Process uploaded image
    $upload_dir = '../../uploads/delivery_proof/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
    $file_name = 'delivery_' . $delivery_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $file_name;
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode([
            'success' => false,
            'error' => 'Only JPG, JPEG, PNG & GIF files are allowed'
        ]);
        exit;
    }
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $target_file)) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to upload image'
        ]);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update delivery status and add proof information
    $stmt = $conn->prepare("
        UPDATE deliveries 
        SET status = 'delivered', delivered_at = NOW(), 
            proof_image = ?, proof_notes = ?
        WHERE id = ? AND delivery_person_id = ?
    ");
    $stmt->bind_param("ssii", $file_name, $notes, $delivery_id, $delivery_person_id);
    $result = $stmt->execute();
    
    if (!$result) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update delivery status'
        ]);
        exit;
    }
    
    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'delivered', delivered_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $result = $stmt->execute();
    
    if (!$result) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update order status'
        ]);
        exit;
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery completed successfully',
        'proof_image' => $file_name
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
