<?php
// Start session and include required files
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in and is a shop owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'shop_owner') {
    echo json_encode(['error' => 'Access denied. Please login as a shop owner.']);
    exit;
}

// Get the logged-in shop owner's shop ID
$shopId = getShopIdForOwner();
if (!$shopId) {
    echo json_encode(['error' => 'No shop found for this account.']);
    exit;
}

// Get products from the database for this specific shop
try {
    $pdo = connectDB();
    
    $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    
    if ($categoryId > 0) {
        // Get products for a specific category AND this shop
        $query = "SELECT p.*, c.name as category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = ? AND p.shop_id = ? 
                  ORDER BY p.name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$categoryId, $shopId]);
    } else {
        // Get all products for this shop only
        $query = "SELECT p.*, c.name as category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.shop_id = ? 
                  ORDER BY p.name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$shopId]);
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process products to add image paths and format data
    foreach ($products as &$product) {
        // Handle image path
        if (!empty($product['image'])) {
            $product['image_url'] = '../../Uploads/products/' . $product['image'];
        } else {
            $product['image_url'] = '../../Uploads/products/default-product.jpg';
        }
        
        // Format price
        $product['formatted_price'] = number_format($product['price'], 2);
        
        // Add status text
        $product['status_text'] = $product['is_active'] ? 'Active' : 'Inactive';
        $product['status_class'] = $product['is_active'] ? 'success' : 'danger';
        
        // Determine stock status
        if ($product['stock'] <= 2) {
            $product['stock_class'] = 'danger';
            $product['stock_status'] = 'Low Stock';
        } else if ($product['stock'] <= 5) {
            $product['stock_class'] = 'warning';
            $product['stock_status'] = 'Medium Stock';
        } else {
            $product['stock_class'] = 'success';
            $product['stock_status'] = 'In Stock';
        }
    }
    
    echo json_encode($products);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Error loading products. Please try again later.']);
}
?>
