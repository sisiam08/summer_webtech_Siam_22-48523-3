# Online Grocery Store

A simple PHP web application for an online grocery store. This application is designed for beginners to understand the basics of web development with PHP, HTML, CSS, and JavaScript.

## Features

- User registration and login
- Product browsing with category filtering
- Shopping cart functionality
- Checkout process
- Order confirmation

## Setup Instructions

1. Place all files in your web server directory (e.g., htdocs for XAMPP)
2. Create a database named 'grocery_store' in MySQL
3. Import the database.sql file to create tables and sample data
4. Access the application through your web browser

## File Structure

### Main Pages
1. Home page (index.html)
2. Products page (products.html)
3. Cart page (cart.html)
4. Checkout page (checkout.html)
5. Order confirmation page (order_confirmation.html)
6. Login page (login.html)
7. Registration page (register.html)

### CSS Files
- css/style.css - Main stylesheet for the entire application

### JavaScript Files
- js/script.js - Main JavaScript file with common functionality

### PHP Files
1. Database Connection:
   - php/db_connection.php - Database connection and utility functions

2. Authentication:
   - php/login_process.php - Process login requests
   - php/register_process.php - Process registration requests
   - php/check_login.php - Check if user is logged in

3. Products:
   - php/get_featured_products.php - Get featured products for homepage
   - php/get_products.php - Get all products or by category
   - php/get_categories.php - Get all categories
   - php/search_products.php - Search products by keyword

4. Cart:
   - php/add_to_cart.php - Add a product to cart
   - php/get_cart.php - Get cart contents
   - php/update_cart.php - Update cart quantity
   - php/remove_from_cart.php - Remove product from cart

5. Checkout:
   - php/get_checkout.php - Get checkout page content
   - php/process_order.php - Process order
   - php/get_order.php - Get order details for confirmation

### Database
- database.sql - SQL file with table structure and sample data

### Images
- images/products/ - Directory for product images

## Login Credentials

- Admin: admin@example.com / admin123
- User: user@example.com / user123

## Technologies Used

- PHP - Backend scripting
- MySQL - Database
- HTML - Structure
- CSS - Styling
- JavaScript - Client-side functionality
- AJAX - Asynchronous requests

## Implemented Flow

1. User registers an account or logs in
2. User browses products by category or search
3. User adds products to cart
4. User proceeds to checkout
5. User enters shipping information
6. User selects payment method
7. User places order
8. User receives order confirmation
   ```
   mysql -u username -p database_name < database/migration.sql
   ```

3. Configure the environment variables:
   ```
   cp .env.example .env
   ```
   Edit the `.env` file with your database credentials and other settings.

4. Set up the web server:
   - For Apache, ensure the `.htaccess` file is properly configured
   - For Nginx, set up the appropriate server block

5. Start the server and visit the application in your browser.

## Usage

### Customer Flow
1. Register/Login
2. Browse products by category or search
3. Add products to cart
4. Proceed to checkout
5. Select delivery address and time
6. Choose payment method
7. Place order
8. Track order status

### Store Owner Flow
1. Register/Login as store owner
2. Set up store profile
3. Add products
4. Manage inventory
5. Process orders
6. View reports

### Admin Flow
1. Login as admin
2. Manage users, stores, and products
3. View and process reports
4. Configure system settings

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature-name`
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contact

For any questions or support, please contact:
- Email: your.email@example.com
- GitHub: [Your GitHub Username](https://github.com/yourusername)
