<?php
/**
 * Admin Functions
 * Helper functions for admin functionality
 */

/**
 * Get all products with category information
 * @param int $limit Limit the number of products returned
 * @param int $offset Offset for pagination
 * @param string $search Search term to filter products
 * @return array Array of products
 */
function getAllProducts($limit = null, $offset = 0, $search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
    ";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = '%' . $search . '%';
        $query .= " WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?";
    }
    
    $query .= " ORDER BY p.id DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($search) && $limit !== null) {
        $stmt->bind_param("sssis", $search, $search, $search, $offset, $limit);
    } elseif (!empty($search)) {
        $stmt->bind_param("sss", $search, $search, $search);
    } elseif ($limit !== null) {
        $stmt->bind_param("ii", $offset, $limit);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch products
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $products;
}

/**
 * Count total products
 * @param string $search Search term to filter products
 * @return int Total number of products
 */
function countProducts($search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "SELECT COUNT(*) as total FROM products p";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = '%' . $search . '%';
        $query .= " LEFT JOIN categories c ON p.category_id = c.id";
        $query .= " WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($search)) {
        $stmt->bind_param("sss", $search, $search, $search);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $row['total'];
}

/**
 * Get all categories
 * @return array Array of categories
 */
function getAllCategories() {
    $conn = getDbConnection();
    
    // Get categories
    $query = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($query);
    
    // Fetch categories
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    // Close connection
    $conn->close();
    
    return $categories;
}

/**
 * Get product by ID
 * @param int $id Product ID
 * @return array|null Product data or null if not found
 */
function getProductById($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $product = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $product;
}

/**
 * Add a new product
 * @param array $data Product data
 * @return int|bool New product ID on success, false on failure
 */
function addProduct($data) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        INSERT INTO products (name, description, price, discount_price, category_id, stock, image, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Set default values
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $price = floatval($data['price']);
    $discountPrice = !empty($data['discount_price']) ? floatval($data['discount_price']) : null;
    $categoryId = !empty($data['category_id']) ? intval($data['category_id']) : null;
    $stock = !empty($data['stock']) ? intval($data['stock']) : 0;
    $image = $data['image'] ?? '';
    $status = $data['status'] ?? 'active';
    
    // Bind parameters
    $stmt->bind_param("ssddisss", $name, $description, $price, $discountPrice, $categoryId, $stock, $image, $status);
    
    // Execute query
    $success = $stmt->execute();
    $productId = $success ? $stmt->insert_id : false;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $productId;
}

/**
 * Update an existing product
 * @param int $id Product ID
 * @param array $data Product data
 * @return bool True on success, false on failure
 */
function updateProduct($id, $data) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, discount_price = ?, 
            category_id = ?, stock = ?, image = ?, status = ?
        WHERE id = ?
    ");
    
    // Set values
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $price = floatval($data['price']);
    $discountPrice = !empty($data['discount_price']) ? floatval($data['discount_price']) : null;
    $categoryId = !empty($data['category_id']) ? intval($data['category_id']) : null;
    $stock = !empty($data['stock']) ? intval($data['stock']) : 0;
    $image = $data['image'] ?? '';
    $status = $data['status'] ?? 'active';
    
    // Bind parameters
    $stmt->bind_param("ssddiissi", $name, $description, $price, $discountPrice, $categoryId, $stock, $image, $status, $id);
    
    // Execute query
    $success = $stmt->execute();
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $success;
}

/**
 * Delete a product
 * @param int $id Product ID
 * @return bool True on success, false on failure
 */
function deleteProduct($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    // Execute query
    $success = $stmt->execute();
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $success;
}

/**
 * Get all orders with user information
 * @param int $limit Limit the number of orders returned
 * @param int $offset Offset for pagination
 * @param string $status Filter by order status
 * @return array Array of orders
 */
function getAllOrders($limit = null, $offset = 0, $status = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT o.*, u.name as customer_name, u.email as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
    ";
    
    // Add status filter if provided
    if (!empty($status)) {
        $query .= " WHERE o.status = ?";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($status) && $limit !== null) {
        $stmt->bind_param("sii", $status, $offset, $limit);
    } elseif (!empty($status)) {
        $stmt->bind_param("s", $status);
    } elseif ($limit !== null) {
        $stmt->bind_param("ii", $offset, $limit);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch orders
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $orders;
}

/**
 * Get dashboard statistics
 * @return array Statistics for the dashboard
 */
function getDashboardStats() {
    $conn = getDbConnection();
    
    // Get total products
    $productQuery = "SELECT COUNT(*) as total FROM products";
    $productResult = $conn->query($productQuery);
    $productRow = $productResult->fetch_assoc();
    $totalProducts = $productRow['total'];
    
    // Get total orders
    $orderQuery = "SELECT COUNT(*) as total FROM orders";
    $orderResult = $conn->query($orderQuery);
    $orderRow = $orderResult->fetch_assoc();
    $totalOrders = $orderRow['total'];
    
    // Get total users
    $userQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $userResult = $conn->query($userQuery);
    $userRow = $userResult->fetch_assoc();
    $totalUsers = $userRow['total'];
    
    // Get total revenue
    $revenueQuery = "SELECT SUM(total) as total FROM orders WHERE status != 'cancelled'";
    $revenueResult = $conn->query($revenueQuery);
    $revenueRow = $revenueResult->fetch_assoc();
    $totalRevenue = $revenueRow['total'] ?? 0;
    
    // Close connection
    $conn->close();
    
    return [
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'totalUsers' => $totalUsers,
        'totalRevenue' => $totalRevenue
    ];
}

/**
 * Get recent orders for the dashboard
 * @param int $limit Number of orders to return
 * @return array Recent orders
 */
function getRecentOrders($limit = 5) {
    $conn = getDbConnection();
    
    // Get recent orders
    $query = "
        SELECT o.id, o.total, o.status, o.created_at as order_date, u.name as customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch orders
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $orders;
}

/**
 * Log admin activity
 * @param string $action Action performed
 * @param string $details Additional details
 * @return bool True on success, false on failure
 */
function logAdminActivity($action, $details = '') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (user_id, action, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // Set values
    $userId = $_SESSION['user_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Bind parameters
    $stmt->bind_param("issss", $userId, $action, $details, $ipAddress, $userAgent);
    
    // Execute query
    $success = $stmt->execute();
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $success;
}
?>
