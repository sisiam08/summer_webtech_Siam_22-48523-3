<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';


// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../Authentication/login.html');
    exit;
}

// Get user data
$userId = $_SESSION['user_id'];
$conn = connectDB();

try {
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found, logout
        session_destroy();
        header('Location: login.html');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    $error = "An error occurred. Please try again later.";
}

// Handle form submission
$errors = [];
$success = false;
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update profile
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate form data
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        // If changing password
        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required';
            } else {
                // Verify current password
                if (!password_verify($currentPassword, $user['password'])) {
                    $errors[] = 'Current password is incorrect';
                }
            }
            
            if (empty($newPassword)) {
                $errors[] = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // Update user if no errors
        if (empty($errors)) {
            try {
                // Start with basic update
                $query = "UPDATE users SET name = :name, phone = :phone";
                $params = [
                    ':name' => $name,
                    ':phone' => $phone,
                    ':user_id' => $userId
                ];
                
                // Add password update if necessary
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $query .= ", password = :password";
                    $params[':password'] = $hashedPassword;
                }
                
                $query .= " WHERE id = :user_id";
                
                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                
                $success = true;
                $successMessage = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                error_log("Error updating profile: " . $e->getMessage());
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    } elseif ($action === 'add_address') {
        // Add new address
        $label = $_POST['label'] ?? '';
        $line1 = $_POST['line1'] ?? '';
        $area = $_POST['area'] ?? '';
        $city = $_POST['city'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $addressPhone = $_POST['address_phone'] ?? '';
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Validate address data
        if (empty($label) || empty($line1) || empty($area) || empty($city) || empty($postal_code) || empty($addressPhone)) {
            $errors[] = 'All address fields are required';
        }
        
        if (empty($errors)) {
            try {
                // If this is default, unset any existing default address
                if ($is_default) {
                    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $stmt->execute();
                }
                
                // Add new address
                $query = "INSERT INTO addresses (user_id, label, line1, area, city, postal_code, phone, is_default) 
                          VALUES (:user_id, :label, :line1, :area, :city, :postal_code, :phone, :is_default)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':label', $label, PDO::PARAM_STR);
                $stmt->bindParam(':line1', $line1, PDO::PARAM_STR);
                $stmt->bindParam(':area', $area, PDO::PARAM_STR);
                $stmt->bindParam(':city', $city, PDO::PARAM_STR);
                $stmt->bindParam(':postal_code', $postal_code, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $addressPhone, PDO::PARAM_STR);
                $stmt->bindParam(':is_default', $is_default, PDO::PARAM_INT);
                $stmt->execute();
                
                $success = true;
                $successMessage = 'Address added successfully!';
                
            } catch (PDOException $e) {
                error_log("Error adding address: " . $e->getMessage());
                $errors[] = 'Failed to add address. Please try again.';
            }
        }
    } elseif ($action === 'delete_address') {
        // Delete address
        $addressId = $_POST['address_id'] ?? 0;
        
        if (empty($addressId)) {
            $errors[] = 'Invalid address';
        }
        
        if (empty($errors)) {
            try {
                // Check if address belongs to user
                $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = :address_id AND user_id = :user_id");
                $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    $errors[] = 'Address not found';
                } else {
                    // Delete address
                    $stmt = $conn->prepare("DELETE FROM addresses WHERE id = :address_id");
                    $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $success = true;
                    $successMessage = 'Address deleted successfully!';
                }
                
            } catch (PDOException $e) {
                error_log("Error deleting address: " . $e->getMessage());
                $errors[] = 'Failed to delete address. Please try again.';
            }
        }
    } elseif ($action === 'set_default_address') {
        // Set default address
        $addressId = $_POST['address_id'] ?? 0;
        
        if (empty($addressId)) {
            $errors[] = 'Invalid address';
        }
        
        if (empty($errors)) {
            try {
                // Check if address belongs to user
                $stmt = $conn->prepare("SELECT id FROM addresses WHERE id = :address_id AND user_id = :user_id");
                $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    $errors[] = 'Address not found';
                } else {
                    // Unset any existing default address
                    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Set new default address
                    $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = :address_id");
                    $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $success = true;
                    $successMessage = 'Default address updated successfully!';
                }
                
            } catch (PDOException $e) {
                error_log("Error setting default address: " . $e->getMessage());
                $errors[] = 'Failed to update default address. Please try again.';
            }
        }
    } elseif ($action === 'reorder') {
        // Reorder functionality - add all items from a previous order to cart
        $orderId = $_POST['order_id'] ?? 0;
        
        if (empty($orderId)) {
            $errors[] = 'Invalid order ID';
        }
        
        if (empty($errors)) {
            try {
                // Get order items for this order and user
                $stmt = $conn->prepare("
                    SELECT oi.product_id, oi.quantity, p.name, p.price, p.stock
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.id = :order_id AND o.user_id = :user_id
                ");
                $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($orderItems)) {
                    $errors[] = 'Order not found or no items in this order';
                } else {
                    $reorderedCount = 0;
                    $unavailableItems = [];
                    
                    // Add each item to cart
                    foreach ($orderItems as $item) {
                        // Check if product still exists and has stock
                        if ($item['stock'] >= $item['quantity']) {
                            // Add to database cart for logged-in user
                            $checkCartStmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                            $checkCartStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                            $checkCartStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                            $checkCartStmt->execute();
                            
                            if ($existingCartItem = $checkCartStmt->fetch(PDO::FETCH_ASSOC)) {
                                // Update existing cart item
                                $newQuantity = $existingCartItem['quantity'] + $item['quantity'];
                                $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE id = :cart_id");
                                $updateStmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                                $updateStmt->bindParam(':cart_id', $existingCartItem['id'], PDO::PARAM_INT);
                                $updateStmt->execute();
                            } else {
                                // Insert new cart item
                                $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                                $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                                $insertStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                                $insertStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                                $insertStmt->execute();
                            }
                            $reorderedCount++;
                        } else {
                            $unavailableItems[] = $item['name'] . ' (insufficient stock)';
                        }
                    }
                    
                    if ($reorderedCount > 0) {
                        $success = true;
                        $successMessage = "Successfully added {$reorderedCount} items to your cart.";
                        if (!empty($unavailableItems)) {
                            $successMessage .= " Note: Some items were unavailable: " . implode(', ', $unavailableItems);
                        }
                    } else {
                        $errors[] = 'All items from this order are currently unavailable.';
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Error reordering: " . $e->getMessage());
                $errors[] = 'Failed to reorder items. Please try again.';
            }
        }
    }
}

// Get user addresses
try {
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching addresses: " . $e->getMessage());
    $addresses = [];
}

// Get order history with detailed information
try {
    $stmt = $conn->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at, o.shipping_address,
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as item_summary
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = :user_id 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
        LIMIT 20
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="account.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Nitto Proyojon</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="account.php">My Account</a></li>
                    <li><a href="../../Authentication/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="account-container">
            <h2>My Account</h2>
            
            <?php if ($success): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Account Navigation Tabs -->
            <div class="account-tabs">
                <button class="tab-button active" onclick="openTab(event, 'profile-tab')">Profile</button>
                <button class="tab-button" onclick="openTab(event, 'addresses-tab')">Addresses</button>
                <button class="tab-button" onclick="openTab(event, 'orders-tab')">Order History</button>
            </div>
            
            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content active">
                <div class="form-container">
                    <h3>Profile Information</h3>
                    <form method="post" action="account.php">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <h4>Change Password</h4>
                        <p><small>Leave blank if you don't want to change your password</small></p>
                        
                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <input type="password" id="current-password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input type="password" id="new-password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password</label>
                            <input type="password" id="confirm-password" name="confirm_password">
                        </div>
                        
                        <button type="submit" class="btn">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <!-- Addresses Tab -->
            <div id="addresses-tab" class="tab-content">
                <div class="addresses-container">
                    <h3>Delivery Addresses</h3>
                    
                    <div class="addresses-list">
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card">
                                <div class="address-header">
                                    <h4><?php echo htmlspecialchars($address['label']); ?></h4>
                                    <?php if ($address['is_default']): ?>
                                        <span class="default-badge">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-details">
                                    <p><?php echo htmlspecialchars($address['line1']); ?></p>
                                    <p><?php echo htmlspecialchars($address['area']) . ', ' . htmlspecialchars($address['city']); ?></p>
                                    <p>Postal Code: <?php echo htmlspecialchars($address['postal_code']); ?></p>
                                    <p>Phone: <?php echo htmlspecialchars($address['phone']); ?></p>
                                </div>
                                <div class="address-actions">
                                    <?php if (!$address['is_default']): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="set_default_address">
                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                            <button type="submit" class="btn btn-small">Set Default</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this address?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($addresses)): ?>
                            <p>No addresses found. Add your first address below.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="add-address-form">
                        <h4>Add New Address</h4>
                        <form method="post" action="account.php">
                            <input type="hidden" name="action" value="add_address">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="label">Address Label</label>
                                    <input type="text" id="label" name="label" placeholder="e.g., Home, Office" required>
                                </div>
                                <div class="form-group">
                                    <label for="address_phone">Phone Number</label>
                                    <input type="tel" id="address_phone" name="address_phone" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="line1">Address Line</label>
                                <input type="text" id="line1" name="line1" placeholder="Street address" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="area">Area</label>
                                    <input type="text" id="area" name="area" required>
                                </div>
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="postal_code">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_default" value="1">
                                    Set as default address
                                </label>
                            </div>
                            
                            <button type="submit" class="btn">Add Address</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order History Tab -->
            <div id="orders-tab" class="tab-content">
                <div class="orders-container">
                    <h3>Order History</h3>
                    
                    <?php if (empty($orders)): ?>
                        <div class="no-orders">
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4>Order #<?php echo $order['id']; ?></h4>
                                            <p class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            <p class="order-total">৳<?php echo number_format($order['total_amount'], 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-items">
                                            <p><strong><?php echo $order['item_count']; ?> item(s):</strong></p>
                                            <p class="items-summary"><?php echo htmlspecialchars($order['item_summary']); ?></p>
                                        </div>
                                        
                                        <div class="order-address">
                                            <p><strong>Delivery Address:</strong></p>
                                            <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="reorder">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" class="btn btn-outline">Reorder</button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-small" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                            View Details
                                        </button>
                                    </div>
                                    
                                    <!-- Detailed order items (hidden by default) -->
                                    <div id="order-details-<?php echo $order['id']; ?>" class="order-details-expanded" style="display: none;">
                                        <h5>Order Items:</h5>
                                        <div class="detailed-items">
                                            <!-- This will be loaded dynamically -->
                                            <p>Loading details...</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Nitto Proyojon. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../Includes/script.js"></script>
    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // Hide all tab content
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remove active class from all tab buttons
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            // Show the selected tab content and mark button as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Toggle order details
        function toggleOrderDetails(orderId) {
            const detailsDiv = document.getElementById('order-details-' + orderId);
            const button = event.target;
            
            if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
                // Show details and load them if not already loaded
                detailsDiv.style.display = 'block';
                button.textContent = 'Hide Details';
                
                // Load detailed order items if not already loaded
                if (detailsDiv.querySelector('.detailed-items p').textContent === 'Loading details...') {
                    loadOrderDetails(orderId);
                }
            } else {
                // Hide details
                detailsDiv.style.display = 'none';
                button.textContent = 'View Details';
            }
        }
        
        // Load detailed order items via AJAX
        function loadOrderDetails(orderId) {
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    const detailsContainer = document.querySelector('#order-details-' + orderId + ' .detailed-items');
                    
                    if (data.success && data.items) {
                        let itemsHtml = '<table class="order-items-table">';
                        itemsHtml += '<thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>';
                        itemsHtml += '<tbody>';
                        
                        data.items.forEach(item => {
                            itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>৳${parseFloat(item.price).toFixed(2)}</td>
                                    <td>৳${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        
                        itemsHtml += '</tbody></table>';
                        detailsContainer.innerHTML = itemsHtml;
                    } else {
                        detailsContainer.innerHTML = '<p>Failed to load order details.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    document.querySelector('#order-details-' + orderId + ' .detailed-items').innerHTML = 
                        '<p>Error loading order details.</p>';
                });
        }
        
        // Auto-open tab based on URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash === '#orders') {
                document.querySelector('[onclick="openTab(event, \'orders-tab\')"]').click();
            } else if (hash === '#addresses') {
                document.querySelector('[onclick="openTab(event, \'addresses-tab\')"]').click();
            }
        });
    </script>
</body>
</html>
