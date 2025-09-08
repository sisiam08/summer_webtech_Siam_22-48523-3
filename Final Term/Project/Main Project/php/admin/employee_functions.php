<?php
/**
 * Admin Functions for Employee Management
 * Helper functions for admin to manage employees/staff members
 */

/**
 * Get all employees with role information
 * @param int $limit Limit the number of employees returned
 * @param int $offset Offset for pagination
 * @param string $search Search term to filter employees
 * @param string $role Filter by specific role
 * @return array Array of employees
 */
function getAllEmployees($limit = null, $offset = 0, $search = '', $role = '') {
    $conn = getDbConnection();
    
    // Build query
    $query = "
        SELECT id, name, email, phone, role, status, created_at, last_login
        FROM users
        WHERE role != 'customer' AND role != 'vendor'
    ";
    
    // Add role filter if provided
    if (!empty($role)) {
        $query .= " AND role = ?";
    }
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = '%' . $search . '%';
        if (!empty($role)) {
            $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        } else {
            $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR role LIKE ?)";
        }
    }
    
    $query .= " ORDER BY id DESC";
    
    // Add limit if provided
    if ($limit !== null) {
        $query .= " LIMIT ?, ?";
    }
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($role) && !empty($search) && $limit !== null) {
        if (!empty($role)) {
            $stmt->bind_param("sssii", $role, $search, $search, $search, $offset, $limit);
        } else {
            $stmt->bind_param("ssssi", $search, $search, $search, $search, $offset, $limit);
        }
    } elseif (!empty($role) && !empty($search)) {
        if (!empty($role)) {
            $stmt->bind_param("ssss", $role, $search, $search, $search);
        } else {
            $stmt->bind_param("ssss", $search, $search, $search, $search);
        }
    } elseif (!empty($role) && $limit !== null) {
        $stmt->bind_param("sii", $role, $offset, $limit);
    } elseif (!empty($search) && $limit !== null) {
        if (!empty($role)) {
            $stmt->bind_param("sssii", $search, $search, $search, $offset, $limit);
        } else {
            $stmt->bind_param("ssssii", $search, $search, $search, $search, $offset, $limit);
        }
    } elseif (!empty($role)) {
        $stmt->bind_param("s", $role);
    } elseif (!empty($search)) {
        if (!empty($role)) {
            $stmt->bind_param("sss", $search, $search, $search);
        } else {
            $stmt->bind_param("ssss", $search, $search, $search, $search);
        }
    } elseif ($limit !== null) {
        $stmt->bind_param("ii", $offset, $limit);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch employees
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $employees;
}

/**
 * Get employee by ID
 * @param int $id Employee ID
 * @return array|null Employee data or null if not found
 */
function getEmployeeById($id) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, role, status, created_at, last_login, 
               profile_image, address
        FROM users
        WHERE id = ? AND role != 'customer' AND role != 'vendor'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $employee = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $employee;
}

/**
 * Add a new employee
 * @param array $data Employee data
 * @return int|bool New employee ID on success, false on failure
 */
