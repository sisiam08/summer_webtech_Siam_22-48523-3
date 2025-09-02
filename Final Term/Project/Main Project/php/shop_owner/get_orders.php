<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if shop owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$shop_owner_id = $_SESSION['user_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

try {
    // Build the query based on filters
    $where_conditions = ["p.shop_owner_id = ?"];
    $params = [$shop_owner_id];
    
    if (!empty($search)) {
        $where_conditions[] = "(o.order_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status;
    }
    
    // Add date filter
    if (!empty($date)) {
        $today = date('Y-m-d');
        switch ($date) {
            case 'today':
                $where_conditions[] = "DATE(o.created_at) = ?";
                $params[] = $today;
                break;
            case 'week':
                $startOfWeek = date('Y-m-d', strtotime('monday this week'));
                $where_conditions[] = "DATE(o.created_at) >= ?";
                $params[] = $startOfWeek;
                break;
            case 'month':
                $startOfMonth = date('Y-m-01');
                $where_conditions[] = "DATE(o.created_at) >= ?";
                $params[] = $startOfMonth;
                break;
            case 'year':
                $startOfYear = date('Y-01-01');
                $where_conditions[] = "DATE(o.created_at) >= ?";
                $params[] = $startOfYear;
                break;
        }
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get total count of orders
    $count_sql = "
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE $where_clause
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get orders with pagination
    $orders_sql = "
        SELECT DISTINCT o.id, o.order_number, o.created_at, o.status, o.total_amount,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE $where_clause
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($orders_sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the orders data
    echo json_encode([
        'orders' => $orders,
        'totalOrders' => $total_orders,
        'currentPage' => $page,
        'totalPages' => ceil($total_orders / $limit)
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>
