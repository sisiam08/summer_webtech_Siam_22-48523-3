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
            $date_format = '%Y-%m-%d';
            break;
        case 'month':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $date_format = '%Y-%m-%d';
            break;
        case 'quarter':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            $date_format = '%Y-%m';
            break;
        case 'year':
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            $date_format = '%Y-%m';
            break;
        default:
            $date_condition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $date_format = '%Y-%m-%d';
    }

    // Query to get sales data grouped by date
    $sql = "SELECT 
                DATE_FORMAT(o.order_date, '$date_format') as period_label,
                SUM(oi.quantity * oi.price) as total_sales
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE p.shop_id = :shop_id 
            AND o.order_status NOT IN ('cancelled', 'refunded')
            $date_condition
            GROUP BY period_label
            ORDER BY period_label ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':shop_id', $shop_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for Chart.js
    $labels = [];
    $salesData = [];
    
    foreach ($results as $row) {
        $labels[] = $row['period_label'];
        $salesData[] = floatval($row['total_sales']);
    }
    
    // If no data found, provide empty arrays
    if (empty($labels)) {
        $labels = ['No Data'];
        $salesData = [0];
    }
    
    echo json_encode([
        'labels' => $labels,
        'salesData' => $salesData,
        'period' => $period
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>