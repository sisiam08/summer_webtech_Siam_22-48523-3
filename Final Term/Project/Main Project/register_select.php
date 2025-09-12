<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Registration Type - Online Grocery Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .registration-selector {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
        }
        
        .registration-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .registration-card {
            background: white;
            padding: 30px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .registration-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .registration-card .icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #4CAF50;
        }
        
        .registration-card h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .registration-card p {
            color: #666;
            line-height: 1.5;
            margin: 0;
        }
        
        .login-link {
            margin-top: 40px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Online Grocery Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="registration-selector">
            <h2>Join Our Platform</h2>
            <p>Choose how you'd like to join our online grocery store community</p>
            
            <div class="registration-cards">
                <a href="register.php" class="registration-card">
                    <div class="icon">🛒</div>
                    <h3>Register as Customer</h3>
                    <p>Shop for fresh groceries and get them delivered to your doorstep. Enjoy convenient online shopping experience.</p>
                </a>
                
                <a href="shop_owner/register.html" class="registration-card">
                    <div class="icon">🏪</div>
                    <h3>Become a Shop Owner</h3>
                    <p>Start selling your products on our platform. Reach more customers and grow your business with us.</p>
                </a>
                
                <a href="delivery/apply.html" class="registration-card">
                    <div class="icon">🚚</div>
                    <h3>Join as Delivery Partner</h3>
                    <p>Earn flexible income by delivering groceries. Work on your own schedule and be part of our delivery team.</p>
                </a>
            </div>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><small>All users (customers, shop owners, and delivery partners) use the same login page</small></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Online Grocery Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
