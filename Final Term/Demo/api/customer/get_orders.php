<?php
// Get customer orders with pagination, filtering, and searching
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Get customer ID
$customer_id = $_SESSION['user_id'];

try {
    // Default pagination values
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, 
                COUNT(oi.id) as item_count
              FROM orders o
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE o.customer_id = :customer_id";
    
    $countQuery = "SELECT COUNT(*) FROM orders WHERE customer_id = :customer_id";
    
    $params = [':customer_id' => $customer_id];
    
    // Add search filter if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $query .= " AND o.order_number LIKE :search";
        $countQuery .= " AND order_number LIKE :search";
        $params[':search'] = $search;
    }
    
    // Add status filter if provided
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = $_GET['status'];
        $query .= " AND o.status = :status";
        $countQuery .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Add date filter if provided
    if (isset($_GET['days']) && !empty($_GET['days'])) {
        $days = intval($_GET['days']);
        $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        $countQuery .= " AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        $params[':days'] = $days;
    }
    
    // Group by and order by
    $query .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
    
    // Get total count of orders
    $stmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        if ($key !== ':limit' && $key !== ':offset') {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $totalOrders = $stmt->fetchColumn();
    
    // Get orders with pagination
    $stmt = $conn->prepare($query);
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    $orderIds = array_column($orders, 'id');
    
    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsQuery = "SELECT oi.order_id, oi.quantity, p.id as product_id, p.name, p.price, p.image
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id IN ($placeholders)
                      ORDER BY oi.id";
        
        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute($orderIds);
        $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group items by order_id
        $orderItems = [];
        foreach ($allItems as $item) {
            $orderItems[$item['order_id']][] = [
                'id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'image' => $item['image']
            ];
        }
        
        // Add items to each order
        foreach ($orders as &$order) {
            $order['items'] = isset($orderItems[$order['id']]) ? $orderItems[$order['id']] : [];
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'orders' => $orders,
        'totalOrders' => $totalOrders,
        'currentPage' => $page,
        'totalPages' => ceil($totalOrders / $limit)
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
