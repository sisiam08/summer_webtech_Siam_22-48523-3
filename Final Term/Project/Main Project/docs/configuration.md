# Configuration System

This document explains how to use the centralized configuration system in the application.

## Overview

The configuration system provides a centralized way to manage application settings across the entire codebase. This improves maintainability and makes it easier to update settings in one place.

## Files

The configuration system consists of the following files:

1. `/config/config.php` - Contains all configuration values
2. `/includes/global_functions.php` - Contains helper functions to access configuration values

## How to Use

### Accessing Configuration Values

You can access configuration values using the `config()` function, which uses dot notation to access nested values:

```php
// Get the site name
$siteName = config('site.name');

// Get the database connection settings
$dbHost = config('database.host');
$dbUser = config('database.user');

// Get a value with a default if not found
$defaultCharge = config('delivery.default_charge', 60);
```

### Available Helper Functions

The system also provides many helper functions to access common configuration values:

```php
// Get the site name
$siteName = getSiteName();

// Format a page title
$pageTitle = formatTitle('Products');

// Format a price with currency symbol
$formattedPrice = formatPrice(99.99);

// Get site URL with optional path
$url = siteUrl('products.php');

// Format dates and times
$formattedDate = formatDate('2025-09-05');
$formattedTime = formatTime('14:30:00');
$formattedDateTime = formatDateTime('2025-09-05 14:30:00');

// Check user roles
if (isAdmin()) {
    // Admin-only code
}

if (isShopOwner()) {
    // Shop owner-only code
}

// Get payment methods
$paymentMethods = getPaymentMethods();
```

## Adding New Configuration Values

To add new configuration values:

1. Open `/config/config.php`
2. Add your new values to the appropriate section or create a new section
3. If needed, add helper functions in `/includes/global_functions.php`

## Example

```php
// In your PHP file:
require_once 'includes/functions.php';  // This loads the configuration system

// Use configuration values
echo getSiteName();  // Outputs: Online Grocery Store

// Format a price
echo formatPrice(1250);  // Outputs: ৳ 1,250.00

// Check minimum order amount
$minOrder = getMinOrderAmount();
if ($totalOrder < $minOrder) {
    echo "Minimum order amount is " . formatPrice($minOrder);
}
```

## Updating Configuration Values

To update any setting in the application, simply modify the values in `/config/config.php`. The changes will apply across the entire application.

## Benefits

- Centralized configuration
- Consistent values across the application
- Easy to update settings in one place
- Type-safe access to configuration values
- Default values for safety
- Helper functions for common operations
