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
    
    // Get total deliveries
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM deliveries WHERE delivery_person_id = ?");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_deliveries = $result->fetch_assoc()['total'];
    
    // Get today's deliveries
    $stmt = $conn->prepare("SELECT COUNT(*) as today FROM deliveries WHERE delivery_person_id = ? AND DATE(delivered_at) = CURDATE()");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $today_deliveries = $result->fetch_assoc()['today'];
    
    // Get pending deliveries
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM deliveries WHERE delivery_person_id = ? AND status IN ('assigned', 'picked_up')");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_deliveries = $result->fetch_assoc()['pending'];
    
    // Get average rating
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM deliveries WHERE delivery_person_id = ? AND rating IS NOT NULL");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $avg_rating = $result->fetch_assoc()['avg_rating'];
    
    // Get total earnings
    $stmt = $conn->prepare("SELECT SUM(delivery_fee) as earnings FROM deliveries WHERE delivery_person_id = ? AND status = 'delivered'");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $earnings = $result->fetch_assoc()['earnings'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_deliveries' => (int)$total_deliveries,
            'today_deliveries' => (int)$today_deliveries,
            'pending_deliveries' => (int)$pending_deliveries,
            'avg_rating' => $avg_rating ? round((float)$avg_rating, 1) : 0,
            'earnings' => $earnings ? (float)$earnings : 0
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
