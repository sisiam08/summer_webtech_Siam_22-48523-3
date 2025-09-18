<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection and functions
include '../../Includes/db_connection.php';
include '../../Includes/functions.php';

try {
    // Get shop ID for the logged-in shop owner
    $shop_id = getShopIdForOwner($_SESSION['user_id']);
    if (!$shop_id) {
        echo json_encode(['error' => 'Shop not found']);
        exit;
    }

    // Get time period from query parameter
    $period = $_GET['period'] ?? 'month';
    
    // Define date range based on period
    $date_condition = '';
    switch ($period) {
        case 'week':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'quarter':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Query to get order status distribution for shop orders
    $sql = "SELECT 
                o.order_status,
                COUNT(DISTINCT o.order_id) as status_count
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE p.shop_id = :shop_id
            $date_condition
            GROUP BY o.order_status
            ORDER BY status_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':shop_id', $shop_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for Chart.js
    $labels = [];
    $counts = [];
    
    // Define status labels mapping for better display
    $status_mapping = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded'
    ];
    
    foreach ($results as $row) {
        $status = $row['order_status'];
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