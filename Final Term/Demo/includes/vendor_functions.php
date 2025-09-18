<?php
/**
 * Admin Functions for Vendor Management
 * Helper functions for admin to manage vendors/shop owners
 */

/**
 * Get all vendors with basic information
 * @param int $limit Limit the number of vendors returned
 * @param int $offset Offset for pagination
 * @param string $search Search term to filter vendors
 * @return array Array of vendors
 */
function getAllVendors($limit = null, $offset = 0, $search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT v.*, 
               u.name as owner_name, 
               u.email as email,
               (SELECT COUNT(*) FROM products WHERE vendor_id = v.id) as product_count
        FROM vendors v
        JOIN users u ON v.user_id = u.id
    ";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = '%' . $search . '%';
        $query .= " WHERE v.shop_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?";
    }
    
    $query .= " ORDER BY v.id DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($search) && $limit !== null) {
        $stmt->bind_param("sssii", $search, $search, $search, $offset, $limit);
    } elseif (!empty($search)) {
        $stmt->bind_param("sss", $search, $search, $search);
    } elseif ($limit !== null) {
        $stmt->bind_param("ii", $offset, $limit);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch vendors
    $vendors = [];
    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $vendors;
}

/**
 * Get vendor by ID with detailed information
 * @param int $id Vendor ID
 * @return array|null Vendor data or null if not found
 */
function getVendorById($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT v.*, 
               u.name as owner_name, 
               u.email as email,
               (SELECT COUNT(*) FROM products WHERE vendor_id = v.id) as product_count,
               (SELECT COUNT(*) FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = v.id) as order_count
        FROM vendors v
        JOIN users u ON v.user_id = u.id
        WHERE v.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $vendor = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $vendor;
}

/**
 * Add a new vendor
 * @param array $data Vendor data
 * @return int|bool New vendor ID on success, false on failure
 */
