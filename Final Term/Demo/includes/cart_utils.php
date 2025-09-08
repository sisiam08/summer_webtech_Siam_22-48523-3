<?php
/**
 * Utility functions for cart operations
 */

/**
 * Get the total number of items in the cart
 * This function handles both old and new cart formats
 *
 * @return int The total number of items
 */
function calculateCartCount() {
    $cartCount = 0;
    
    // First check if user is logged in and using database cart
    if (isset($_SESSION['user_id'])) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['cart_count'] ?? 0;
            }
        } catch (mysqli_sql_exception $e) {
            // If error (like table doesn't exist), fall back to session cart
        }
    }
    
    // Use session cart
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productId => $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $cartCount += $item['quantity'];
            } else {
                $cartCount += $item;
            }
        }
    }
    
    return $cartCount;
}

/**
 * Ensure cart is using the new structure
 * Converts old format (product_id => quantity) to new format (product_id => ['quantity' => quantity, 'added_at' => timestamp])
 */
function normalizeCartStructure() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        return;
    }
    
    foreach ($_SESSION['cart'] as $productId => $item) {
        if (!is_array($item)) {
            // Convert old format to new format
            $_SESSION['cart'][$productId] = [
                'quantity' => $item,
                'added_at' => date('Y-m-d H:i:s')
            ];
        } else if (!isset($item['added_at'])) {
            // Ensure added_at is set
            $_SESSION['cart'][$productId]['added_at'] = date('Y-m-d H:i:s');
        }
    }
}

/**
 * Add a product to the cart
 * 
 * @param int $productId The product ID to add
 * @param int $quantity The quantity to add (default 1)
 * @return bool Success or failure
 */
function addProductToCart($productId, $quantity = 1) {
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($productId <= 0 || $quantity <= 0) {
        return false;
    }
    
    // Ensure cart exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add or update product in cart
    if (isset($_SESSION['cart'][$productId])) {
        if (is_array($_SESSION['cart'][$productId]) && isset($_SESSION['cart'][$productId]['quantity'])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            // Convert old format to new format
            $oldQuantity = $_SESSION['cart'][$productId];
            $_SESSION['cart'][$productId] = [
                'quantity' => $oldQuantity + $quantity,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
    } else {
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return true;
}

/**
 * Update cart item quantity
 * 
 * @param int $productId The product ID to update
 * @param int $quantity The new quantity
 * @return bool Success or failure
 */
function updateProductQuantity($productId, $quantity) {
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($productId <= 0) {
        return false;
    }
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        return true;
    }
    
    // Update quantity
    if (isset($_SESSION['cart'][$productId])) {
        if (is_array($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        } else {
            // Convert to new format
            $_SESSION['cart'][$productId] = [
                'quantity' => $quantity,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }
        return true;
    }
    
    return false;
}

/**
 * Remove item from cart
 * 
 * @param int $productId The product ID to remove
 * @return bool Success or failure
 */
function removeProductFromCart($productId) {
    $productId = (int)$productId;
    
    if ($productId <= 0) {
        return false;
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    
    return false;
}

/**
 * Clear the entire cart
 */
function emptyShoppingCart() {
    $_SESSION['cart'] = [];
}
?>
