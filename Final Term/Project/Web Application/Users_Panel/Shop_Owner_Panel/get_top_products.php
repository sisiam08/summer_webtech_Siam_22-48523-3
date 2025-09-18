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

    // Query to get top selling products with category information
    $sql = "SELECT 
                p.product_name as name,
                c.category_name,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * oi.price) as revenue
            FROM products p
            JOIN order_items oi ON p.product_id = oi.product_id
            JOIN orders o ON oi.order_id = o.order_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.shop_id = :shop_id
            AND o.order_status NOT IN ('cancelled', 'refunded')
            $date_condition
            GROUP BY p.product_id, p.product_name, c.category_name
            ORDER BY quantity_sold DESC, revenue DESC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':shop_id', $shop_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results for display
    $top_products = [];
    foreach ($results as $row) {
        $top_products[] = [
            'name' => htmlspecialchars($row['name']),
            'category_name' => htmlspecialchars($row['category_name'] ?? 'Uncategorized'),
            'quantity_sold' => intval($row['quantity_sold']),
            'revenue' => floatval($row['revenue'])
        ];
    }
    
    // If no data found, return empty array
    if (empty($top_products)) {
        $top_products = [];
    }
    
    echo json_encode($top_products);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>