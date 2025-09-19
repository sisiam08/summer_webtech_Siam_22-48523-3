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

    // Query to get top selling products
    $sql = "SELECT 
                p.name,
                p.id,
                c.name as category_name,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * oi.price) as revenue
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.shop_id = ? 
            AND o.status NOT IN ('cancelled', 'refunded')
            $date_condition
            GROUP BY p.id, p.name, c.name
            ORDER BY quantity_sold DESC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$shop_id]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results
    $topProducts = [];
    foreach ($results as $row) {
        $topProducts[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'quantity_sold' => intval($row['quantity_sold']),
            'revenue' => intval(ceil($row['revenue']))
        ];
    }
    
    echo json_encode($topProducts);

} catch (PDOException $e) {
    error_log('Database error in get_top_products.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Error in get_top_products.php: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>
