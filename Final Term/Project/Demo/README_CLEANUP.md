# Project Cleanup Guide

This README file provides information about the main files in the project and which files can be safely removed.

## Main Files in Each Section

### Shop Owner Section

- **add_product.php** - API endpoint for adding products
- **add-product-updated.php** - UI form for adding products
- **products.php** - Main products listing page
- **edit-product.php** - UI form for editing products
- **save_product.php** - API endpoint for saving product data
- **get_products.php** - API endpoint for getting products list
- **get_product.php** - API endpoint for getting a single product
- **delete_product.php** - API endpoint for deleting a product
- **orders.php** - Orders management page
- **profile.php** - Shop profile management page

### Authentication

- **login.php** - Main login page and processing
- **auth/login_process.php** - API endpoint for AJAX login
- **logout.php** - Logout functionality

### User Management

- **api/admin/get_user.php** - Get single user data API
- **api/admin/get_users.php** - Get multiple users API
- **api/admin/add_user.php** - Add user API
- **api/admin/update_user.php** - Update user API
- **api/admin/delete_user.php** - Delete user API

## Files That Can Be Removed

Files with the following naming patterns can be safely removed:

1. Files with "test", "debug", "fixed", "new" in their names:
   - *-debug.php
   - *_debug.php
   - *-test.php
   - *_test.php
   - *-fixed.php
   - *_fixed.php
   - *-new.php
   - *_new.php

2. Files that start with "test_", "debug_", "fix_", etc.

3. Duplicate API endpoints with slightly different names:
   - add-products.php (use add_product.php instead)
   - add_products_new.php (use add_product.php instead)
   - add-test-products.php (use add_product.php instead)

## Cleanup Script

A cleanup script has been created to move all duplicate/debug files to a backup folder.
Run the following script to move these files:

```
cleanup_duplicate_files.bat
```

This will move all test/debug files to a backup folder without deleting them.
After verifying everything works correctly, you can safely delete the backup folder.

## Best Practices Going Forward

1. **Use Consistent Naming**: Stick to a single naming convention (either snake_case or kebab-case)
2. **Don't Create Duplicate Files**: Instead, use version control (Git) for tracking changes
3. **Use Comments**: Add comments in the code instead of creating debug versions
4. **Use Feature Branches**: If using Git, create feature branches for new features
5. **Document Your Code**: Add proper documentation to make it clear what each file does

By following these practices, you can avoid accumulating duplicate files in the future.
