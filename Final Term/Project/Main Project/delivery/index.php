<?php
// Initialize session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../helpers.php';

// Check if user is logged in and is a delivery person
if (!isLoggedIn() || !isDelivery()) {
    redirect('../login.php');
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/delivery.css">
</head>
<body class="delivery-dashboard">
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="assignments.html">My Assignments</a></li>
                    <li><a href="completed.html">Completed Deliveries</a></li>
                    <li><a href="profile.html">My Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

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
                        <a href="index.php">
                            <i class="material-icons">dashboard</i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="assignments.html">
                            <i class="material-icons">assignment</i>
                            My Assignments
                        </a>
                    </li>
                    <li>
                        <a href="completed.html">
                            <i class="material-icons">check_circle</i>
                            Completed
                        </a>
                    </li>
                    <li>
                        <a href="profile.html">
                            <i class="material-icons">person</i>
                            My Profile
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
                    <button class="btn-primary" onclick="window.location.href='assignments.html'">
                        View Assignments
                    </button>
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

        function loadDashboardData() {
            fetch('../api/delivery/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pending-deliveries').textContent = data.stats.pending || 0;
                        document.getElementById('completed-today').textContent = data.stats.completed_today || 0;
                        document.getElementById('earnings-today').textContent = '৳' + (data.stats.earnings_today || 0);
                        document.getElementById('rating').textContent = data.stats.rating || '5.0';
                    }
                })
                .catch(error => console.error('Error loading dashboard data:', error));
        }

        function loadCurrentDelivery() {
            fetch('../api/delivery/get_current_delivery.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.delivery) {
                        displayCurrentDelivery(data.delivery);
                    }
                })
                .catch(error => console.error('Error loading current delivery:', error));
        }

        function loadRecentActivity() {
            fetch('../api/delivery/get_completed.php?limit=5')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRecentActivity(data.deliveries);
                    }
                })
                .catch(error => console.error('Error loading recent activity:', error));
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
