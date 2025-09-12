<?php
// Script to create sample shops and their owners in the database
require_once '../config/database.php';

echo "Creating sample shops and shop owners...\n";

// Make sure the database is properly set up for multi-shop functionality
$checkShopsTable = $conn->query("SHOW TABLES LIKE 'shops'");
if ($checkShopsTable->num_rows == 0) {
    echo "Error: The 'shops' table doesn't exist. Please run update_multi_shop_db.bat first.\n";
    exit;
}

// Array of sample shops
$sampleShops = [
    [
        'owner_name' => 'John Smith',
        'email' => 'freshfarms@example.com',
        'password' => 'password123',
        'shop_name' => 'Fresh Farms',
        'description' => 'Offering the freshest fruits and vegetables directly from local farms.',
        'delivery_charge' => 4.99,
        'minimum_order' => 10.00,
        'address' => '123 Farm Road, Farmville, CA 90210',
        'phone' => '555-123-4567'
    ],
    [
        'owner_name' => 'Maria Garcia',
        'email' => 'organicworld@example.com',
        'password' => 'password123',
        'shop_name' => 'Organic World',
        'description' => 'Certified organic produce and health products for conscious consumers.',
        'delivery_charge' => 3.50,
        'minimum_order' => 15.00,
        'address' => '456 Green Ave, Ecotown, CA 90211',
        'phone' => '555-987-6543'
    ],
    [
        'owner_name' => 'David Johnson',
        'email' => 'meatmaster@example.com',
        'password' => 'password123',
        'shop_name' => 'Meat Master',
        'description' => 'Premium quality meats, poultry and seafood for meat lovers.',
        'delivery_charge' => 5.99,
        'minimum_order' => 20.00,
        'address' => '789 Butcher St, Carnivore, CA 90212',
        'phone' => '555-456-7890'
    ],
    [
        'owner_name' => 'Sarah Williams',
        'email' => 'bakerydelight@example.com',
        'password' => 'password123',
        'shop_name' => 'Bakery Delight',
        'description' => 'Freshly baked bread, pastries, and desserts made from scratch daily.',
        'delivery_charge' => 2.99,
        'minimum_order' => 8.00,
        'address' => '101 Baker Lane, Sweetville, CA 90213',
        'phone' => '555-234-5678'
    ]
];

// Insert the sample data
foreach ($sampleShops as $shop) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Create user account for shop owner (in users table)
        $hashedPassword = password_hash($shop['password'], PASSWORD_DEFAULT);
        $role = 'shop_owner';
        
        $userSql = "INSERT INTO users (name, email, password, role, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param('ssss', 
            $shop['owner_name'], 
            $shop['email'], 
            $hashedPassword, 
            $role
        );
        $userStmt->execute();
        
        // Get the new user ID
        $ownerId = $conn->insert_id;
        
        // 2. Create shop record
        $shopSql = "INSERT INTO shops (name, owner_id, description, delivery_charge, minimum_order, address, phone, email, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $shopStmt = $conn->prepare($shopSql);
        $shopStmt->bind_param('sisddsss', 
            $shop['shop_name'], 
            $ownerId, 
            $shop['description'], 
            $shop['delivery_charge'], 
            $shop['minimum_order'], 
            $shop['address'], 
            $shop['phone'], 
            $shop['email']
        );
        $shopStmt->execute();
        
        // Get the new shop ID
        $shopId = $conn->insert_id;
        
        // 3. Create sample products for this shop
        createSampleProducts($conn, $shopId, $shop['shop_name']);
        
        // Commit the transaction
        $conn->commit();
        
        echo "Created shop: {$shop['shop_name']} with owner: {$shop['owner_name']} (Email: {$shop['email']})\n";
        
    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $conn->rollback();
        echo "Error creating shop {$shop['shop_name']}: " . $e->getMessage() . "\n";
    }
}

echo "\nSample shops and owners created successfully!\n";
echo "\nShop Owner Login Credentials:\n";
foreach ($sampleShops as $shop) {
    echo "Shop: {$shop['shop_name']}\n";
    echo "Email: {$shop['email']}\n";
    echo "Password: {$shop['password']}\n\n";
}

