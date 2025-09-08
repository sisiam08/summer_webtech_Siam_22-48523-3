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

// Check if the vendors table exists
$checkTable = "SHOW TABLES LIKE 'vendors'";
$result = $conn->query($checkTable);

if ($result->num_rows == 0) {
    // Create vendors table
    $createTable = "CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        description TEXT,
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable) === TRUE) {
        // Add vendor_id column to products table if it doesn't exist
        $checkColumn = "SHOW COLUMNS FROM products LIKE 'vendor_id'";
        $columnResult = $conn->query($checkColumn);
        
        if ($columnResult->num_rows == 0) {
            $addColumn = "ALTER TABLE products ADD COLUMN vendor_id INT DEFAULT NULL, 
                          ADD FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL";
            $conn->query($addColumn);
        }
        
        // Insert sample vendors
        $insertVendors = "INSERT INTO vendors (name, email, phone, description, commission_rate) VALUES
            ('Farm Fresh Produce', 'farmfresh@example.com', '123-456-7890', 'Local farm offering fresh fruits and vegetables', 8.5),
            ('Organic Dairy Co.', 'dairy@example.com', '234-567-8901', 'Organic dairy products from grass-fed cows', 9.0),
            ('Artisan Bakery', 'bakery@example.com', '345-678-9012', 'Handcrafted breads and pastries', 10.0)";
        $conn->query($insertVendors);
        
        // Update some products to assign vendors
        if ($conn->query($insertVendors) === TRUE) {
            $updateProducts = "UPDATE products SET vendor_id = 1 WHERE category_id IN (1, 2);
                              UPDATE products SET vendor_id = 2 WHERE category_id = 3;
                              UPDATE products SET vendor_id = 3 WHERE category_id = 4";
            $conn->multi_query($updateProducts);
        }
    }
}

// Get parameters from the request
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Set limit for pagination
$limit = 10;
$offset = ($page - 1) * $limit;

// Build the SQL query based on filters
$sql = "SELECT v.*, COUNT(p.id) as product_count 
        FROM vendors v 
        LEFT JOIN products p ON v.id = p.vendor_id 
        WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM vendors WHERE 1=1";

$params = [];
$types = "";

// Add search filter if provided
if (!empty($search)) {
    $sql .= " AND (v.name LIKE ? OR v.email LIKE ? OR v.phone LIKE ?)";
    $countSql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    array_push($params, $searchParam, $searchParam, $searchParam);
    $types .= "sss";
}

// Add status filter if provided
if ($status !== '') {
    $sql .= " AND v.is_active = ?";
    $countSql .= " AND is_active = ?";
    array_push($params, $status);
    $types .= "i";
}

// Add grouping
$sql .= " GROUP BY v.id";

// Add ordering
$sql .= " ORDER BY v.id DESC LIMIT ? OFFSET ?";
array_push($params, $limit, $offset);
$types .= "ii";

// Prepare and execute the count query
$countStmt = $conn->prepare($countSql);
if (!empty($params) && count($params) > 0 && !str_ends_with($types, "ii")) {
    // Remove the "ii" from the types string and the last two parameters
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    $bindParams = array_merge([$countTypes], $countParams);
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

// Fetch vendors
$vendors = [];
while ($row = $result->fetch_assoc()) {
    $vendors[] = $row;
}

// Return JSON response
echo json_encode([
    'vendors' => $vendors,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalRecords' => $totalRows
]);
?>
$sql = "SELECT * FROM vendors ORDER BY name ASC";
$vendors = fetchAll($sql);

// Return vendors
echo json_encode($vendors);
?>
