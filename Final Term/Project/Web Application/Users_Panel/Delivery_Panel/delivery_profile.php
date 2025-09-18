<?php
// Initialize session
session_start();

// Include required files
require_once __DIR__ . '/../../Database/database.php';
require_once __DIR__ . '/../../Includes/functions.php';

// Check if user is logged in and is a delivery person
if (!isLoggedIn() || !isDelivery()) {
    redirect('../../Authentication/login.php');
}

$user = getCurrentUser();
$userName = $user['name'] ?? 'Delivery Partner';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $conn = connectDB();
        
        if ($action === 'update_profile') {
            // Validate and update profile information
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            
            // Validation
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            if (empty($phone)) {
                $errors[] = 'Phone number is required';
            }
            if (empty($address)) {
                $errors[] = 'Address is required';
            }
            
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $user['id']]);
                
                setFlashMessage('success', 'Profile updated successfully!');
                
                // Refresh user data
                $user = getCurrentUser();
            } else {
                setFlashMessage('error', implode('<br>', $errors));
            }
            
        } elseif ($action === 'change_password') {
            // Handle password change
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validation
            $errors = [];
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters long';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            }
            
            if (empty($errors)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);
                
                setFlashMessage('success', 'Password changed successfully!');
            } else {
                setFlashMessage('error', implode('<br>', $errors));
            }
        }
        
    } catch (Exception $e) {
        setFlashMessage('error', 'Error updating profile: ' . $e->getMessage());
    }
    
    // Redirect to prevent form resubmission
    header('Location: delivery_profile.php');
    exit;
}

// Get delivery statistics for profile overview
try {
    $conn = connectDB();
    
    // Get delivery statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as total_delivered,
            COUNT(CASE WHEN status IN ('assigned', 'picked_up') THEN 1 END) as active_orders,
            AVG(CASE WHEN status = 'delivered' AND pickup_time IS NOT NULL AND delivery_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, pickup_time, delivery_time) END) as avg_delivery_time,
            SUM(CASE WHEN status = 'delivered' THEN 
                (SELECT SUM(oi.quantity * p.price) 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = o.id) 
                ELSE 0 END) as total_revenue
        FROM orders o
        WHERE o.delivery_person_id = ?
    ");
    $statsStmt->execute([$user['id']]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = [
        'total_delivered' => 0,
        'active_orders' => 0,
        'avg_delivery_time' => 0,
        'total_revenue' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Delivery Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="delivery.css">
</head>
<body class="delivery-dashboard">
    <div class="container dashboard-container">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="material-icons">directions_bike</i>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($userName); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <ul>
                    <li>
                        <a href="delivery_index.php">
                            <i class="material-icons">dashboard</i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="assignments.php">
                            <i class="material-icons">assignment</i>
                            My Assignments
                        </a>
                    </li>
                    <li>
                        <a href="completed.php">
                            <i class="material-icons">check_circle</i>
                            Completed
                        </a>
                    </li>
                    <li class="active">
                        <a href="delivery_profile.php">
                            <i class="material-icons">person</i>
                            My Profile
                        </a>
                    </li>
                    <li>
                        <a href="../../Authentication/logout.php" class="logout-btn">
                            <i class="material-icons">logout</i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="dashboard-main">
            <div class="page-header">
                <h2>My Profile</h2>
                <p>Manage your profile and delivery settings</p>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="profile-container">
                <!-- Profile Overview -->
                <div class="profile-overview">
                    <h3>Profile Overview</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="material-icons">check_circle</i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_delivered']); ?></h3>
                                <p>Deliveries Completed</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="material-icons">assignment</i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['active_orders']); ?></h3>
                                <p>Active Assignments</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="material-icons">timer</i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo round($stats['avg_delivery_time'] ?? 0); ?> min</h3>
                                <p>Avg. Delivery Time</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="material-icons">monetization_on</i>
                            </div>
                            <div class="stat-info">
                                <h3>৳<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tabs -->
                <div class="profile-tabs">
                    <div class="tab-nav">
                        <button type="button" class="tab-button active" onclick="showTab('profile-info')">
                            <i class="material-icons">person</i>
                            Profile Information
                        </button>
                        <button type="button" class="tab-button" onclick="showTab('change-password')">
                            <i class="material-icons">lock</i>
                            Change Password
                        </button>
                    </div>

                    <!-- Profile Information Tab -->
                    <div id="profile-info" class="tab-content active">
                        <div class="form-card">
                            <h3>Profile Information</h3>
                            <form method="POST" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small>Email cannot be changed. Contact admin if needed.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Account Status</label>
                                    <div class="status-info">
                                        <span class="status-badge status-active">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <small>Your account is currently <?php echo $user['is_active'] ? 'active and approved for deliveries' : 'pending approval'; ?>.</small>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i>
                                    Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Tab -->
                    <div id="change-password" class="tab-content">
                        <div class="form-card">
                            <h3>Change Password</h3>
                            <form method="POST" class="password-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" minlength="6" required>
                                    <small>Password must be at least 6 characters long.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>
                                
                                <div class="password-strength" id="password-strength" style="display: none;">
                                    <div class="strength-meter">
                                        <div class="strength-fill" id="strength-fill"></div>
                                    </div>
                                    <p id="strength-text">Password strength: <span id="strength-level">Weak</span></p>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">lock</i>
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('password-strength');
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-level');
            
            if (password.length === 0) {
                strengthMeter.style.display = 'none';
                return;
            }
            
            strengthMeter.style.display = 'block';
            
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            let strengthLevel = 'Weak';
            let strengthColor = '#ff4444';
            
            if (strength >= 4) {
                strengthLevel = 'Strong';
                strengthColor = '#44ff44';
            } else if (strength >= 2) {
                strengthLevel = 'Medium';
                strengthColor = '#ffaa44';
            }
            
            strengthFill.style.width = (strength / 6 * 100) + '%';
            strengthFill.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthLevel;
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>