<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Connect to database
require_once '../db_connect.php';

try {
    // Get delivery person ID
    $stmt = $conn->prepare("SELECT id FROM delivery_personnel WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Delivery personnel not found'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $delivery_id = $row['id'];
    
    // Get query parameters
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    $min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build the query based on filters
    $query = "
        SELECT d.*, o.order_number, o.created_at, o.total_amount, c.first_name AS customer_first_name, c.last_name AS customer_last_name
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users c ON o.user_id = c.id
        WHERE d.delivery_person_id = ? AND d.status = 'delivered'
    ";
    
    $params = [$delivery_id];
    $types = "i";
    
    // Add date range filter
    if (!empty($from_date)) {
        $query .= " AND DATE(d.delivered_at) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    
    if (!empty($to_date)) {
        $query .= " AND DATE(d.delivered_at) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    
    // Add rating filter
    if ($min_rating > 0) {
        $query .= " AND (d.rating >= ? OR d.rating IS NULL)";
        $params[] = $min_rating;
        $types .= "i";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (o.order_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    // Add order by
    $query .= " ORDER BY d.delivered_at DESC";
    
    // Get total count for pagination
    $countQuery = str_replace("d.*, o.order_number, o.created_at, o.total_amount, c.first_name AS customer_first_name, c.last_name AS customer_last_name", "COUNT(*) as total", $query);
    
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalRows = $result->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);
    
    // Add limit and offset
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $deliveries = [];
    while ($row = $result->fetch_assoc()) {
        // Format customer name
        $row['customer_name'] = $row['customer_first_name'] . ' ' . $row['customer_last_name'];
        
        // Clean up response
        unset($row['customer_first_name']);
        unset($row['customer_last_name']);
        
        $deliveries[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'deliveries' => $deliveries,
        'pagination' => [
            'total' => (int)$totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
