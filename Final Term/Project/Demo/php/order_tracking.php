<?php
/**
 * Updates an order's status and creates an order tracking event
 * 
 * @param PDO $pdo Database connection
 * @param int $orderId Order ID
 * @param string $status New status
 * @param string $description Optional description for the tracking event
 * @param int $userId Optional user ID of who made the update
 * @return bool True on success, false on failure
 */
function updateOrderStatus($pdo, $orderId, $status, $description = '', $userId = null) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        
        // Create tracking event
        $sql = "INSERT INTO order_tracking (order_id, status, description, created_by, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $status, $description, $userId]);
        
        // Commit transaction
        $pdo->commit();
        
        return true;
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        error_log("Error updating order status: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates multiple orders' statuses from a specific shop
 * Useful for shop owners to bulk update orders
 * 
 * @param PDO $pdo Database connection
 * @param array $orderIds Array of order IDs
 * @param string $status New status
 * @param int $shopOwnerId Shop owner user ID
 * @return array Results with success/failure for each order
 */
function bulkUpdateOrderStatus($pdo, $orderIds, $status, $shopOwnerId) {
    $results = [];
    
    foreach ($orderIds as $orderId) {
        // Verify this order belongs to this shop owner
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND p.shop_owner_id = ?
        ");
        $stmt->execute([$orderId, $shopOwnerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $description = "Status updated to {$status} by shop owner";
            $success = updateOrderStatus($pdo, $orderId, $status, $description, $shopOwnerId);
            
            $results[$orderId] = [
                'success' => $success,
                'message' => $success ? 'Status updated successfully' : 'Failed to update status'
            ];
        } else {
            $results[$orderId] = [
                'success' => false,
                'message' => 'Unauthorized: Order does not belong to this shop owner'
            ];
        }
    }
    
    return $results;
}

/**
 * Creates initial tracking event when an order is placed
 * Should be called after an order is created
 * 
 * @param PDO $pdo Database connection
 * @param int $orderId Order ID
 * @param int $userId User ID who placed the order
 * @return bool True on success, false on failure
 */
function createInitialOrderTrackingEvent($pdo, $orderId, $userId) {
    try {
        $sql = "INSERT INTO order_tracking (order_id, status, description, created_by, created_at) 
                VALUES (?, 'order_placed', 'Order placed successfully', ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating initial tracking event: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets all tracking events for an order
 * 
 * @param PDO $pdo Database connection
 * @param int $orderId Order ID
 * @return array Array of tracking events
 */
function getOrderTrackingEvents($pdo, $orderId) {
    try {
        $sql = "SELECT ot.*, u.name as updated_by_name 
                FROM order_tracking ot
                LEFT JOIN users u ON ot.created_by = u.id
                WHERE ot.order_id = ?
                ORDER BY ot.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting tracking events: " . $e->getMessage());
        return [];
    }
}
?>
