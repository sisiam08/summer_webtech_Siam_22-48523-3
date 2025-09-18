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
    // Redirect to login page if not authenticated
    header("Location: ../../Authentication/login.html");
    exit;
}

// Get pending counts for badges
$conn = connectDB();

// Get pending shop owners count
$sql = "SELECT COUNT(*) as count FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        WHERE u.role = 'shop_owner' AND s.id IS NULL AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingShopOwnersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending delivery men count
$sql = "SELECT COUNT(*) as count FROM users u 
        WHERE u.role = 'delivery_man' AND u.is_active = 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pendingDeliveryMenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Create arrays for compatibility with existing code
$pendingShopOwners = array_fill(0, $pendingShopOwnersCount, null);
$pendingDeliveryMen = array_fill(0, $pendingDeliveryMenCount, null);

$conn = null;

// User is authenticated as admin, proceed with the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../Includes/style.css">
    <link rel="stylesheet" href="admin.css">
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
            <a href="categories.php" class="menu-item active">
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
            <h2>Categories Management</h2>
            <div class="user-info">
                <div class="dropdown">
                    <span id="admin-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <i class="material-icons">arrow_drop_down</i>
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="../../Authentication/logout.php" id="logout-btn">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="categories-container">
            <div class="actions-bar">
                <button id="add-category-btn" class="btn primary">
                    <i class="material-icons">add</i> Add New Category
                </button>
                <div class="search-box">
                    <input type="text" id="search-categories" placeholder="Search categories...">
                    <i class="material-icons">search</i>
                </div>
            </div>

            <div class="categories-table-container">
                <table id="categories-table" class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table-body">
                        <!-- Categories will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add New Category</h3>
                <span class="close" id="close-modal">&times;</span>
            </div>
            <form id="category-form">
                <input type="hidden" id="category-id" name="category_id">
                <div class="form-group">
                    <label for="category-name">Category Name</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="category-description">Description</label>
                    <textarea id="category-description" name="description" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" id="cancel-btn" class="btn secondary">Cancel</button>
                    <button type="submit" id="save-btn" class="btn primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Category management functions
        let categories = [];
        let editingCategoryId = null;

        // Load categories on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Add category button
            document.getElementById('add-category-btn').addEventListener('click', function() {
                openCategoryModal();
            });

            // Close modal
            document.getElementById('close-modal').addEventListener('click', closeCategoryModal);
            document.getElementById('cancel-btn').addEventListener('click', closeCategoryModal);

            // Search functionality
            document.getElementById('search-categories').addEventListener('input', function(e) {
                filterCategories(e.target.value);
            });

            // Form submission
            document.getElementById('category-form').addEventListener('submit', handleCategorySubmit);

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                const modal = document.getElementById('category-modal');
                if (e.target === modal) {
                    closeCategoryModal();
                }
            });
        }

        function loadCategories() {
            fetch('get_categories.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        categories = data.categories;
                        displayCategories(categories);
                    } else {
                        alert('Error loading categories: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                    alert('Error loading categories. Please try again.');
                });
        }

        function displayCategories(categoriesToShow) {
            const tbody = document.getElementById('categories-table-body');
            tbody.innerHTML = '';

            categoriesToShow.forEach(category => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${category.id}</td>
                    <td>${category.name}</td>
                    <td>${category.description || 'No description'}</td>
                    <td>${new Date(category.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn small primary" onclick="editCategory(${category.id})">
                            <i class="material-icons">edit</i>
                        </button>
                        <button class="btn small danger" onclick="deleteCategory(${category.id})">
                            <i class="material-icons">delete</i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function filterCategories(searchTerm) {
            const filtered = categories.filter(category => 
                category.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (category.description && category.description.toLowerCase().includes(searchTerm.toLowerCase()))
            );
            displayCategories(filtered);
        }

        function openCategoryModal(category = null) {
            const modal = document.getElementById('category-modal');
            const title = document.getElementById('modal-title');
            const form = document.getElementById('category-form');

            if (category) {
                // Edit mode
                title.textContent = 'Edit Category';
                document.getElementById('category-id').value = category.id;
                document.getElementById('category-name').value = category.name;
                document.getElementById('category-description').value = category.description || '';
                editingCategoryId = category.id;
            } else {
                // Add mode
                title.textContent = 'Add New Category';
                form.reset();
                document.getElementById('category-id').value = '';
                editingCategoryId = null;
            }

            modal.style.display = 'block';
        }

        function closeCategoryModal() {
            document.getElementById('category-modal').style.display = 'none';
            document.getElementById('category-form').reset();
            editingCategoryId = null;
        }

        function handleCategorySubmit(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = {
                name: formData.get('name'),
                description: formData.get('description')
            };

            const url = editingCategoryId ? 'update_category.php' : 'add_category.php';
            if (editingCategoryId) {
                data.id = editingCategoryId;
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    closeCategoryModal();
                    loadCategories(); // Reload categories
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving category. Please try again.');
            });
        }

        function editCategory(id) {
            const category = categories.find(c => c.id == id);
            if (category) {
                openCategoryModal(category);
            }
        }

        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                fetch('delete_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        loadCategories(); // Reload categories
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting category. Please try again.');
                });
            }
        }
    </script>
</body>
</html>