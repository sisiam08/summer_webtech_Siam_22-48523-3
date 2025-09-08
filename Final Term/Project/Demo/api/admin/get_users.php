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

// Get users with pagination
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role)) {
    $whereConditions[] = "role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total users for pagination
$countSql = "SELECT COUNT(*) as total FROM users $whereClause";
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalUsers = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get users for current page
$sql = "SELECT id, name, email, role, status, created_at FROM users $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
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
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Return users with pagination info
echo json_encode([
    'users' => $users,
    'total' => $totalUsers,
    'current_page' => (int)$page,
    'total_pages' => $totalPages,
    'per_page' => (int)$limit
]);
?>
