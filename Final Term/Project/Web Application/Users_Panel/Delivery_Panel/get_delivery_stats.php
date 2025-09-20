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
    
    // Get today's date in PHP timezone for consistent date filtering
    $today = date('Y-m-d');
    
    $userInfo = getCurrentUser();
    
    // 1. PENDING DELIVERIES: Count orders with status 'delivered' assigned to this delivery man
    // These are orders ready for delivery pickup/completion
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
        AND DATE(delivery_time) = ?
    ");
    $completedTodayStmt->execute([$deliveryPersonId, $today]);
    $completedToday = $completedTodayStmt->fetch(PDO::FETCH_ASSOC)['completed_today_count'];
    
    // 3. TOTAL COMPLETED DELIVERIES: All completed orders by this delivery man
    $totalCompletedStmt = $conn->prepare("
        SELECT COUNT(*) as total_completed
        FROM orders 
        WHERE delivery_person_id = ? AND status = 'completed'
    ");
    $totalCompletedStmt->execute([$deliveryPersonId]);
    $totalCompleted = $totalCompletedStmt->fetch(PDO::FETCH_ASSOC)['total_completed'];
    
    // 4. TODAY'S EARNINGS: Commission from orders completed TODAY
    // Using a simpler approach: 2% of total order amount for completed orders today
    $earningsTodayStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount * 0.02), 0) as earnings_today
        FROM orders
        WHERE delivery_person_id = ? 
        AND status = 'completed' 
        AND DATE(delivery_time) = ?
    ");
    $earningsTodayStmt->execute([$deliveryPersonId, $today]);
    $earningsToday = $earningsTodayStmt->fetch(PDO::FETCH_ASSOC)['earnings_today'];
    
    // 5. TOTAL EARNINGS: Commission from all completed orders
    $totalEarningsStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount * 0.02), 0) as total_earnings
        FROM orders
        WHERE delivery_person_id = ? 
        AND status = 'completed'
    ");
    $totalEarningsStmt->execute([$deliveryPersonId]);
    $totalEarnings = $totalEarningsStmt->fetch(PDO::FETCH_ASSOC)['total_earnings'];
    
    // Calculate a simple rating based on completed deliveries (5.0 - 0.1 for every 10 deliveries, min 4.0)
    $rating = max(4.0, 5.0 - (floor($totalCompleted / 10) * 0.1));
    
    $response = [
        'success' => true,
        'raw_values' => [
            'pending_raw' => (int)$pending,
            'completed_today_raw' => (int)$completedToday,
            'total_completed_raw' => (int)$totalCompleted,
            'earnings_today_raw' => (float)$earningsToday,
            'total_earnings_raw' => (float)$totalEarnings
        ],
        'stats' => [
            'pending' => (int)$pending,
            'completed_today' => (int)$completedToday,
            'earnings_today' => number_format(ceil($earningsToday), 0),
            'rating' => number_format($rating, 1),
            'total_completed' => (int)$totalCompleted,
            'total_earnings' => number_format(ceil($totalEarnings), 0)
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