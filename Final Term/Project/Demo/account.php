<?php
// Initialize session
session_start();

// Include required files
require_once 'php/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
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

// Get order history
try {
    $stmt = $conn->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = :user_id 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
        LIMIT 10
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
    <title>My Account - Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/account.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="account.php">My Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <h2>My Account</h2>
            
            <?php if ($success): ?>
                <div class="success">
                    <p>Account updated successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="account.php">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    <small>Email cannot be changed</small>
                </div>
                
                <h3>Change Password</h3>
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
                
                <button type="submit" class="btn">Update Account</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
