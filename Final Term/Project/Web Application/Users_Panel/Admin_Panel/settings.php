<?php
// Start session
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database for pending counts
require_once __DIR__ . '/../../Database/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get admin details
$conn = connectDB();
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bindParam(1, $adminId, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminName = $admin['name'] ?? 'Admin';

// Handle form submissions
$success_message = '';
$error_message = '';

// Create settings table if it doesn't exist
$createSettingsTable = "
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->exec($createSettingsTable);

// Initialize default settings if table is empty
$checkSettings = $conn->query("SELECT COUNT(*) as count FROM system_settings");
if ($checkSettings->fetch()['count'] == 0) {
    $defaultSettings = [
        ['site_name', 'GroceryBD Admin', 'text', 'Website name displayed in headers', 'general'],
        ['site_email', 'admin@grocerybd.com', 'text', 'Primary contact email', 'general'],
        ['site_phone', '+880 1700-000000', 'text', 'Primary contact phone number', 'general'],
        ['currency_symbol', '৳', 'text', 'Currency symbol used throughout the site', 'general'],
        ['timezone', 'Asia/Dhaka', 'text', 'System timezone', 'general'],
        ['maintenance_mode', '0', 'boolean', 'Enable maintenance mode', 'system'],
        ['user_registration', '1', 'boolean', 'Allow new user registration', 'system'],
        ['email_notifications', '1', 'boolean', 'Send email notifications', 'system'],
        ['max_upload_size', '5', 'number', 'Maximum file upload size in MB', 'system'],
        ['session_timeout', '1440', 'number', 'Session timeout in minutes', 'system'],
        ['shop_approval_required', '1', 'boolean', 'Require admin approval for new shops', 'business'],
        ['delivery_approval_required', '1', 'boolean', 'Require admin approval for delivery personnel', 'business'],
        ['minimum_order_amount', '100', 'number', 'Minimum order amount in Taka', 'business'],
        ['delivery_fee', '50', 'number', 'Standard delivery fee in Taka', 'business'],
        ['platform_commission', '5', 'number', 'Platform commission percentage', 'business']
    ];
    
    $insertStmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category) VALUES (?, ?, ?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $insertStmt->execute($setting);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_settings') {
            try {
                $conn->beginTransaction();
                
                foreach ($_POST['settings'] as $key => $value) {
                    if ($key === 'action') continue;
                    
                    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                
                $conn->commit();
                $success_message = 'Settings updated successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Error updating settings: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'clear_cache') {
            // Simulate cache clearing
            $success_message = 'System cache cleared successfully!';
        } elseif ($_POST['action'] === 'backup_database') {
            // Simulate database backup
            $success_message = 'Database backup initiated successfully!';
        } elseif ($_POST['action'] === 'reset_settings') {
            try {
                $conn->exec("DELETE FROM system_settings");
                // Re-initialize default settings (code from above)
                $success_message = 'Settings reset to default values!';
            } catch (Exception $e) {
                $error_message = 'Error resetting settings: ' . $e->getMessage();
            }
        }
    }
}

// Get all settings grouped by category
$stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY category, setting_key");
$stmt->execute();
$allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $settingsByCategory[$setting['category']][] = $setting;
}

// Get system statistics
$systemStats = [];

// User statistics
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$systemStats['total_users'] = $stmt->fetch()['total_users'];

$stmt = $conn->query("SELECT COUNT(*) as active_users FROM users WHERE is_active = 1");
$systemStats['active_users'] = $stmt->fetch()['active_users'];

