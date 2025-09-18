<?php
/**
 * Admin Functions for Shop Management
 * Helper functions for admin to manage shops/shop owners
 */

/**
 * Get all shops with basic information
 * @param int $limit Limit the number of shops returned
 * @param int $offset Offset for pagination
 * @param string $search Search term to filter shops
 * @return array Array of shops
 */
function getAllShops($limit = null, $offset = 0, $search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT v.*, 
               u.name as owner_name, 
               u.email as email,
               (SELECT COUNT(*) FROM products WHERE shop_id = v.id) as product_count
        FROM shops v
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
    
    // Fetch shops
    $shops = [];
    while ($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $shops;
}

/**
 * Get shop by ID with detailed information
 * @param int $id Shop ID
 * @return array|null Shop data or null if not found
 */
function getShopById($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT v.*, 
               u.name as owner_name, 
               u.email as email,
               (SELECT COUNT(*) FROM products WHERE shop_id = v.id) as product_count,
               (SELECT COUNT(*) FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.shop_id = v.id) as order_count
        FROM shops v
        JOIN users u ON v.user_id = u.id
        WHERE v.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $shop = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $shop;
}

/**
 * Add a new shop
 * @param array $data Shop data
 * @return int|bool New shop ID on success, false on failure
 */
function addShop($data) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First create a user account
        $password = password_hash(generateRandomPassword(), PASSWORD_DEFAULT);
        
        $userStmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, 'shop', 'active')
        ");
        
        $ownerName = $data['owner_name'];
        $email = $data['email'];
        
        $userStmt->bind_param("sss", $ownerName, $email, $password);
        $userStmt->execute();
        
        $userId = $userStmt->insert_id;
        $userStmt->close();
        
        // Then create the shop record
        $shopStmt = $conn->prepare("
            INSERT INTO shops (user_id, shop_name, description, phone, address, logo, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $shopName = $data['shop_name'];
        $description = $data['description'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $logo = $data['logo'] ?? '';
        $status = $data['status'] ?? 'pending';
        
        $shopStmt->bind_param("issssss", $userId, $shopName, $description, $phone, $address, $logo, $status);
        $shopStmt->execute();
        
        $shopId = $shopStmt->insert_id;
        $shopStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return $shopId;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error adding shop: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing shop
 * @param int $id Shop ID
 * @param array $data Shop data
 * @return bool True on success, false on failure
 */
function updateShop($id, $data) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get the shop to get the user_id
        $shopStmt = $conn->prepare("SELECT user_id FROM shops WHERE id = ?");
        $shopStmt->bind_param("i", $id);
        $shopStmt->execute();
        $shopResult = $shopStmt->get_result();
        
        if ($shopResult->num_rows !== 1) {
            $shopStmt->close();
            $conn->rollback();
            $conn->close();
            return false;
        }
        
        $shop = $shopResult->fetch_assoc();
        $userId = $shop['user_id'];
        $shopStmt->close();
        
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
        
        // Update shop information
        $shopUpdateStmt = $conn->prepare("
            UPDATE shops 
            SET shop_name = ?, description = ?, phone = ?, address = ?, logo = ?, status = ?
            WHERE id = ?
        ");
        
        $shopName = $data['shop_name'];
        $description = $data['description'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $logo = $data['logo'] ?? '';
        $status = $data['status'] ?? 'pending';
        
        $shopUpdateStmt->bind_param("ssssssi", $shopName, $description, $phone, $address, $logo, $status, $id);
        $shopUpdateStmt->execute();
        $shopUpdateStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error updating shop: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update shop status
 * @param int $id Shop ID
 * @param string $action Action to perform (activate/suspend)
 * @return bool True on success, false on failure
 */
function updateShopStatus($id, $action) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user_id from shop
        $shopStmt = $conn->prepare("SELECT user_id FROM shops WHERE id = ?");
        $shopStmt->bind_param("i", $id);
        $shopStmt->execute();
        $shopResult = $shopStmt->get_result();
        
        if ($shopResult->num_rows !== 1) {
            $shopStmt->close();
            $conn->rollback();
            $conn->close();
            return false;
        }
        
        $shop = $shopResult->fetch_assoc();
        $userId = $shop['user_id'];
        $shopStmt->close();
        
        // Determine new status
        $status = ($action === 'activate') ? 'active' : 'suspended';
        $userStatus = ($action === 'activate') ? 'active' : 'inactive';
        
        // Update shop status
        $shopUpdateStmt = $conn->prepare("UPDATE shops SET status = ? WHERE id = ?");
        $shopUpdateStmt->bind_param("si", $status, $id);
        $shopUpdateStmt->execute();
        $shopUpdateStmt->close();
        
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
        
        error_log('Error updating shop status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get shop products
 * @param int $shopId Shop ID
 * @param int $limit Limit the number of products returned
 * @param int $offset Offset for pagination
 * @return array Array of products
 */
function getShopProducts($shopId, $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.shop_id = ?
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
        $stmt->bind_param("iii", $shopId, $offset, $limit);
    } else {
        $stmt->bind_param("i", $shopId);
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
 * Get shop orders
 * @param int $shopId Shop ID
 * @param int $limit Limit the number of orders returned
 * @param int $offset Offset for pagination
 * @return array Array of orders
 */
function getShopOrders($shopId, $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    // Build query for orders that contain products from this shop
    $query = "
        SELECT DISTINCT o.id, o.user_id, o.total, o.status, o.payment_status, 
               o.created_at, u.name as customer_name, u.email as customer_email
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.shop_id = ?
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
        $stmt->bind_param("iii", $shopId, $offset, $limit);
    } else {
        $stmt->bind_param("i", $shopId);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch orders
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Get order items for this shop
        $orderItemsQuery = "
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND p.shop_id = ?
        ";
        
        $itemsStmt = $conn->prepare($orderItemsQuery);
        $itemsStmt->bind_param("ii", $row['id'], $shopId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        $shopTotal = 0;
        
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
            $shopTotal += $item['price'] * $item['quantity'];
        }
        
        $itemsStmt->close();
        
        // Add items and shop-specific total to order
        $row['items'] = $items;
        $row['shop_total'] = $shopTotal;
        
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
 * Get dashboard statistics for shops
 * @return array Statistics for the admin dashboard
 */
function getShopStats() {
    $conn = getDbConnection();
    
    // Get total shops
    $shopQuery = "SELECT COUNT(*) as total FROM shops";
    $shopResult = $conn->query($shopQuery);
    $shopRow = $shopResult->fetch_assoc();
    $totalShops = $shopRow['total'];
    
    // Get active shops
    $activeQuery = "SELECT COUNT(*) as total FROM shops WHERE status = 'active'";
    $activeResult = $conn->query($activeQuery);
    $activeRow = $activeResult->fetch_assoc();
    $activeShops = $activeRow['total'];
    
    // Get pending shops
    $pendingQuery = "SELECT COUNT(*) as total FROM shops WHERE status = 'pending'";
    $pendingResult = $conn->query($pendingQuery);
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingShops = $pendingRow['total'];
    
    // Get top shops by sales
    $topShopsQuery = "
        SELECT v.id, v.shop_name, COUNT(DISTINCT o.id) as order_count, 
               SUM(oi.price * oi.quantity) as total_sales
        FROM shops v
        JOIN products p ON p.shop_id = v.id
        JOIN order_items oi ON oi.product_id = p.id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status != 'cancelled'
        GROUP BY v.id
        ORDER BY total_sales DESC
        LIMIT 5
    ";
    
    $topShopsResult = $conn->query($topShopsQuery);
    $topShops = [];
    
    while ($row = $topShopsResult->fetch_assoc()) {
        $topShops[] = $row;
    }
    
    // Close connection
    $conn->close();
    
    return [
        'totalShops' => $totalShops,
        'activeShops' => $activeShops,
        'pendingShops' => $pendingShops,
        'topShops' => $topShops
    ];
}
?>
