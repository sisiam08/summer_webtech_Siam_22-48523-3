<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();

require_once __DIR__ . '/../../Database/database.php';

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get shop ID for the current shop owner
    $shop_query = "SELECT id FROM shops WHERE owner_id = " . intval($_SESSION['user_id']);
    $shop_result = mysqli_query($conn, $shop_query);
    $shop = mysqli_fetch_assoc($shop_result);

    if (!$shop) {
        echo json_encode(['error' => 'No shop found']);
        exit;
    }

    $shopId = $shop['id'];
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Build WHERE conditions
    $where_conditions = ["o.shop_id = " . intval($shopId)];
    
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $where_conditions[] = "(o.id LIKE '%$search_escaped%' OR c.name LIKE '%$search_escaped%' OR c.email LIKE '%$search_escaped%')";
    }

    if ($status !== 'all') {
        $status_escaped = mysqli_real_escape_string($conn, $status);
        $where_conditions[] = "o.status = '$status_escaped'";
    }

    $sql = "SELECT o.id, o.user_id, o.customer_id, o.total_amount, o.status, o.created_at, 
                   c.name, c.email, c.phone, c.address, c.city
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            WHERE " . implode(' AND ', $where_conditions) . " 
            ORDER BY o.created_at DESC LIMIT 50";

    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $orders = [];
    while ($order = mysqli_fetch_assoc($result)) {
        $order['order_date'] = date('M j, Y', strtotime($order['created_at']));
        $order['status'] = ucfirst($order['status']);
        $order['customer_name'] = $order['name'];
        $order['customer_email'] = $order['email'];
        
        // Get order items for this order
        $items_sql = "SELECT oi.order_id, oi.product_id, oi.quantity, oi.price as item_price,
                             p.name as product_name, p.image as product_image, p.price as product_price
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = " . intval($order['id']) . " AND p.shop_id = " . intval($shopId);
        
        $items_result = mysqli_query($conn, $items_sql);
        $order_items = [];
        
        if ($items_result) {
            while ($item = mysqli_fetch_assoc($items_result)) {
                $item['price'] = $item['item_price']; // Use the order item price, not current product price
                $order_items[] = $item;
            }
        }
        
        $order['items'] = $order_items;
        $order['item_count'] = count($order_items);
        
        // Calculate total for items from this shop
        $shop_total = 0;
        foreach ($order_items as $item) {
            $shop_total += $item['quantity'] * $item['item_price'];
        }
        $order['shop_total'] = $shop_total;
        
        $orders[] = $order;
    }

    ob_clean();
    echo json_encode($orders);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