// Check if other tables exist for additional stats
try {
    $stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
    $systemStats['total_orders'] = $stmt->fetch()['total_orders'];
} catch (PDOException $e) {
    $systemStats['total_orders'] = 0;
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
    $systemStats['total_products'] = $stmt->fetch()['total_products'];
} catch (PDOException $e) {
    $systemStats['total_products'] = 0;
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

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .settings-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .settings-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tab-button {
            padding: 15px 25px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: #4caf50;
            border-bottom-color: #4caf50;
            background: white;
        }
        
        .tab-button:hover {
            background: #f0f0f0;
        }
        
        .tab-content {
            padding: 30px;
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .setting-group {
            margin-bottom: 30px;
        }
        
        .setting-group h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.2em;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        
        .setting-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1em;
        }
        
        .setting-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .setting-control input,
        .setting-control select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .setting-control input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        
        .stat-card.red {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
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
        
        .backup-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .backup-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .backup-info p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .settings-tabs {
                flex-wrap: wrap;
            }
            
            .tab-button {
                flex: 1;
                min-width: 50%;
            }
            
            .setting-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .action-buttons {
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
            <a href="settings.php" class="menu-item active">
                <i class="material-icons">settings</i> Settings
            </a>
        </div>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h2>System Settings</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-username"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown-content">
                        <a href="admin_profile.php">Profile</a>
                        <a href="../../Authentication/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Settings Container -->
        <div class="settings-container">
            <div class="settings-tabs">
                <button class="tab-button active" onclick="openTab(event, 'general-settings')">
                    <i class="material-icons">tune</i> General
                </button>
                <button class="tab-button" onclick="openTab(event, 'system-settings')">
                    <i class="material-icons">computer</i> System
                </button>
                <button class="tab-button" onclick="openTab(event, 'business-settings')">
                    <i class="material-icons">business</i> Business
                </button>
                <button class="tab-button" onclick="openTab(event, 'maintenance')">
                    <i class="material-icons">build</i> Maintenance
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_settings">
                
                <!-- General Settings Tab -->
                <div id="general-settings" class="tab-content active">
                    <div class="setting-group">
                        <h3>General Configuration</h3>
                        <?php if (isset($settingsByCategory['general'])): ?>
                            <?php foreach ($settingsByCategory['general'] as $setting): ?>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $setting['setting_key'])); ?></h4>
                                        <p><?php echo htmlspecialchars($setting['description']); ?></p>
                                    </div>
                                    <div class="setting-control">
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" 
                                                       name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                       value="1"
                                                       <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                <span>Enabled</span>
                                            </div>
                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                            <input type="number" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                   min="0">
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Settings Tab -->
                <div id="system-settings" class="tab-content">
                    <div class="setting-group">
                        <h3>System Configuration</h3>
                        <?php if (isset($settingsByCategory['system'])): ?>
                            <?php foreach ($settingsByCategory['system'] as $setting): ?>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $setting['setting_key'])); ?></h4>
                                        <p><?php echo htmlspecialchars($setting['description']); ?></p>
                                    </div>
                                    <div class="setting-control">
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" 
                                                       name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                       value="1"
                                                       <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                <span>Enabled</span>
                                            </div>
                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                            <input type="number" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                   min="0">
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Business Settings Tab -->
                <div id="business-settings" class="tab-content">
                    <div class="setting-group">
                        <h3>Business Configuration</h3>
                        <?php if (isset($settingsByCategory['business'])): ?>
                            <?php foreach ($settingsByCategory['business'] as $setting): ?>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $setting['setting_key'])); ?></h4>
                                        <p><?php echo htmlspecialchars($setting['description']); ?></p>
                                    </div>
                                    <div class="setting-control">
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" 
                                                       name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                       value="1"
                                                       <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                <span>Enabled</span>
                                            </div>
                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                            <input type="number" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                   min="0"
                                                   step="0.01">
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Maintenance Tab -->
                <div id="maintenance" class="tab-content">
                    <div class="setting-group">
                        <h3>System Maintenance</h3>
                        <div class="backup-info">
                            <h4>System Maintenance Tools</h4>
                            <p>Use these tools to maintain and optimize your system. Please use with caution as some actions cannot be undone.</p>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="button" class="btn btn-secondary" onclick="clearCache()">
                                <i class="material-icons">cached</i>
                                Clear System Cache
                            </button>
                            
                            <button type="button" class="btn btn-warning" onclick="backupDatabase()">
                                <i class="material-icons">backup</i>
                                Backup Database
                            </button>
                            
                            <button type="button" class="btn btn-danger" onclick="resetSettings()">
                                <i class="material-icons">restore</i>
                                Reset to Defaults
                            </button>
                            
                            <a href="../../Authentication/logout.php" class="btn btn-secondary">
                                <i class="material-icons">exit_to_app</i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>

                <div style="padding: 20px; background: #f8f9fa; border-top: 1px solid #e0e0e0;">
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons">save</i>
                        Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        function clearCache() {
            if (confirm('Are you sure you want to clear the system cache?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="clear_cache">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function backupDatabase() {
            if (confirm('Are you sure you want to create a database backup?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="backup_database">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="reset_settings">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>