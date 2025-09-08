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
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build the query based on filters
    $query = "
        SELECT d.*, o.order_number, o.created_at, o.total_amount, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.phone AS customer_phone,
        a.name AS address_name, a.city, a.state, a.postal_code
        FROM deliveries d
        JOIN orders o ON d.order_id = o.id
        JOIN users c ON o.user_id = c.id
        JOIN addresses a ON o.address_id = a.id
        WHERE d.delivery_person_id = ?
    ";
    
    $params = [$delivery_id];
    $types = "i";
    
    // Add status filter
    if ($status !== 'all') {
        $query .= " AND d.status = ?";
        $params[] = $status;
        $types .= "s";
    } else {
        $query .= " AND d.status IN ('assigned', 'picked_up')";
    }
    
    // Add date filter
    if (!empty($date)) {
        $query .= " AND DATE(d.assigned_at) = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (o.order_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR a.city LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    // Add order by
    $query .= " ORDER BY d.assigned_at DESC";
    
    // Get total count for pagination
    $countQuery = str_replace("d.*, o.order_number, o.created_at, o.total_amount, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.phone AS customer_phone,
        a.name AS address_name, a.city, a.state, a.postal_code", "COUNT(*) as total", $query);
    
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
        
        // Format address
        $row['address'] = $row['address_name'] . ', ' . $row['city'] . ', ' . $row['state'] . ' ' . $row['postal_code'];
        
        // Clean up response
        unset($row['customer_first_name']);
        unset($row['customer_last_name']);
        unset($row['address_name']);
        unset($row['city']);
        unset($row['state']);
        unset($row['postal_code']);
        
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
