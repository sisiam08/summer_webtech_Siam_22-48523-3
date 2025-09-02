# Project Reorganization Plan

## Current Issues
1. Multiple files performing the same functions
2. Files scattered across different directories
3. Inconsistent database connection approach
4. Duplicate authentication mechanisms

## Proposed Directory Structure

```
Main Project/
├── admin/              # Admin panel frontend files
├── assets/             # All static assets
│   ├── css/
│   ├── js/
│   └── images/
├── auth/               # Authentication related files
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/             # Configuration files
│   ├── database.php
│   └── constants.php
├── customer/           # Customer panel frontend files
├── database/           # Database scripts
│   ├── migrations/
│   └── seeds/
├── delivery/           # Delivery panel frontend files
├── includes/           # Shared PHP files
│   ├── functions.php
│   ├── helpers.php
│   └── session.php
├── shop_owner/         # Shop owner panel frontend files
├── uploads/            # For uploaded files
├── api/                # API endpoints for AJAX calls
│   ├── admin/
│   ├── customer/
│   ├── delivery/
│   └── shop_owner/
├── index.php           # Main entry point
└── .htaccess           # URL rewriting rules
```

## Files to Keep (Primary Files)

### Core Files
- `php/db_connection.php` → `config/database.php`
- `php/functions.php` → `includes/functions.php`
- `helpers.php` → `includes/helpers.php`

### Authentication
- `php/login_process.php` → `auth/login.php`
- `php/register_process.php` → `auth/register.php`
- `php/logout.php` → `auth/logout.php`

### Role-specific API endpoints
- All files in `php/admin/` → `api/admin/`
- All files in `php/customer/` → `api/customer/`
- All files in `php/delivery/` → `api/delivery/`
- All files in `php/shop_owner/` → `api/shop_owner/`

### Frontend Files
- Keep all HTML files in their respective directories

## Files to Remove (Duplicate/Temporary)
- `setup_admin.php` (after admin is created)
- `admin_setup_check.php` (temporary)
- `create_admin_direct.php` (temporary)
- `run_admin_setup.bat` (temporary)
- `auto_login_admin.php` (temporary)
- Any duplicate API endpoints not in the proper directories

## Implementation Steps
1. Create the new directory structure
2. Move core files to their new locations
3. Update include/require paths in all files
4. Test each panel functionality
5. Remove duplicate/temporary files
