// Dashboard functionality - Simple functions without classes

// Check user authentication status
async function checkUserStatus() {
    try {
        const response = await fetch('check_auth.php');
        const data = await response.json();
        
        if (data.isAuthenticated && data.role === 'customer') {
            // User is logged in as customer
            document.body.classList.add('logged-in', 'customer');
            updateNavigation(data);
            updatePageTitle('Customer Dashboard');
        } else {
            // User is not logged in or not a customer
            document.body.classList.remove('logged-in', 'customer');
            updatePageTitle('Online Grocery Store');
        }
    } catch (error) {
        console.error('Error checking user status:', error);
    }
}

// Update navigation based on user status
function updateNavigation(userData) {
    const nav = document.querySelector('nav ul');
    if (nav) {
        // Update navigation for logged-in users
        nav.innerHTML = `
            <li><a href="index.html">Home</a></li>
            <li><a href="products.html">Products</a></li>
            <li><a href="cart.html"></a></li>
            <li><a href="account.html">My Account</a></li>
            <li><a href="logout.php">Logout</a></li>
        `;
        
        // Update welcome message
        const welcomeMessage = document.querySelector('.welcome-message h2');
        if (welcomeMessage && userData.name) {
            welcomeMessage.textContent = `Welcome back, ${userData.name}!`;
        }
    }
}

// Update page title
function updatePageTitle(title) {
    document.title = title;
}

// Load dashboard data for logged-in customers
async function loadDashboardData() {
    if (document.body.classList.contains('customer')) {
        loadDashboardStats();
        loadRecentOrders();
    }
}

// Load and display dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch('get_dashboard_summary.php');
        const data = await response.json();
        
        if (data.success) {
            updateStats(data.stats);
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Update statistics on the page
function updateStats(stats) {
    const elements = {
        'total-orders': stats.total_orders || 0,
        'pending-orders': stats.pending_orders || 0,
        'total-spent': '$' + (stats.total_spent || 0),
        'wishlist-count': stats.wishlist_count || 0
    };

    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }
}

// Load and display recent orders
async function loadRecentOrders() {
    try {
        const response = await fetch('get_recent_orders.php');
        const data = await response.json();
        
        if (data.success) {
            displayRecentOrders(data.orders);
        }
    } catch (error) {
        console.error('Error loading recent orders:', error);
        displayOrdersError();
    }
}

// Display recent orders in the UI
function displayRecentOrders(orders) {
    const container = document.getElementById('recent-orders-list');
    if (!container) return;

    if (orders.length === 0) {
        container.innerHTML = '<p>No recent orders found.</p>';
        return;
    }

    let html = '<div class="orders-list">';
    orders.forEach(order => {
        html += `
            <div class="order-item">
                <div class="order-info">
                    <strong>Order #${order.id}</strong>
                    <span class="order-date">${order.created_at}</span>
                </div>
                <div class="order-status status-${order.status.toLowerCase()}">${order.status}</div>
                <div class="order-total">$${order.total}</div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

// Display error message when orders fail to load
function displayOrdersError() {
    const container = document.getElementById('recent-orders-list');
    if (container) {
        container.innerHTML = '<p>Error loading recent orders.</p>';
    }
}

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    checkUserStatus();
    loadDashboardData();
});
