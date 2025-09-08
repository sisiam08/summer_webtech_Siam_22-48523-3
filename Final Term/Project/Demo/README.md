# Online Grocery Store

A complete e-commerce platform for online grocery shopping with multiple user roles and multi-shop functionality.

## Features

- Multi-role system (Admin, Shop Owner, Delivery Person, Customer)
- Multi-shop functionality with separate shops and delivery charges
- User registration and authentication
- Product management and browsing
- Shopping cart with items grouped by shop
- Shop-specific delivery charges
- Order processing and tracking
- Admin dashboard with analytics

## Project Structure (Reorganized)

```
Main Project/
├── admin/              # Admin panel frontend files
├── assets/             # All static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Images and icons
├── auth/               # Authentication related files
│   ├── login_process.php
│   ├── register_process.php
│   └── logout.php
├── config/             # Configuration files
│   ├── database.php    # Database connection
│   └── constants.php   # Application constants
├── customer/           # Customer panel frontend files
├── database/           # Database scripts
│   ├── migrations/     # Database structure
│   └── seeds/          # Sample data
├── delivery/           # Delivery panel frontend files
├── includes/           # Shared PHP files
│   ├── functions.php   # General functions
│   ├── helpers.php     # Helper functions
│   ├── session.php     # Session management
│   └── shop_functions.php # Multi-shop functionality
├── shop_owner/         # Shop owner panel frontend files
├── uploads/            # For uploaded files
├── api/                # API endpoints for AJAX calls
│   ├── admin/          # Admin API endpoints
│   ├── customer/       # Customer API endpoints
│   ├── delivery/       # Delivery API endpoints
│   └── shop_owner/     # Shop owner API endpoints
├── index.php           # Main entry point
├── cleanup.php/bat     # Cleanup utility
├── setup_database.php/bat # Database setup utility
└── README.md           # This file
```

## Multi-Shop Functionality

The platform now supports multiple shops with the following features:

1. **Shop Management:**
   - Each shop has its own profile, products, and delivery settings
   - Shop owners can set their own delivery charges

2. **Product Browsing:**
   - Products are displayed with shop information
   - Customers can filter products by shop or category
   - Product cards show the shop name and delivery fee

3. **Cart Management:**
   - Items in cart are grouped by shop
   - Each shop has its own subtotal
   - Delivery charges are calculated per shop

4. **Checkout Process:**
   - Separate orders are created for each shop
   - Delivery charges are applied for each shop order
   - Order confirmation shows items grouped by shop

5. **Order Tracking:**
   - Shop owners can manage their own orders
   - Customers can see order status for each shop
   - Delivery personnel are assigned to specific shop orders
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

## User Roles

1. **Admin**
   - Email: admin@example.com
   - Password: admin123
   - Access: Full administrative control

2. **Shop Owner**
   - Manages products, inventory, and orders for their shop

3. **Delivery Person**
   - Handles order deliveries and status updates

4. **Customer**
   - Browses products, places orders, and manages their account

## Setup Instructions

1. **Database Setup**
   - Make sure MySQL is running with these credentials:
     - Host: 127.0.0.1
     - Username: root
     - Password: Siam@MySQL2025
     - Database: grocery_store
   - Run `setup_database.bat` to create the necessary database and tables

2. **Running the Application**
   - Run `start_php_server.bat` to start the PHP development server
   - Access the application at http://localhost:8000

3. **Project Cleanup**
   - To remove temporary, testing, and unnecessary files:
   - Run `cleanup.bat` to start the cleanup process
   - A browser window will open showing files to be removed
   - Click "Confirm Cleanup" to proceed
   - This will clean up the project structure and remove all debug files

## Login Information

- **Admin Panel**: http://localhost:8000/admin/index.html
  - Email: admin@example.com
  - Password: admin123

- **Shop Owner Panel**: http://localhost:8000/shop_owner/login.html
  - Email: shopowner@test.com
  - Password: password123

- **Delivery Panel**: http://localhost:8000/delivery/index.html
  - Create a delivery account through the admin panel

- **Customer Panel**: Access through the main site after login
  - Register at http://localhost:8000/register.html

## Project Reorganization

If you're seeing the old project structure, use the following steps to reorganize:

1. Run `reorganize.bat` to start the reorganization process
2. Follow the on-screen instructions to complete each step
3. After reorganization, test all functionality to ensure everything works correctly
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
