<?php
// Include required files
require_once 'includes/functions.php';

// Set the page title using the configuration
$pageTitle = formatTitle('Configuration Demo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .config-demo {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .config-section {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .config-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .config-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .config-label {
            width: 200px;
            font-weight: bold;
        }
        
        .config-value {
            flex: 1;
        }
        
        code {
            background: #eee;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><?php echo getSiteName(); ?></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="config-demo">
        <h2>Configuration System Demo</h2>
        <p>This page demonstrates how the new configuration system works.</p>
        
        <div class="config-section">
            <h3>Site Information</h3>
            
            <div class="config-row">
                <div class="config-label">Site Name:</div>
                <div class="config-value"><?php echo config('site.name'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Site Tagline:</div>
                <div class="config-value"><?php echo config('site.tagline'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Site URL:</div>
                <div class="config-value"><?php echo config('site.url'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Contact Email:</div>
                <div class="config-value"><?php echo getSupportEmail(); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Contact Phone:</div>
                <div class="config-value"><?php echo getSupportPhone(); ?></div>
            </div>
        </div>
        
        <div class="config-section">
            <h3>Price Formatting</h3>
            
            <div class="config-row">
                <div class="config-label">Currency:</div>
                <div class="config-value"><?php echo config('site.currency'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Currency Symbol:</div>
                <div class="config-value"><?php echo config('site.currency_symbol'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Example Price (1000):</div>
                <div class="config-value"><?php echo formatPrice(1000); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Example Price (1299.99):</div>
                <div class="config-value"><?php echo formatPrice(1299.99); ?></div>
            </div>
        </div>
        
        <div class="config-section">
            <h3>Date and Time Formatting</h3>
            
            <div class="config-row">
                <div class="config-label">Date Format:</div>
                <div class="config-value"><?php echo config('site.date_format'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Time Format:</div>
                <div class="config-value"><?php echo config('site.time_format'); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Current Date:</div>
                <div class="config-value"><?php echo formatDate(date('Y-m-d')); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Current Time:</div>
                <div class="config-value"><?php echo formatTime(date('H:i:s')); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Current Date and Time:</div>
                <div class="config-value"><?php echo formatDateTime(date('Y-m-d H:i:s')); ?></div>
            </div>
        </div>
        
        <div class="config-section">
            <h3>E-commerce Settings</h3>
            
            <div class="config-row">
                <div class="config-label">Default Delivery Charge:</div>
                <div class="config-value"><?php echo formatPrice(getDefaultDeliveryCharge()); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Free Delivery Threshold:</div>
                <div class="config-value"><?php echo formatPrice(getFreeDeliveryThreshold()); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Minimum Order Amount:</div>
                <div class="config-value"><?php echo formatPrice(getMinOrderAmount()); ?></div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Tax Percentage:</div>
                <div class="config-value"><?php echo getTaxPercentage(); ?>%</div>
            </div>
            
            <div class="config-row">
                <div class="config-label">Available Payment Methods:</div>
                <div class="config-value">
                    <ul>
                        <?php foreach (getPaymentMethods() as $code => $name): ?>
                            <li><strong><?php echo $code; ?></strong>: <?php echo $name; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="config-section">
            <h3>Using Configuration Values in Code</h3>
            
            <p>You can access configuration values in your code like this:</p>
            
            <pre><code>// Get a simple value
$siteName = config('site.name');

// Get a value with a default
$deliveryCharge = config('delivery.default_charge', 60);

// Using helper functions
$formattedPrice = formatPrice(1000);
$formattedDate = formatDate('2025-09-05');

// Check user roles
if (isAdmin()) {
    // Admin-only code
}</code></pre>
            
            <p>See the documentation in <code>docs/configuration.md</code> for more details.</p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo getSiteName(); ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
