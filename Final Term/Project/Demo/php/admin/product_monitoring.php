<?php
/**
 * Admin Product Monitoring Functions
 * These functions allow admins to monitor products but not directly manage them
 */

/**
 * Flag a product as inappropriate
 * @param int $productId Product ID
 * @param string $reason Reason for flagging
 * @param string $comments Additional comments
 * @return bool True on success, false on failure
 */
function flagProduct($productId, $reason, $comments = '') {
    $conn = getDbConnection();
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update product status
        $updateStmt = $conn->prepare("
            UPDATE products 
            SET status = 'flagged', 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $updateStmt->bind_param("i", $productId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Log the flag action
        $logStmt = $conn->prepare("
            INSERT INTO product_flags (product_id, flagged_by, reason, comments, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $adminId = $_SESSION['admin_id'] ?? 0;
        $logStmt->bind_param("iiss", $productId, $adminId, $reason, $comments);
        $logStmt->execute();
        $logStmt->close();
        
        // Get vendor info to send notification
        $vendorStmt = $conn->prepare("
            SELECT p.vendor_id, p.name as product_name, v.user_id 
            FROM products p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.id = ?
        ");
        
        $vendorStmt->bind_param("i", $productId);
        $vendorStmt->execute();
        $vendorResult = $vendorStmt->get_result();
        $vendorInfo = $vendorResult->fetch_assoc();
        $vendorStmt->close();
        
        // Send notification to vendor
        if ($vendorInfo) {
            $notifyStmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, link, created_at)
                VALUES (?, 'product_flagged', ?, ?, NOW())
            ");
            
            $vendorUserId = $vendorInfo['user_id'];
            $productName = $vendorInfo['product_name'];
            $message = "Your product \"$productName\" has been flagged for review: $reason";
            $link = "vendor/product_edit.php?id=$productId";
            
            $notifyStmt->bind_param("iss", $vendorUserId, $message, $link);
            $notifyStmt->execute();
            $notifyStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error flagging product: ' . $e->getMessage());
        return false;
    }
}

/**
 * Unflag a product (remove flag)
 * @param int $productId Product ID
 * @return bool True on success, false on failure
 */
function unflagProduct($productId) {
    $conn = getDbConnection();
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update product status back to active
        $updateStmt = $conn->prepare("
            UPDATE products 
            SET status = 'active', 
                updated_at = NOW()
            WHERE id = ? AND status = 'flagged'
        ");
        
        $updateStmt->bind_param("i", $productId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Mark flag as resolved
        $resolveStmt = $conn->prepare("
            UPDATE product_flags 
            SET resolved = 1, 
                resolved_by = ?,
                resolved_at = NOW() 
            WHERE product_id = ? AND resolved = 0
        ");
        
        $adminId = $_SESSION['admin_id'] ?? 0;
        $resolveStmt->bind_param("ii", $adminId, $productId);
        $resolveStmt->execute();
        $resolveStmt->close();
        
        // Get vendor info to send notification
        $vendorStmt = $conn->prepare("
            SELECT p.vendor_id, p.name as product_name, v.user_id 
            FROM products p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.id = ?
        ");
        
        $vendorStmt->bind_param("i", $productId);
        $vendorStmt->execute();
        $vendorResult = $vendorStmt->get_result();
        $vendorInfo = $vendorResult->fetch_assoc();
        $vendorStmt->close();
        
        // Send notification to vendor
        if ($vendorInfo) {
            $notifyStmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, link, created_at)
                VALUES (?, 'product_unflagged', ?, ?, NOW())
            ");
            
            $vendorUserId = $vendorInfo['user_id'];
            $productName = $vendorInfo['product_name'];
            $message = "Your product \"$productName\" has been reviewed and approved.";
            $link = "vendor/products.php";
            
            $notifyStmt->bind_param("iss", $vendorUserId, $message, $link);
            $notifyStmt->execute();
            $notifyStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error unflagging product: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all products with pagination and filtering for admin monitoring
 * This function does not allow direct management, only oversight
 * 
 * @param int $limit Number of products to return
 * @param int $offset Offset for pagination
 * @param string $search Search term
 * @param int $categoryId Filter by category ID
 * @param int $vendorId Filter by vendor ID
 * @param string $status Filter by status
 * @return array Array of products
 */
function getProductsForAdmin($vendorId = 0, $categoryId = 0, $status = '', $search = '', $limit = null, $offset = 0) {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT p.*, 
               c.name AS category_name,
               v.shop_name AS vendor_name,
               v.id AS vendor_id,
               (SELECT COUNT(*) FROM product_flags WHERE product_id = p.id AND resolved = 0) AS flag_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN vendors v ON p.vendor_id = v.id
        WHERE 1=1
    ";
    
    // Add filters
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if ($categoryId > 0) {
        $query .= " AND p.category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    }
    
    if ($vendorId > 0) {
        $query .= " AND p.vendor_id = ?";
        $params[] = $vendorId;
        $types .= "i";
    }
    
    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Add order by
    $query .= " ORDER BY p.updated_at DESC";
    
    // Add limit
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
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
 * Count products based on filters
 * @param string $search Search term
 * @param int $categoryId Filter by category ID
 * @param int $vendorId Filter by vendor ID
 * @param string $status Filter by status
 * @return int Number of products
 */
function countProductsForAdmin($vendorId = 0, $categoryId = 0, $status = '', $search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT COUNT(*) AS total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN vendors v ON p.vendor_id = v.id
        WHERE 1=1
    ";
    
    // Add filters
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if ($categoryId > 0) {
        $query .= " AND p.category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    }
    
    if ($vendorId > 0) {
        $query .= " AND p.vendor_id = ?";
        $params[] = $vendorId;
        $types .= "i";
    }
    
    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $row['total'];
}

/**
 * Get product details by ID with vendor and category information
 * @param int $productId Product ID
 * @return array|null Product details or null if not found
 */
function getProductDetailsForAdmin($productId) {
    $conn = getDbConnection();
    
    // Prepare query
    $query = "
        SELECT p.*, 
               c.name AS category_name,
               v.shop_name AS vendor_name,
               v.id AS vendor_id,
               v.user_id AS vendor_user_id,
               u.name AS vendor_owner_name,
               u.email AS vendor_email,
               (SELECT COUNT(*) FROM product_flags WHERE product_id = p.id AND resolved = 0) AS flag_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN vendors v ON p.vendor_id = v.id
        JOIN users u ON v.user_id = u.id
        WHERE p.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // If product exists, get flags
    if ($product) {
        $flagsQuery = "
            SELECT pf.*, u.name AS flagged_by_name, ur.name AS resolved_by_name
            FROM product_flags pf
            LEFT JOIN users u ON pf.flagged_by = u.id
            LEFT JOIN users ur ON pf.resolved_by = ur.id
            WHERE pf.product_id = ?
            ORDER BY pf.created_at DESC
        ";
        
        $flagsStmt = $conn->prepare($flagsQuery);
        $flagsStmt->bind_param("i", $productId);
        $flagsStmt->execute();
        
        $flagsResult = $flagsStmt->get_result();
        $flags = [];
        
        while ($flag = $flagsResult->fetch_assoc()) {
            $flags[] = $flag;
        }
        
        $product['flags'] = $flags;
        $flagsStmt->close();
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $product;
}

/**
 * Create the product_flags table if it doesn't exist
 */
function ensureProductFlagsTable() {
    $conn = getDbConnection();
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS product_flags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            flagged_by INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            comments TEXT,
            resolved TINYINT(1) DEFAULT 0,
            resolved_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            INDEX (product_id),
            INDEX (flagged_by),
            INDEX (resolved)
        )
    ");
    
    $conn->close();
}

/**
 * Create notifications table if it doesn't exist
 */
function ensureNotificationsTable() {
    $conn = getDbConnection();
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255),
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            INDEX (user_id),
            INDEX (type),
            INDEX (is_read)
        )
    ");
    
    $conn->close();
}

// Ensure required tables exist
ensureProductFlagsTable();
ensureNotificationsTable();
?>
