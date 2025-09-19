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
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $conn = connectDB();
    
    // Get shop ID for the current shop owner
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
    
    // Define date range and format based on period
    $date_condition = '';
    $date_format = '';
    switch ($period) {
        case 'week':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $date_format = '%Y-%m-%d';
            break;
        case 'month':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $date_format = '%Y-%m-%d';
            break;
        case 'quarter':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            $date_format = '%Y-%m';
            break;
        case 'year':
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            $date_format = '%Y-%m';
            break;
        default:
            $date_condition = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $date_format = '%Y-%m-%d';
    }

    // Query to get sales data grouped by date
    $sql = "SELECT 
                DATE_FORMAT(o.created_at, '$date_format') as period_label,
                COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.shop_id = ? 
            AND o.status NOT IN ('cancelled', 'refunded')
            $date_condition
            GROUP BY period_label
            ORDER BY period_label ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$shop_id]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for Chart.js
    $labels = [];
    $salesData = [];
    
    foreach ($results as $row) {
        $labels[] = $row['period_label'];
        $salesData[] = intval(ceil($row['total_sales']));
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
    error_log('Database error in get_sales_data.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Error in get_sales_data.php: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>
