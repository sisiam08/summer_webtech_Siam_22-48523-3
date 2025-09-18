<?php
session_start();
require_once __DIR__ . '/../../Database/database.php';

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../Authentication/login.html");
    exit();
}

$conn = connectDB();
$adminId = $_SESSION['user_id'];

// Get admin details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error_message'] = "Admin profile not found.";
    header("Location: admin_index.php");
    exit();
}

// Get pending counts for badges
$sql = "SELECT COUNT(*) as count FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwnersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$sql = "SELECT COUNT(*) as count FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validate required fields
            if (empty($name) || empty($email)) {
                $error_message = 'Name and email are required fields.';
            } else {
                // Check if email is already taken by another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $adminId]);
                
                if ($stmt->fetch()) {
                    $error_message = 'This email is already registered with another account.';
                } else {
                    // Update admin profile
                    try {
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $adminId]);
                        
                        // Update session data
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        
                        $success_message = 'Profile updated successfully!';
                        
                        // Refresh admin data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
                        $stmt->bindParam(1, $adminId, PDO::PARAM_INT);
                        $stmt->execute();
                        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                    } catch (Exception $e) {
                        $error_message = 'Error updating profile: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate password fields
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error_message = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $error_message = 'New password and confirmation do not match.';
            } elseif (strlen($newPassword) < 6) {
                $error_message = 'New password must be at least 6 characters long.';
            } else {
                // Verify current password
                if (password_verify($currentPassword, $admin['password'])) {
                    // Update password
                    try {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $adminId]);
                        
                        $success_message = 'Password changed successfully!';
                        
                    } catch (Exception $e) {
                        $error_message = 'Error changing password: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Current password is incorrect.';
                }
            }
        }
    }
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 48px;
        }
        
        .profile-name {
            font-size: 1.8em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-role {
            color: #666;
            font-size: 1.1em;
            background: #e8f5e8;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .form-tabs {
            display: flex;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }
        
        .tab-button {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            flex: 1;
            text-align: center;
        }
        
        .tab-button.active {
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .form-actions {
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: right;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.8em;
            font-weight: 700;
            color: #4CAF50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .info-content h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-content p {
            margin: 0;
            color: #666;
            font-size: 1.1em;
        }
        
        @media (max-width: 768px) {
            .profile-card {
                padding: 20px;
            }
            
            .form-tabs {
                flex-direction: column;
            }
            
            .tab-button {
                flex: none;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin">
    <div class="admin-sidebar">
        <div class="brand">
            Grocery Admin
        </div>
        <div class="menu">
            <a href="admin_index.php" class="menu-item">
                <i class="material-icons">dashboard</i> Dashboard
            </a>
            <a href="categories.php" class="menu-item">
                <i class="material-icons">category</i> Categories
            </a>
            <a href="banner_management.php" class="menu-item">
                <i class="material-icons">view_carousel</i> Banner Management
            </a>
            <a href="shop_owners.php" class="menu-item">
                <i class="material-icons">store</i> Shop Owners
                <?php if ($pendingShopOwnersCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingShopOwnersCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="delivery_men.php" class="menu-item">
                <i class="material-icons">delivery_dining</i> Delivery Men
                <?php if ($pendingDeliveryMenCount > 0): ?>
                <span class="pending-badge"><?php echo $pendingDeliveryMenCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="employees.php" class="menu-item">
                <i class="material-icons">people</i> Employees
            </a>
            <a href="customers.php" class="menu-item">
                <i class="material-icons">person</i> Customers
            </a>
            <a href="settings.php" class="menu-item">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>Admin Profile</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($admin['name']); ?></span>
                    <div class="dropdown-content">
                        <a href="admin_profile.php">Profile</a>
                        <a href="../../Authentication/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="material-icons">check_circle</i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="material-icons">error</i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="material-icons">account_circle</i>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                    <div class="profile-role">System Administrator</div>
                </div>

                <!-- Profile Information -->
                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="material-icons">email</i>
                        </div>
                        <div class="info-content">
                            <h4>Email Address</h4>
                            <p><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="material-icons">phone</i>
                        </div>
                        <div class="info-content">
                            <h4>Phone Number</h4>
                            <p><?php echo htmlspecialchars($admin['phone'] ?: 'Not provided'); ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="material-icons">calendar_today</i>
                        </div>
                        <div class="info-content">
                            <h4>Member Since</h4>
                            <p><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="material-icons">update</i>
                        </div>
                        <div class="info-content">
                            <h4>Last Updated</h4>
                            <p><?php echo isset($admin['updated_at']) && $admin['updated_at'] ? date('F j, Y g:i A', strtotime($admin['updated_at'])) : 'Never'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Management Forms -->
            <div class="profile-card">
                <div class="form-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'profile-tab')">
                        <i class="material-icons">person</i> Edit Profile
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'password-tab')">
                        <i class="material-icons">lock</i> Change Password
                    </button>
                </div>

                <!-- Edit Profile Tab -->
                <div id="profile-tab" class="tab-content active">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?: ''); ?>" placeholder="+880 1700-000000">
                        </div>

                        <div class="form-actions">
                            <a href="admin_index.php" class="btn btn-secondary">
                                <i class="material-icons">arrow_back</i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons">save</i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Tab -->
                <div id="password-tab" class="tab-content">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6" onkeyup="checkPasswordStrength()">
                            <div id="password-strength" class="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" onkeyup="checkPasswordMatch()">
                            <div id="password-match" class="password-strength"></div>
                        </div>

                        <div class="form-actions">
                            <a href="admin_index.php" class="btn btn-secondary">
                                <i class="material-icons">arrow_back</i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons">lock</i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            
            // Show the selected tab and mark button as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            // Check length
            if (password.length >= 6) strength++;
            else feedback.push('at least 6 characters');
            
            // Check for lowercase
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            // Check for uppercase
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            // Check for numbers
            if (/\d/.test(password)) strength++;
            else feedback.push('number');
            
            // Check for special characters
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            else feedback.push('special character');
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Medium strength';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            if (feedback.length > 0 && strength < 4) {
                strengthText += ' - Add: ' + feedback.slice(0, 2).join(', ');
            }
            
            strengthDiv.innerHTML = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        }
        
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = 'Passwords match';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.innerHTML = 'Passwords do not match';
                matchDiv.className = 'password-strength strength-weak';
            }
        }
    </script>
</body>
</html>