function addEmployee($data) {
    $conn = getDbConnection();
    
    try {
        // Hash password
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Prepare statement
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, phone, role, status, address, profile_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'] ?? '';
        $role = $data['role'] ?? 'staff';
        $status = $data['status'] ?? 'active';
        $address = $data['address'] ?? '';
        $profileImage = $data['profile_image'] ?? '';
        
        $stmt->bind_param("ssssssss", $name, $email, $password, $phone, $role, $status, $address, $profileImage);
        $stmt->execute();
        
        $employeeId = $stmt->insert_id;
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
        
        return $employeeId;
    } catch (Exception $e) {
        if ($conn) {
            $conn->close();
        }
        
        error_log('Error adding employee: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing employee
 * @param int $id Employee ID
 * @param array $data Employee data
 * @return bool True on success, false on failure
 */
function updateEmployee($id, $data) {
    $conn = getDbConnection();
    
    try {
        // Build query
        $query = "
            UPDATE users 
            SET name = ?, email = ?, phone = ?, role = ?, status = ?, address = ?
        ";
        
        // Check if password is being updated
        if (!empty($data['password'])) {
            $query .= ", password = ?";
        }
        
        // Check if profile image is being updated
        if (!empty($data['profile_image'])) {
            $query .= ", profile_image = ?";
        }
        
        $query .= " WHERE id = ? AND role != 'customer' AND role != 'vendor'";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'] ?? '';
        $role = $data['role'] ?? 'staff';
        $status = $data['status'] ?? 'active';
        $address = $data['address'] ?? '';
        
        // Bind parameters
        if (!empty($data['password']) && !empty($data['profile_image'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $profileImage = $data['profile_image'];
            $stmt->bind_param("sssssssi", $name, $email, $phone, $role, $status, $address, $password, $profileImage, $id);
        } elseif (!empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bind_param("ssssssi", $name, $email, $phone, $role, $status, $address, $password, $id);
        } elseif (!empty($data['profile_image'])) {
            $profileImage = $data['profile_image'];
            $stmt->bind_param("sssssssi", $name, $email, $phone, $role, $status, $address, $profileImage, $id);
        } else {
            $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $address, $id);
        }
        
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
        
        error_log('Error updating employee: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update employee status
 * @param int $id Employee ID
 * @param string $status New status (active/inactive)
 * @return bool True on success, false on failure
 */
function updateEmployeeStatus($id, $status) {
    $conn = getDbConnection();
    
    try {
        // Prepare statement
        $stmt = $conn->prepare("
            UPDATE users 
            SET status = ?
            WHERE id = ? AND role != 'customer' AND role != 'vendor'
        ");
        
        $stmt->bind_param("si", $status, $id);
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
        
        error_log('Error updating employee status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete an employee
 * @param int $id Employee ID
 * @return bool True on success, false on failure
 */
function deleteEmployee($id) {
    $conn = getDbConnection();
    
    try {
        // First check if this is not the last admin
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as admin_count
            FROM users
            WHERE role = 'admin' AND id != ?
        ");
        
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        // If this is the last admin, don't delete
        if ($row['admin_count'] == 0) {
            $checkStmt->close();
            $conn->close();
            return false;
        }
        
        $checkStmt->close();
        
        // Prepare delete statement
        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ? AND role != 'customer' AND role != 'vendor'
        ");
        
        $stmt->bind_param("i", $id);
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
        
        error_log('Error deleting employee: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get employee activity logs
 * @param int $employeeId Employee ID
 * @param int $limit Limit the number of logs returned
 * @param int $offset Offset for pagination
 * @return array Array of activity logs
 */
function getEmployeeActivityLogs($employeeId, $limit = 50, $offset = 0) {
    $conn = getDbConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("
        SELECT id, user_id, action, entity_type, entity_id, details, ip_address, created_at
        FROM activity_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?, ?
    ");
    
    $stmt->bind_param("iii", $employeeId, $offset, $limit);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $logs = [];
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    return $logs;
}

/**
 * Log employee activity
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string $entityType Type of entity affected
 * @param int $entityId ID of entity affected
 * @param array $details Additional details
 * @return bool True on success, false on failure
 */
function logEmployeeActivity($userId, $action, $entityType, $entityId, $details = []) {
    $conn = getDbConnection();
    
    try {
        // Prepare statement
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $detailsJson = json_encode($details);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt->bind_param("ississ", $userId, $action, $entityType, $entityId, $detailsJson, $ipAddress);
        $stmt->execute();
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        if ($conn) {
            $conn->close();
        }
        
        error_log('Error logging activity: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get employee roles
 * @return array Array of available employee roles
 */
function getEmployeeRoles() {
    return [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'Staff',
        'support' => 'Customer Support',
        'accountant' => 'Accountant'
    ];
}

/**
 * Check if an employee has a specific permission
 * @param string $role Employee role
 * @param string $permission Permission to check
 * @return bool True if employee has permission, false otherwise
 */
function employeeHasPermission($role, $permission) {
    // Define role-based permissions
    $permissions = [
        'admin' => [
            'manage_vendors', 'manage_employees', 'manage_products', 'manage_categories',
            'manage_orders', 'manage_customers', 'manage_settings', 'view_reports',
            'process_payments', 'manage_refunds', 'manage_site_content'
        ],
        'manager' => [
            'manage_vendors', 'manage_products', 'manage_categories', 'manage_orders',
            'manage_customers', 'view_reports'
        ],
        'staff' => [
            'manage_products', 'manage_orders', 'view_reports'
        ],
        'support' => [
            'manage_orders', 'manage_customers'
        ],
        'accountant' => [
            'view_reports', 'process_payments', 'manage_refunds'
        ]
    ];
    
    // If role doesn't exist, deny permission
    if (!isset($permissions[$role])) {
        return false;
    }
    
    // Check if permission exists for role
    return in_array($permission, $permissions[$role]);
}
?>
