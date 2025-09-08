<?php
// Initialize session
session_start();

// Include required files
require_once '../database_connection.php';
require_once '../helpers.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_employee') {
            // Add new employee
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            
            // Validate input
            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $error_message = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Invalid email format.';
            } elseif (!in_array($role, ['shop_owner', 'delivery_man', 'admin'])) {
                $error_message = 'Invalid role selected.';
            } else {
                // Check if email already exists
                $sql = "SELECT id FROM users WHERE email = '$email'";
                $result = mysqli_query($conn, $sql);
                
                if (mysqli_num_rows($result) > 0) {
                    $error_message = 'Email already exists.';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Add employee to database
                    $sql = "INSERT INTO users (name, email, password, role, is_active) 
                            VALUES ('$name', '$email', '$hashed_password', '$role', 1)";
                    
                    if (mysqli_query($conn, $sql)) {
                        $success_message = 'Employee added successfully.';
                    } else {
                        $error_message = 'Error adding employee: ' . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] === 'update_role') {
            // Update employee role
            $user_id = $_POST['user_id'] ?? '';
            $role = $_POST['role'] ?? '';
            
            if (empty($user_id) || empty($role)) {
                $error_message = 'User ID and role are required.';
            } elseif (!in_array($role, ['shop_owner', 'delivery_man', 'admin', 'customer'])) {
                $error_message = 'Invalid role selected.';
            } else {
                $sql = "UPDATE users SET role = '$role' WHERE id = $user_id";
                
                if (mysqli_query($conn, $sql)) {
                    $success_message = 'Employee role updated successfully.';
                } else {
                    $error_message = 'Error updating employee role: ' . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Enable/disable employee
            $user_id = $_POST['user_id'] ?? '';
            $is_active = $_POST['is_active'] ?? '';
            
            if (empty($user_id) || $is_active === '') {
                $error_message = 'User ID and status are required.';
            } else {
                $sql = "UPDATE users SET is_active = $is_active WHERE id = $user_id";
                
                if (mysqli_query($conn, $sql)) {
                    $success_message = 'Employee status updated successfully.';
                } else {
                    $error_message = 'Error updating employee status: ' . mysqli_error($conn);
                }
            }
        }
    }
}

// Get all employees
$sql = "SELECT id, name, email, role, is_active, created_at FROM users WHERE role IN ('admin', 'shop_owner', 'delivery_man') ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$employees = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
}

// Get current admin info
$current_admin = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Admin Panel</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .admin-container {
            display: flex;
            min-height: calc(100vh - 60px - 60px);
        }
        
        .admin-sidebar {
            width: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .admin-sidebar li {
            margin-bottom: 10px;
        }
        
        .admin-sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .admin-sidebar a:hover, .admin-sidebar a.active {
            background-color: #e9ecef;
        }
        
        .admin-content {
            flex: 1;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th, .admin-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
        }
        
        .admin-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .admin-form h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .alert {
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
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
        
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-primary {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Panel - Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="../index.php" target="_blank">View Site</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-container">
            <div class="admin-sidebar">
                <h3>Admin Menu</h3>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="products.php">Manage Products</a></li>
                    <li><a href="categories.php">Manage Categories</a></li>
                    <li><a href="orders.php">Manage Orders</a></li>
                    <li><a href="customers.php">Manage Customers</a></li>
                    <li><a href="employees.php" class="active">Manage Employees</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Manage Employees</h2>
                    <button class="btn" id="show-add-form">Add New Employee</button>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="admin-form" id="add-employee-form" style="display: none;">
                    <h3>Add New Employee</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add_employee">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="shop_owner">Shop Owner</option>
                                <option value="delivery_man">Delivery Person</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn">Add Employee</button>
                            <button type="button" class="btn btn-secondary" id="cancel-add">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="employee-list">
                    <h3>Current Employees</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No employees found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo $employee['id']; ?></td>
                                        <td><?php echo $employee['name']; ?></td>
                                        <td><?php echo $employee['email']; ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $employee['role'] === 'admin' ? 'badge-primary' : 
                                                    ($employee['role'] === 'shop_owner' ? 'badge-info' : 'badge-success'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $employee['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $employee['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($employee['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm edit-role" data-id="<?php echo $employee['id']; ?>" 
                                                        data-name="<?php echo $employee['name']; ?>"
                                                        data-role="<?php echo $employee['role']; ?>">
                                                    Edit Role
                                                </button>
                                                
                                                <?php if ($employee['id'] != $current_admin['id']): ?>
                                                    <form method="post" action="" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $employee['is_active'] ? 0 : 1; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $employee['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                                                onclick="return confirm('Are you sure you want to <?php echo $employee['is_active'] ? 'deactivate' : 'activate'; ?> this employee?')">
                                                            <?php echo $employee['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing role -->
    <div id="edit-role-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 999;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 5px; width: 400px;">
            <h3>Edit Employee Role</h3>
            <form method="post" action="">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="edit-user-id">
                <p id="edit-user-name" style="margin-bottom: 15px;"></p>
                <div class="form-group">
                    <label for="edit-role">Role</label>
                    <select id="edit-role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="shop_owner">Shop Owner</option>
                        <option value="delivery_man">Delivery Person</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" id="cancel-edit">Cancel</button>
                    <button type="submit" class="btn">Update Role</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Toggle add employee form
        document.getElementById('show-add-form').addEventListener('click', function() {
            document.getElementById('add-employee-form').style.display = 'block';
        });
        
        document.getElementById('cancel-add').addEventListener('click', function() {
            document.getElementById('add-employee-form').style.display = 'none';
        });
        
        // Edit role modal
        const editRoleButtons = document.querySelectorAll('.edit-role');
        const editRoleModal = document.getElementById('edit-role-modal');
        const cancelEdit = document.getElementById('cancel-edit');
        
        editRoleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const userRole = this.getAttribute('data-role');
                
                document.getElementById('edit-user-id').value = userId;
                document.getElementById('edit-user-name').textContent = 'Employee: ' + userName;
                document.getElementById('edit-role').value = userRole;
                
                editRoleModal.style.display = 'block';
            });
        });
        
        cancelEdit.addEventListener('click', function() {
            editRoleModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editRoleModal) {
                editRoleModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
