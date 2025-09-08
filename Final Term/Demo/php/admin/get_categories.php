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

// Get parameters from the request
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Set limit for pagination
$limit = 10;
$offset = ($page - 1) * $limit;

// Build the SQL query based on filters
$sql = "SELECT * FROM categories WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM categories WHERE 1=1";

$params = [];
$types = "";

// Add search filter if provided
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $countSql .= " AND (name LIKE ? OR description LIKE ?)";
    $searchParam = "%$search%";
    array_push($params, $searchParam, $searchParam);
    $types .= "ss";
}

// Add status filter if provided
if ($status !== '') {
    $sql .= " AND is_active = ?";
    $countSql .= " AND is_active = ?";
    array_push($params, $status);
    $types .= "i";
}

// Add ordering
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
array_push($params, $limit, $offset);
$types .= "ii";

// Prepare and execute the count query
$countStmt = $conn->prepare($countSql);
if (!empty($params) && count($params) > 0 && !str_ends_with($types, "ii")) {
    $bindParams = array_merge([$types], $params);
    $countStmt->bind_param(...$bindParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Prepare and execute the main query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $bindParams = array_merge([$types], $params);
    $stmt->bind_param(...$bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch categories
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Return JSON response
echo json_encode([
    'categories' => $categories,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalRecords' => $totalRows
]);
?>
