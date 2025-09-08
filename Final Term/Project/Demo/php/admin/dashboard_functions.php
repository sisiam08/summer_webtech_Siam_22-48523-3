<?php
/**
 * Admin Functions for Categories and Settings Management
 * Helper functions for admin to manage categories and system settings
 */

/**
 * Get all categories with product counts
 * @param int $limit Limit the number of categories returned
 * @param int $offset Offset for pagination
 * @param string $search Search term to filter categories
 * @return array Array of categories
 */
function getAllCategories($limit = null, $offset = 0, $search = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT c.*, 
               (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
        FROM categories c
    ";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = '%' . $search . '%';
        $query .= " WHERE c.name LIKE ? OR c.description LIKE ?";
    }
    
    $query .= " ORDER BY c.id DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($search) && $limit !== null) {
        $stmt->bind_param("ssii", $search, $search, $offset, $limit);
    } elseif (!empty($search)) {
        $stmt->bind_param("ss", $search, $search);
    } elseif ($limit !== null) {
        $stmt->bind_param("ii", $offset, $limit);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch categories
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $categories;
}

/**
 * Get category by ID
 * @param int $id Category ID
 * @return array|null Category data or null if not found
 */
function getCategoryById($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
        FROM categories c
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $category = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $category;
}

/**
 * Add a new category
 * @param array $data Category data
 * @return int|bool New category ID on success, false on failure
 */
