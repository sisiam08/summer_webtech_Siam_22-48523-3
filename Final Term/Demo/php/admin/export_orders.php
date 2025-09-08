<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

// Set header for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV header row
fputcsv($output, [
    'Order ID', 
    'Date', 
    'Customer', 
    'Email', 
    'Phone', 
    'Address', 
    'Status', 
    'Payment Method', 
    'Payment Status', 
    'Subtotal', 
    'Shipping', 
    'Tax', 
    'Total'
]);

// Get filter parameters
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

// Get all orders matching the filters
$sql = "SELECT o.*, u.name as customer_name, u.email, u.phone, 
        a.street, a.city, a.state, a.zip_code, a.country,
        pm.name as payment_method
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
        $whereClause
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Write each order as a CSV row
while ($order = $result->fetch_assoc()) {
    $address = $order['street'] . ', ' . $order['city'] . ', ' . 
               $order['state'] . ', ' . $order['zip_code'] . ', ' . $order['country'];
    
    fputcsv($output, [
        $order['id'],
        $order['order_date'],
        $order['customer_name'],
        $order['email'],
        $order['phone'] ?? 'N/A',
        $address,
        $order['status'],
        $order['payment_method'],
        $order['payment_status'],
        $order['subtotal'],
        $order['shipping_fee'],
        $order['tax'],
        $order['total']
    ]);
}

// Close the output stream
fclose($output);
?>
