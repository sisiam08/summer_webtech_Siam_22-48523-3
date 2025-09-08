<?php
// Application constants

// Site information
define('SITE_NAME', 'Online Grocery Store');
define('SITE_VERSION', '1.0.0');

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_SHOP_OWNER', 'shop_owner');
define('ROLE_DELIVERY', 'delivery');
define('ROLE_CUSTOMER', 'customer');

// Order statuses
define('ORDER_PENDING', 'pending');
define('ORDER_PROCESSING', 'processing');
define('ORDER_SHIPPED', 'shipped');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELLED', 'cancelled');
?>