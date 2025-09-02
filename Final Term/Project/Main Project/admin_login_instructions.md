## Admin Login Instructions

You already have an admin account in your database with these credentials:

- **Email:** `admin@example.com`
- **Password:** `admin123`

To log in to the admin panel:

1. Go to the login page: http://localhost:8000/login.html (after starting the PHP server)
2. Enter the email: `admin@example.com`
3. Enter the password: `admin123`
4. Click the Login button

If you're still having issues logging in, you can try these troubleshooting steps:

1. Make sure you're using the exact credentials above (no extra spaces)
2. Check if your database connection is working properly
3. Ensure the login form is submitting to the correct endpoint

## Starting the PHP Server

Run the `start_php_server.bat` file to start a PHP development server on port 8000. Then you can access your application at http://localhost:8000/

## Alternative Login Method

If the regular login isn't working, you can try this PHP script to automatically log you in as admin:

```php
<?php
// Auto login as admin script
session_start();

// Set admin session directly
$_SESSION['user_id'] = 1; // Assuming admin ID is 1
$_SESSION['user_role'] = 'admin';

echo "Successfully logged in as admin!";
echo "<p>Click <a href='admin/index.html'>here</a> to go to admin panel.</p>";
?>
```

Save this as `auto_login_admin.php` in your project folder and access it through http://localhost:8000/auto_login_admin.php
