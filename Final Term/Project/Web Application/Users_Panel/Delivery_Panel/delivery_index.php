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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Online Grocery Store</title>
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
                    <h3 id="delivery-person-name"><?php echo htmlspecialchars($userName); ?></h3>
                    <p id="delivery-person-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <ul>
                    <li class="active">
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
                    <li>
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
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">assignment</i>
                    </div>
                    <div class="stat-content">
                        <h3 id="pending-deliveries">0</h3>
                        <p>Pending Deliveries</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">check_circle</i>
                    </div>
                    <div class="stat-content">
                        <h3 id="completed-today">0</h3>
                        <p>Completed Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">monetization_on</i>
                    </div>
                    <div class="stat-content">
                        <h3 id="earnings-today">৳0</h3>
                        <p>Today's Earnings</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="material-icons">star</i>
                    </div>
                    <div class="stat-content">
                        <h3 id="rating">5.0</h3>
                        <p>Average Rating</p>
                    </div>
                </div>
            </div>

            <div class="current-delivery">
                <h2>Current Delivery</h2>
                <div id="current-delivery-card" class="delivery-card">
                    <p>No active delivery at the moment</p>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <div id="recent-deliveries" class="activity-list">
                    <p>Loading recent activities...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadCurrentDelivery();
            loadRecentActivity();
        });

        async function loadDashboardData() {
            try {
                const response = await fetch('get_delivery_stats.php');
                const data = await response.json();
                
                console.log('Stats response:', data); // Debug log
                
                if (data.success) {
                    document.getElementById('pending-deliveries').textContent = data.stats.pending || 0;
                    document.getElementById('completed-today').textContent = data.stats.completed_today || 0;
                    document.getElementById('earnings-today').textContent = '৳' + (data.stats.earnings_today || '0.00');
                    document.getElementById('rating').textContent = data.stats.rating || '5.0';
                } else {
                    console.error('Stats error:', data.error);
                    // Set default values on error
                    document.getElementById('pending-deliveries').textContent = '0';
                    document.getElementById('completed-today').textContent = '0';
                    document.getElementById('earnings-today').textContent = '৳0.00';
                    document.getElementById('rating').textContent = '5.0';
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                // Set default values on error
                document.getElementById('pending-deliveries').textContent = '0';
                document.getElementById('completed-today').textContent = '0';
                document.getElementById('earnings-today').textContent = '৳0.00';
                document.getElementById('rating').textContent = '5.0';
            }
        }

        function loadCurrentDelivery() {
            // Disabled - API endpoint doesn't exist
            console.log('Current delivery loading disabled');
        }

        function loadRecentActivity() {
            // Disabled - API endpoint doesn't exist  
            console.log('Recent activity loading disabled');
        }

        function displayCurrentDelivery(delivery) {
            const card = document.getElementById('current-delivery-card');
            card.innerHTML = `
                <div class="delivery-info">
                    <h4>Order #${delivery.order_id}</h4>
                    <p><strong>Customer:</strong> ${delivery.customer_name}</p>
                    <p><strong>Address:</strong> ${delivery.delivery_address}</p>
                    <p><strong>Status:</strong> ${delivery.status}</p>
                </div>
                <div class="delivery-actions">
                    <button class="btn-primary" onclick="window.location.href='delivery-details.html?id=${delivery.id}'">
                        View Details
                    </button>
                </div>
            `;
        }

        function displayRecentActivity(deliveries) {
            const container = document.getElementById('recent-deliveries');
            
            if (!deliveries || deliveries.length === 0) {
                container.innerHTML = '<p>No recent activity</p>';
                return;
            }

            container.innerHTML = deliveries.map(delivery => `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="material-icons">check_circle</i>
                    </div>
                    <div class="activity-content">
                        <p><strong>Order #${delivery.order_id}</strong> delivered successfully</p>
                        <small>${delivery.completed_at}</small>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>
