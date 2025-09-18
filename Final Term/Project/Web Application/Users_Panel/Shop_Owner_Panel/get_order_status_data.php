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

// Check if user is logged in and is a shop owner
if (!isLoggedIn() || !isShopOwner()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $conn = connectDB();
    
    // Get shop ID for the current shop owner (same logic as get_orders.php)
    $stmt = $conn->prepare("SELECT id FROM shops WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode(['error' => 'Shop not found']);
        exit();
    }
    
    $shop_id = $shop['id'];

    // Get time period from query parameter
    $period = $_GET['period'] ?? 'month';
    
    // Define date range based on period
    $date_condition = '';
    switch ($period) {
        case 'week':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'quarter':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Query to get order status distribution for shop orders (matching get_orders.php schema)
    $sql = "SELECT 
                o.status,
                COUNT(o.id) as status_count
            FROM orders o
            WHERE o.shop_id = ?
            $date_condition
            GROUP BY o.status
            ORDER BY status_count DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$shop_id]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for Chart.js
    $labels = [];
    $counts = [];
    
    // Define status labels mapping for better display
    $status_mapping = [
        'Pending' => 'Pending',
        'Processing' => 'Processing',
        'Shipped' => 'Shipped',
        'Delivered' => 'Delivered',
        'Cancelled' => 'Cancelled',
        'Refunded' => 'Refunded',
        'Completed' => 'Completed'
    ];
    
    foreach ($results as $row) {
        $status = $row['status'];
        $display_label = $status_mapping[$status] ?? ucfirst($status);
        
        $labels[] = $display_label;
        $counts[] = intval($row['status_count']);
    }
    
    // If no data found, provide default structure
    if (empty($labels)) {
        $labels = ['No Orders'];
        $counts = [0];
    }
    
    echo json_encode([
        'labels' => $labels,
        'counts' => $counts,
        'period' => $period
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>