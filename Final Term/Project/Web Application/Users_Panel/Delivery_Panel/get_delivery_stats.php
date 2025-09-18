<?php
// Start session and include required files
session_start();
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and is a delivery person
if (!isLoggedIn() || !isDelivery()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $conn = connectDB();
    $deliveryPersonId = $_SESSION['user_id'];
    
    // Debug: Add user info to response
    $userInfo = getCurrentUser();
    
    // 1. PENDING DELIVERIES: Count orders with status 'delivered' assigned to this delivery man
    // These appear in "My Assignments" page
    $pendingStmt = $conn->prepare("
        SELECT COUNT(*) as pending_count
        FROM orders 
        WHERE delivery_person_id = ? AND status = 'delivered'
    ");
    $pendingStmt->execute([$deliveryPersonId]);
    $pending = $pendingStmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
    
    // 2. COMPLETED TODAY: Count orders with status 'completed' by this delivery man TODAY
    $completedTodayStmt = $conn->prepare("
        SELECT COUNT(*) as completed_today_count
        FROM orders 
        WHERE delivery_person_id = ? 
        AND status = 'completed' 
        AND DATE(created_at) = CURDATE()
    ");
    $completedTodayStmt->execute([$deliveryPersonId]);
    $completedToday = $completedTodayStmt->fetch(PDO::FETCH_ASSOC)['completed_today_count'];
    
    // 3. TODAY'S EARNINGS: 2% commission from orders completed TODAY by this delivery man
    $earningsTodayStmt = $conn->prepare("
        SELECT COALESCE(SUM(
            (SELECT SUM(oi.quantity * p.price) * 0.02
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = o.id)
        ), 0) as earnings_today
        FROM orders o
        WHERE o.delivery_person_id = ? 
        AND o.status = 'completed' 
        AND DATE(o.created_at) = CURDATE()
    ");
    $earningsTodayStmt->execute([$deliveryPersonId]);
    $earningsToday = $earningsTodayStmt->fetch(PDO::FETCH_ASSOC)['earnings_today'];
    
    // Get total completed deliveries (for rating calculation)
    $totalCompletedStmt = $conn->prepare("
        SELECT COUNT(*) as total_completed
        FROM orders 
        WHERE delivery_person_id = ? AND status = 'completed'
    ");
    $totalCompletedStmt->execute([$deliveryPersonId]);
    $totalCompleted = $totalCompletedStmt->fetch(PDO::FETCH_ASSOC)['total_completed'];
    
    // Calculate a simple rating based on completed deliveries (5.0 - 0.1 for every 10 deliveries, min 4.0)
    $rating = max(4.0, 5.0 - (floor($totalCompleted / 10) * 0.1));
    
    $response = [
        'success' => true,
        'debug' => [
            'user_id' => $deliveryPersonId,
            'user_name' => $userInfo['name'] ?? 'Unknown',
            'user_role' => $userInfo['role'] ?? 'Unknown',
            'current_date' => date('Y-m-d'),
            'queries_executed' => [
                'pending' => "SELECT COUNT(*) FROM orders WHERE delivery_person_id = $deliveryPersonId AND status = 'delivered'",
                'completed_today' => "SELECT COUNT(*) FROM orders WHERE delivery_person_id = $deliveryPersonId AND status = 'completed' AND DATE(created_at) = CURDATE()",
                'earnings_today' => "2% commission from completed orders today"
            ]
        ],
        'raw_values' => [
            'pending_raw' => (int)$pending,
            'completed_today_raw' => (int)$completedToday,
            'earnings_today_raw' => (float)$earningsToday
        ],
        'stats' => [
            'pending' => (int)$pending,
            'completed_today' => (int)$completedToday,
            'earnings_today' => number_format($earningsToday, 2),
            'rating' => number_format($rating, 1),
            'total_completed' => (int)$totalCompleted
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?>