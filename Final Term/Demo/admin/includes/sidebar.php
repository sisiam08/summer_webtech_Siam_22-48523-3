<div class="admin-sidebar">
    <div class="brand">
        Grocery Admin
    </div>
    <div class="menu">
        <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="material-icons">dashboard</i> Dashboard
        </a>
        <a href="vendors.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : ''; ?>">
            <i class="material-icons">store</i> Vendors
        </a>
        <a href="employees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
            <i class="material-icons">people</i> Employees
        </a>
        <a href="categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="material-icons">category</i> Categories
        </a>
        <a href="customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="material-icons">supervisor_account</i> Customers
        </a>
        <a href="products.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'product_details.php') ? 'active' : ''; ?>">
            <i class="material-icons">visibility</i> Product Oversight
        </a>
        <a href="orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="material-icons">shopping_cart</i> Orders
        </a>
        <a href="reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="material-icons">bar_chart</i> Reports
        </a>
        <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="material-icons">settings</i> Settings
        </a>
    </div>
</div>
