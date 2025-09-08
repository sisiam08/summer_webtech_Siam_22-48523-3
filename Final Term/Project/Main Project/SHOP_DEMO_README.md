# Shop Demo Instructions

This document provides step-by-step instructions for setting up shops and viewing their products in the customer site.

## Quick Setup

For a complete setup that handles everything automatically, run:

```
setup_shops_demo.bat
```

This script will:
1. Set up the basic database structure
2. Update the database for multi-shop functionality
3. Create sample shops with products
4. Provide login details for shop owners

## Manual Setup Steps

If you prefer to set up things manually, follow these steps:

### Step 1: Set up the basic database

Run the database setup script to create the necessary tables:

```
setup_database.bat
```

### Step 2: Enable multi-shop functionality

Update the database to support multi-shop functionality:

```
update_multi_shop_db.bat
```

### Step 3: Create sample shops

Add sample shops with products to the database:

```
create_sample_shops.bat
```

## Shop Owner Login Credentials

After setup, you can log in with the following credentials:

### Shop #1: Fresh Farms
- Email: freshfarms@example.com
- Password: password123
- Products: Fresh fruits and vegetables

### Shop #2: Organic World
- Email: organicworld@example.com
- Password: password123
- Products: Organic produce and health products

### Shop #3: Meat Master
- Email: meatmaster@example.com
- Password: password123
- Products: Premium meats and seafood

### Shop #4: Bakery Delight
- Email: bakerydelight@example.com
- Password: password123
- Products: Fresh baked goods and desserts

## Viewing Shop Products

To view the shops and their products in the customer site:

1. Start the PHP server:
   ```
   start_php_server.bat
   ```

2. Open your browser and go to:
   ```
   http://localhost:8000/products.php
   ```

3. You will see products from all shops with shop names displayed on each product card

4. Use the shop filter in the sidebar to view products from specific shops

## Multi-Shop Functionality Features

The multi-shop functionality includes these key features:

1. **Products Display**: Products are shown with their respective shop names
   - Each product card now displays which shop it belongs to
   - Products can be filtered by shop in the sidebar

2. **Shop-grouped Cart**: Items in the cart are grouped by shop
   - Each shop's items are displayed together
   - Subtotals are calculated per shop
   - Delivery charges are applied per shop

3. **Multi-Shop Checkout**: Checkout process handles multiple shops
   - Order summary shows items grouped by shop
   - Each shop has its own delivery charge
   - Total includes all shop subtotals and delivery charges

4. **Shop Owner Management**: Each shop owner can manage their own products
   - Shop owners can only see and modify their own products
   - Shop owners can set their shop's delivery charge

## Shop Owner Panel

To access the shop owner panel:

1. Go to:
   ```
   http://localhost:8000/shop_owner/login.html
   ```

2. Enter the email and password for any of the shops listed above

3. Manage your shop's products, view orders, and update your shop profile

## Creating New Shops

To create a new shop through the UI:

1. Go to:
   ```
   http://localhost:8000/shop_owner/register.html
   ```

2. Fill out the registration form with your shop details

3. Submit the form to create your shop

4. You will receive login credentials to access your shop owner panel

## Customer Account

For testing the multi-shop ordering system:

- Email: customer@example.com
- Password: password123

You can use this account to:
1. Browse products from all shops
2. Add products from multiple shops to your cart
3. Complete checkout with items from multiple shops