/**
 * Creates sample products for a shop
 * 
 * @param mysqli $conn Database connection
 * @param int $shopId Shop ID
 * @param string $shopName Shop name
 */
function createSampleProducts($conn, $shopId, $shopName) {
    // Sample product categories
    $categories = [1, 2, 3, 4]; // Assuming these category IDs exist
    
    // Generic product data customized for each shop
    $productData = [];
    
    if ($shopName == 'Fresh Farms') {
        $productData = [
            ['Fresh Apples', 'Crisp, juicy apples freshly picked from our orchards', 2.99, 1, '../images/products/apples.jpg'],
            ['Organic Bananas', 'Sweet and nutritious organic bananas', 1.99, 1, '../images/products/bananas.jpg'],
            ['Red Grapes', 'Plump and sweet red grapes', 3.49, 1, '../images/products/grapes.jpg'],
            ['Fresh Spinach', 'Nutrient-rich spinach leaves', 2.49, 2, '../images/products/spinach.jpg'],
            ['Carrots', 'Fresh, crunchy carrots', 1.79, 2, '../images/products/carrots.jpg']
        ];
    } elseif ($shopName == 'Organic World') {
        $productData = [
            ['Organic Kale', 'Locally grown organic kale', 3.99, 2, '../images/products/kale.jpg'],
            ['Organic Tomatoes', 'Juicy vine-ripened organic tomatoes', 2.99, 2, '../images/products/tomatoes.jpg'],
            ['Organic Avocados', 'Creamy, ripe organic avocados', 4.99, 1, '../images/products/avocados.jpg'],
            ['Organic Quinoa', 'Premium organic quinoa', 6.99, 3, '../images/products/quinoa.jpg'],
            ['Organic Honey', 'Raw, unfiltered organic honey', 8.99, 3, '../images/products/honey.jpg']
        ];
    } elseif ($shopName == 'Meat Master') {
        $productData = [
            ['Premium Ribeye Steak', 'Tender, marbled ribeye steak', 15.99, 4, '../images/products/ribeye.jpg'],
            ['Free-Range Chicken', 'Whole free-range chicken', 9.99, 4, '../images/products/chicken.jpg'],
            ['Fresh Salmon Fillet', 'Wild-caught salmon fillet', 12.99, 4, '../images/products/salmon.jpg'],
            ['Ground Beef', 'Lean ground beef', 7.99, 4, '../images/products/groundbeef.jpg'],
            ['Smoked Bacon', 'Hickory-smoked bacon strips', 6.99, 4, '../images/products/bacon.jpg']
        ];
    } elseif ($shopName == 'Bakery Delight') {
        $productData = [
            ['Sourdough Bread', 'Artisanal sourdough bread baked daily', 4.99, 3, '../images/products/sourdough.jpg'],
            ['Chocolate Croissants', 'Buttery chocolate-filled croissants (4 pack)', 7.99, 3, '../images/products/croissants.jpg'],
            ['Blueberry Muffins', 'Fresh-baked blueberry muffins (6 pack)', 8.99, 3, '../images/products/muffins.jpg'],
            ['Cinnamon Rolls', 'Gooey cinnamon rolls with cream cheese frosting (4 pack)', 9.99, 3, '../images/products/cinnamonrolls.jpg'],
            ['Artisan Cookies', 'Assorted artisan cookies (12 pack)', 10.99, 3, '../images/products/cookies.jpg']
        ];
    }
    
    // Insert products for this shop
    foreach ($productData as $product) {
        $productSql = "INSERT INTO products (name, description, price, category_id, image, shop_id, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $productStmt = $conn->prepare($productSql);
        $productStmt->bind_param('ssdiis', 
            $product[0], // name
            $product[1], // description
            $product[2], // price
            $product[3], // category_id
            $product[4], // image
            $shopId
        );
        $productStmt->execute();
    }
}