function addCategory($data) {
    $conn = getDbConnection();
    
    try {
        // Prepare statement
        $stmt = $conn->prepare("
            INSERT INTO categories (name, description, image, status) 
            VALUES (?, ?, ?, ?)
        ");
        
        $name = $data['name'];
        $description = $data['description'] ?? '';
        $image = $data['image'] ?? '';
        $status = $data['status'] ?? 'active';
        
        $stmt->bind_param("ssss", $name, $description, $image, $status);
        $stmt->execute();
        
        $categoryId = $stmt->insert_id;
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
        
        return $categoryId;
    } catch (Exception $e) {
        if ($conn) {
            $conn->close();
        }
        
        error_log('Error adding category: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing category
 * @param int $id Category ID
 * @param array $data Category data
 * @return bool True on success, false on failure
 */
function updateCategory($id, $data) {
    $conn = getDbConnection();
    
    try {
        // Prepare statement
        $stmt = $conn->prepare("
            UPDATE categories 
            SET name = ?, description = ?, image = ?, status = ?
            WHERE id = ?
        ");
        
        $name = $data['name'];
        $description = $data['description'] ?? '';
        $image = $data['image'] ?? '';
        $status = $data['status'] ?? 'active';
        
        $stmt->bind_param("ssssi", $name, $description, $image, $status, $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
        
        return $affected > 0;
    } catch (Exception $e) {
        if ($conn) {
            $conn->close();
        }
        
        error_log('Error updating category: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a category
 * @param int $id Category ID
 * @return bool True on success, false on failure
 */
function deleteCategory($id) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if category has products
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as product_count
            FROM products
            WHERE category_id = ?
        ");
        
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        $checkStmt->close();
        
        // If category has products, move them to uncategorized
        if ($row['product_count'] > 0) {
            // Get or create uncategorized category
            $uncategorizedId = getUncategorizedCategoryId($conn);
            
            // Update products
            $updateStmt = $conn->prepare("
                UPDATE products
                SET category_id = ?
                WHERE category_id = ?
            ");
            
            $updateStmt->bind_param("ii", $uncategorizedId, $id);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // Now delete the category
        $deleteStmt = $conn->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");
        
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $affected = $deleteStmt->affected_rows;
        $deleteStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Close connection
        $conn->close();
        
        return $affected > 0;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log('Error deleting category: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get or create the uncategorized category
 * @param mysqli $conn Database connection
 * @return int Uncategorized category ID
 */
function getUncategorizedCategoryId($conn) {
    // Check if uncategorized category exists
    $stmt = $conn->prepare("
        SELECT id
        FROM categories
        WHERE name = 'Uncategorized'
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id'];
    }
    
    $stmt->close();
    
    // Create uncategorized category
    $stmt = $conn->prepare("
        INSERT INTO categories (name, description, status)
        VALUES ('Uncategorized', 'Products without a specific category', 'active')
    ");
    
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    
    return $id;
}

/**
 * Get system settings
 * @return array System settings
 */
function getSystemSettings() {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->query("
        SELECT *
        FROM settings
    ");
    
    // Fetch settings
    $settings = [];
    while ($row = $stmt->fetch_assoc()) {
        $settings[$row['key']] = $row['value'];
    }
    
    // Close connection
    $conn->close();
    
    return $settings;
}

/**
 * Update system settings
 * @param array $settings Settings to update
 * @return bool True on success, false on failure
 */
function updateSystemSettings($settings) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $checkStmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM settings
                WHERE `key` = ?
            ");
            
            $checkStmt->bind_param("s", $key);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                // Update existing setting
                $updateStmt = $conn->prepare("
                    UPDATE settings
                    SET `value` = ?
                    WHERE `key` = ?
                ");
                
                $updateStmt->bind_param("ss", $value, $key);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert new setting
                $insertStmt = $conn->prepare("
                    INSERT INTO settings (`key`, `value`)
                    VALUES (?, ?)
                ");
                
                $insertStmt->bind_param("ss", $key, $value);
                $insertStmt->execute();
                $insertStmt->close();
            }
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
        
        error_log('Error updating settings: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get dashboard statistics
 * @return array Statistics for the admin dashboard
 */
function getDashboardStats() {
    $conn = getDbConnection();
    
    // Get total orders
    $orderQuery = "SELECT COUNT(*) as total FROM orders";
    $orderResult = $conn->query($orderQuery);
    $orderRow = $orderResult->fetch_assoc();
    $totalOrders = $orderRow['total'];
    
    // Get total revenue
    $revenueQuery = "SELECT SUM(total) as total FROM orders WHERE status = 'completed'";
    $revenueResult = $conn->query($revenueQuery);
    $revenueRow = $revenueResult->fetch_assoc();
    $totalRevenue = $revenueRow['total'] ?? 0;
    
    // Get total users
    $userQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
    $userResult = $conn->query($userQuery);
    $userRow = $userResult->fetch_assoc();
    $totalUsers = $userRow['total'];
    
    // Get total products
    $productQuery = "SELECT COUNT(*) as total FROM products";
    $productResult = $conn->query($productQuery);
    $productRow = $productResult->fetch_assoc();
    $totalProducts = $productRow['total'];
    
    // Get vendor stats
    $vendorStats = getVendorStats();
    
    // Get recent orders
    $recentOrdersQuery = "
        SELECT o.id, o.user_id, o.total, o.status, o.payment_status, 
               o.created_at, u.name as customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ";
    
    $recentOrdersResult = $conn->query($recentOrdersQuery);
    $recentOrders = [];
    
    while ($row = $recentOrdersResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
    
    // Close connection
    $conn->close();
    
    return [
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'totalUsers' => $totalUsers,
        'totalProducts' => $totalProducts,
        'vendorStats' => $vendorStats,
        'recentOrders' => $recentOrders
    ];
}

/**
 * Create database tables for settings if they don't exist
 */
function createSettingsTable() {
    $conn = getDbConnection();
    
    // Create settings table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Create activity_logs table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS activity_logs (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `action` VARCHAR(100),
            `entity_type` VARCHAR(100),
            `entity_id` INT,
            `details` TEXT,
            `ip_address` VARCHAR(50),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`user_id`),
            INDEX (`entity_type`, `entity_id`)
        )
    ");
    
    // Insert default settings if they don't exist
    $defaultSettings = [
        'site_name' => 'Online Grocery Store',
        'site_description' => 'A multi-vendor marketplace for grocery shopping',
        'admin_email' => 'admin@example.com',
        'currency' => 'USD',
        'currency_symbol' => '$',
        'tax_rate' => '0',
        'vendor_commission_rate' => '10',
        'allow_vendor_registration' => '1',
        'vendor_auto_approve' => '0',
        'maintenance_mode' => '0'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM settings
            WHERE `key` = ?
        ");
        
        $checkStmt->bind_param("s", $key);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($row['count'] == 0) {
            $insertStmt = $conn->prepare("
                INSERT INTO settings (`key`, `value`)
                VALUES (?, ?)
            ");
            
            $insertStmt->bind_param("ss", $key, $value);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>