function addVendor($data) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First create a user account
        $password = password_hash(generateRandomPassword(), PASSWORD_DEFAULT);
        
        $userStmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, 'vendor', 'active')
        ");
        
        $ownerName = $data['owner_name'];
        $email = $data['email'];
        
        $userStmt->bind_param("sss", $ownerName, $email, $password);
        $userStmt->execute();
        
        $userId = $userStmt->insert_id;
        $userStmt->close();
        
        // Then create the vendor record
        $vendorStmt = $conn->prepare("
            INSERT INTO vendors (user_id, shop_name, description, phone, address, logo, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $shopName = $data['shop_name'];
        $description = $data['description'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $logo = $data['logo'] ?? '';
        $status = $data['status'] ?? 'pending';
        
        $vendorStmt->bind_param("issssss", $userId, $shopName, $description, $phone, $address, $logo, $status);
        $vendorStmt->execute();
        
        $vendorId = $vendorStmt->insert_id;
        $vendorStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return $vendorId;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error adding vendor: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing vendor
 * @param int $id Vendor ID
 * @param array $data Vendor data
 * @return bool True on success, false on failure
 */
function updateVendor($id, $data) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get the vendor to get the user_id
        $vendorStmt = $conn->prepare("SELECT user_id FROM vendors WHERE id = ?");
        $vendorStmt->bind_param("i", $id);
        $vendorStmt->execute();
        $vendorResult = $vendorStmt->get_result();
        
        if ($vendorResult->num_rows !== 1) {
            $vendorStmt->close();
            $conn->rollback();
            $conn->close();
            return false;
        }
        
        $vendor = $vendorResult->fetch_assoc();
        $userId = $vendor['user_id'];
        $vendorStmt->close();
        
        // Update user information
        $userStmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?
            WHERE id = ?
        ");
        
        $ownerName = $data['owner_name'];
        $email = $data['email'];
        
        $userStmt->bind_param("ssi", $ownerName, $email, $userId);
        $userStmt->execute();
        $userStmt->close();
        
        // Update vendor information
        $vendorUpdateStmt = $conn->prepare("
            UPDATE vendors 
            SET shop_name = ?, description = ?, phone = ?, address = ?, logo = ?, status = ?
            WHERE id = ?
        ");
        
        $shopName = $data['shop_name'];
        $description = $data['description'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $logo = $data['logo'] ?? '';
        $status = $data['status'] ?? 'pending';
        
        $vendorUpdateStmt->bind_param("ssssssi", $shopName, $description, $phone, $address, $logo, $status, $id);
        $vendorUpdateStmt->execute();
        $vendorUpdateStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error updating vendor: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update vendor status
 * @param int $id Vendor ID
 * @param string $action Action to perform (activate/suspend)
 * @return bool True on success, false on failure
 */
function updateVendorStatus($id, $action) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user_id from vendor
        $vendorStmt = $conn->prepare("SELECT user_id FROM vendors WHERE id = ?");
        $vendorStmt->bind_param("i", $id);
        $vendorStmt->execute();
        $vendorResult = $vendorStmt->get_result();
        
        if ($vendorResult->num_rows !== 1) {
            $vendorStmt->close();
            $conn->rollback();
            $conn->close();
            return false;
        }
        
        $vendor = $vendorResult->fetch_assoc();
        $userId = $vendor['user_id'];
        $vendorStmt->close();
        
        // Determine new status
        $status = ($action === 'activate') ? 'active' : 'suspended';
        $userStatus = ($action === 'activate') ? 'active' : 'inactive';
        
        // Update vendor status
        $vendorUpdateStmt = $conn->prepare("UPDATE vendors SET status = ? WHERE id = ?");
        $vendorUpdateStmt->bind_param("si", $status, $id);
        $vendorUpdateStmt->execute();
        $vendorUpdateStmt->close();
        
        // Update user status
        $userUpdateStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $userUpdateStmt->bind_param("si", $userStatus, $userId);
        $userUpdateStmt->execute();
        $userUpdateStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error updating vendor status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get vendor products
 * @param int $vendorId Vendor ID
 * @param int $limit Limit the number of products returned
 * @param int $offset Offset for pagination
 * @return array Array of products
 */
function getVendorProducts($vendorId, $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.vendor_id = ?
    ";
    
    $query .= " ORDER BY p.id DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if ($limit !== null) {
        $stmt->bind_param("iii", $vendorId, $offset, $limit);
    } else {
        $stmt->bind_param("i", $vendorId);
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
 * Get vendor orders
 * @param int $vendorId Vendor ID
 * @param int $limit Limit the number of orders returned
 * @param int $offset Offset for pagination
 * @return array Array of orders
 */
function getVendorOrders($vendorId, $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    // Build query for orders that contain products from this vendor
    $query = "
        SELECT DISTINCT o.id, o.user_id, o.total, o.status, o.payment_status, 
               o.created_at, u.name as customer_name, u.email as customer_email
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.vendor_id = ?
    ";
    
    $query .= " ORDER BY o.created_at DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if ($limit !== null) {
        $stmt->bind_param("iii", $vendorId, $offset, $limit);
    } else {
        $stmt->bind_param("i", $vendorId);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch orders
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Get order items for this vendor
        $orderItemsQuery = "
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND p.vendor_id = ?
        ";
        
        $itemsStmt = $conn->prepare($orderItemsQuery);
        $itemsStmt->bind_param("ii", $row['id'], $vendorId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        $vendorTotal = 0;
        
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
            $vendorTotal += $item['price'] * $item['quantity'];
        }
        
        $itemsStmt->close();
        
        // Add items and vendor-specific total to order
        $row['items'] = $items;
        $row['vendor_total'] = $vendorTotal;
        
        $orders[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $orders;
}

/**
 * Generate a random password
 * @param int $length Password length
 * @return string Random password
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Get dashboard statistics for vendors
 * @return array Statistics for the admin dashboard
 */
function getVendorStats() {
    $conn = getDbConnection();
    
    // Get total vendors
    $vendorQuery = "SELECT COUNT(*) as total FROM vendors";
    $vendorResult = $conn->query($vendorQuery);
    $vendorRow = $vendorResult->fetch_assoc();
    $totalVendors = $vendorRow['total'];
    
    // Get active vendors
    $activeQuery = "SELECT COUNT(*) as total FROM vendors WHERE status = 'active'";
    $activeResult = $conn->query($activeQuery);
    $activeRow = $activeResult->fetch_assoc();
    $activeVendors = $activeRow['total'];
    
    // Get pending vendors
    $pendingQuery = "SELECT COUNT(*) as total FROM vendors WHERE status = 'pending'";
    $pendingResult = $conn->query($pendingQuery);
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingVendors = $pendingRow['total'];
    
    // Get top vendors by sales
    $topVendorsQuery = "
        SELECT v.id, v.shop_name, COUNT(DISTINCT o.id) as order_count, 
               SUM(oi.price * oi.quantity) as total_sales
        FROM vendors v
        JOIN products p ON p.vendor_id = v.id
        JOIN order_items oi ON oi.product_id = p.id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status != 'cancelled'
        GROUP BY v.id
        ORDER BY total_sales DESC
        LIMIT 5
    ";
    
    $topVendorsResult = $conn->query($topVendorsQuery);
    $topVendors = [];
    
    while ($row = $topVendorsResult->fetch_assoc()) {
        $topVendors[] = $row;
    }
    
    // Close connection
    $conn->close();
    
    return [
        'totalVendors' => $totalVendors,
        'activeVendors' => $activeVendors,
        'pendingVendors' => $pendingVendors,
        'topVendors' => $topVendors
    ];
}
?>
