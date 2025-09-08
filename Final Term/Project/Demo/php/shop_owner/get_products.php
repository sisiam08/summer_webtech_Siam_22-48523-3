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
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Build the query based on filters
    $where_conditions = ["p.shop_owner_id = ?"];
    $params = [$shop_owner_id];
    
    if (!empty($search)) {
        $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category;
    }
    
    if (!empty($status)) {
        switch ($status) {
            case 'in-stock':
                $where_conditions[] = "p.stock > 10";
                break;
            case 'low-stock':
                $where_conditions[] = "p.stock > 0 AND p.stock <= 10";
                break;
            case 'out-of-stock':
                $where_conditions[] = "p.stock <= 0";
                break;
        }
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count of products
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM products p
        $where_clause
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get products with pagination
    $products_sql = "
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.id DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($products_sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the products data
    echo json_encode([
        'products' => $products,
        'totalProducts' => $total_products,
        'currentPage' => $page,
        'totalPages' => ceil($total_products / $limit)
    ]);
    
} catch (PDOException $e) {
    // Log the error and return an error message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
