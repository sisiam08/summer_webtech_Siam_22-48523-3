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

// Get products with pagination
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$vendor = $_GET['vendor'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category;
}

if (!empty($vendor)) {
    $whereConditions[] = "p.vendor_id = ?";
    $params[] = $vendor;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total products for pagination
$countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalProducts = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products for current page
$sql = "SELECT p.*, c.name as category, v.name as vendor 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN vendors v ON p.vendor_id = v.id
        $whereClause
        ORDER BY p.id DESC
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
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Return products with pagination info
echo json_encode([
    'products' => $products,
    'total' => $totalProducts,
    'current_page' => (int)$page,
    'total_pages' => $totalPages,
    'per_page' => (int)$limit
]);
?>
