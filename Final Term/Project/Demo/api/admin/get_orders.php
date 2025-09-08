<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Get orders with pagination
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(o.id LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($date)) {
    $whereConditions[] = "DATE(o.order_date) = ?";
    $params[] = $date;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total orders for pagination
$countSql = "SELECT COUNT(*) as total FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            $whereClause";
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalOrders = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $limit);

// Get orders for current page
$sql = "SELECT o.id, o.order_date, o.total, o.status, o.payment_status, u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params)) . 'ii';
    $allParams = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types, ...$allParams);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Return orders with pagination info
echo json_encode([
    'orders' => $orders,
    'total' => $totalOrders,
    'current_page' => (int)$page,
    'total_pages' => $totalPages,
    'per_page' => (int)$limit
]);
?>
