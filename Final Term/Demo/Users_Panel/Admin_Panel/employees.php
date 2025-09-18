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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_employee') {
            // Add new employee
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $password = $_POST['password'] ?? '';
            $department = $_POST['department'] ?? '';
            $position = $_POST['position'] ?? '';
            $salary = $_POST['salary'] ?? '';
            $hire_date = $_POST['hire_date'] ?? '';
            
            // Validate input
            if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($department) || empty($position) || empty($salary) || empty($hire_date)) {
                $error_message = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Invalid email format.';
            } elseif (!is_numeric($salary) || $salary <= 0) {
                $error_message = 'Invalid salary amount.';
            } else {
                // Check if email already exists in employees table
                $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
                $stmt->bindParam(1, $email, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Email already exists.';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Add employee to database
                    $stmt = $conn->prepare("INSERT INTO employees (name, email, phone, password, department, position, salary, hire_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                    $stmt->bindParam(1, $name, PDO::PARAM_STR);
                    $stmt->bindParam(2, $email, PDO::PARAM_STR);
                    $stmt->bindParam(3, $phone, PDO::PARAM_STR);
                    $stmt->bindParam(4, $hashed_password, PDO::PARAM_STR);
                    $stmt->bindParam(5, $department, PDO::PARAM_STR);
                    $stmt->bindParam(6, $position, PDO::PARAM_STR);
                    $stmt->bindParam(7, $salary, PDO::PARAM_STR);
                    $stmt->bindParam(8, $hire_date, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Employee added successfully.';
                    } else {
                        $error_message = 'Error adding employee.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'update_employee') {
            // Update employee information
            $employee_id = $_POST['employee_id'] ?? '';
            $department = $_POST['department'] ?? '';
            $position = $_POST['position'] ?? '';
            $salary = $_POST['salary'] ?? '';
            
            if (empty($employee_id) || empty($department) || empty($position) || empty($salary)) {
                $error_message = 'All fields are required.';
            } elseif (!is_numeric($salary) || $salary <= 0) {
                $error_message = 'Invalid salary amount.';
            } else {
                $stmt = $conn->prepare("UPDATE employees SET department = ?, position = ?, salary = ? WHERE id = ?");
                $stmt->bindParam(1, $department, PDO::PARAM_STR);
                $stmt->bindParam(2, $position, PDO::PARAM_STR);
                $stmt->bindParam(3, $salary, PDO::PARAM_STR);
                $stmt->bindParam(4, $employee_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $success_message = 'Employee information updated successfully.';
                } else {
                    $error_message = 'Error updating employee information.';
                }
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Enable/disable employee
            $employee_id = $_POST['employee_id'] ?? '';
            $is_active = $_POST['is_active'] ?? '';
            
            if (empty($employee_id) || $is_active === '') {
                $error_message = 'Employee ID and status are required.';
            } else {
                $stmt = $conn->prepare("UPDATE employees SET is_active = ? WHERE id = ?");
                $stmt->bindParam(1, $is_active, PDO::PARAM_INT);
                $stmt->bindParam(2, $employee_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $success_message = 'Employee status updated successfully.';
                } else {
                    $error_message = 'Error updating employee status.';
                }
            }
        }
    }
}

// Create employees table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    department ENUM('Management', 'Engineering', 'Design', 'Marketing', 'Sales', 'Finance', 'Human Resources', 'Operations', 'Customer Support', 'Quality Assurance') NOT NULL,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    hire_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->exec($createTableSQL);

// Get all employees
$stmt = $conn->prepare("SELECT id, name, email, phone, department, position, salary, hire_date, is_active, created_at FROM employees ORDER BY created_at DESC");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Employee Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .employee-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .employee-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .employee-form.show {
            display: block;
        }
        .employee-form h3 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
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
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: #f9f9f9;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-management {
            background-color: #8e44ad;
            color: white;
        }
        .badge-engineering {
            background-color: #3498db;
            color: white;
        }
        .badge-design {
            background-color: #e67e22;
            color: white;
        }
        .badge-marketing {
            background-color: #e74c3c;
            color: white;
        }
        .badge-sales {
            background-color: #27ae60;
            color: white;
        }
        .badge-finance {
            background-color: #f39c12;
            color: white;
        }
        .badge-human-resources {
            background-color: #9b59b6;
            color: white;
        }
        .badge-operations {
            background-color: #34495e;
            color: white;
        }
        .badge-customer-support {
            background-color: #16a085;
            color: white;
        }
        .badge-quality-assurance {
            background-color: #2c3e50;
            color: white;
        }
        .badge-active {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .text-center {
            text-align: center;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .modal-header h3 {
            margin: 0;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
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
            <a href="employees.php" class="menu-item active">
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
            <h2>Employee Management</h2>
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

        <div style="margin-bottom: 20px;">
            <button class="btn btn-primary" id="show-add-form">
                <i class="material-icons">add</i> Add New Employee
            </button>
        </div>

        <div class="employee-form" id="add-employee-form">
            <h3>Add New Employee</h3>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_employee">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Management">Management</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Design">Design</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Finance">Finance</option>
                            <option value="Human Resources">Human Resources</option>
                            <option value="Operations">Operations</option>
                            <option value="Customer Support">Customer Support</option>
                            <option value="Quality Assurance">Quality Assurance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" required placeholder="e.g., Senior Developer, Marketing Manager">
                    </div>
                    <div class="form-group">
                        <label for="salary">Monthly Salary (৳)</label>
                        <input type="number" id="salary" name="salary" required min="1" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="hire_date">Hire Date</label>
                        <input type="date" id="hire_date" name="hire_date" required>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                    <button type="button" class="btn btn-secondary" id="cancel-add">Cancel</button>
                </div>
            </form>
        </div>

        <div class="employee-container">
            <div style="padding: 20px;">
                <h3>Current Employees</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Salary</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No employees found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $employee['department'])); ?>">
                                            <?php echo htmlspecialchars($employee['department']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td>৳<?php echo number_format($employee['salary'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $employee['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info edit-employee" 
                                                    data-id="<?php echo $employee['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                    data-department="<?php echo $employee['department']; ?>"
                                                    data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                                    data-salary="<?php echo $employee['salary']; ?>">
                                                Edit
                                            </button>
                                            
                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $employee['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" 
                                                        class="btn btn-sm <?php echo $employee['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                                        onclick="return confirm('Are you sure you want to <?php echo $employee['is_active'] ? 'deactivate' : 'activate'; ?> this employee?')">
                                                    <?php echo $employee['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
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

    <!-- Modal for editing employee -->
    <div id="edit-employee-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Employee Information</h3>
                <span class="close" id="close-modal">&times;</span>
            </div>
            <form method="post" action="">
                <input type="hidden" name="action" value="update_employee">
                <input type="hidden" name="employee_id" id="edit-employee-id">
                <p id="edit-employee-name" style="margin-bottom: 15px; font-weight: 600;"></p>
                <div class="form-group">
                    <label for="edit-department">Department</label>
                    <select id="edit-department" name="department" required>
                        <option value="Management">Management</option>
                        <option value="Engineering">Engineering</option>
                        <option value="Design">Design</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Sales">Sales</option>
                        <option value="Finance">Finance</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Operations">Operations</option>
                        <option value="Customer Support">Customer Support</option>
                        <option value="Quality Assurance">Quality Assurance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-position">Position</label>
                    <input type="text" id="edit-position" name="position" required>
                </div>
                <div class="form-group">
                    <label for="edit-salary">Monthly Salary (৳)</label>
                    <input type="number" id="edit-salary" name="salary" required min="1" step="0.01">
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" id="cancel-edit">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle add employee form
        document.getElementById('show-add-form').addEventListener('click', function() {
            const form = document.getElementById('add-employee-form');
            form.classList.add('show');
        });
        
        document.getElementById('cancel-add').addEventListener('click', function() {
            const form = document.getElementById('add-employee-form');
            form.classList.remove('show');
        });
        
        // Edit employee modal
        const editEmployeeButtons = document.querySelectorAll('.edit-employee');
        const editEmployeeModal = document.getElementById('edit-employee-modal');
        const cancelEdit = document.getElementById('cancel-edit');
        const closeModal = document.getElementById('close-modal');
        
        editEmployeeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const employeeId = this.getAttribute('data-id');
                const employeeName = this.getAttribute('data-name');
                const department = this.getAttribute('data-department');
                const position = this.getAttribute('data-position');
                const salary = this.getAttribute('data-salary');
                
                document.getElementById('edit-employee-id').value = employeeId;
                document.getElementById('edit-employee-name').textContent = 'Employee: ' + employeeName;
                document.getElementById('edit-department').value = department;
                document.getElementById('edit-position').value = position;
                document.getElementById('edit-salary').value = salary;
                
                editEmployeeModal.style.display = 'block';
            });
        });
        
        cancelEdit.addEventListener('click', function() {
            editEmployeeModal.style.display = 'none';
        });
        
        closeModal.addEventListener('click', function() {
            editEmployeeModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editEmployeeModal) {
                editEmployeeModal.style.display = 'none';
            }
        });
        
        // Set today as max date for hire date
        document.getElementById('hire_date').max = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>