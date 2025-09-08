<?php
// Script to create sample shop owners

// Include database connection
require_once 'config/database.php';

echo "Creating sample shop owners...\n";

// Check if the shops table exists
$result = $conn->query("SHOW TABLES LIKE 'shops'");
if ($result->num_rows == 0) {
    echo "Error: The 'shops' table doesn't exist. Please run the database setup script first.\n";
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

// First, check if shop owners already exist to avoid duplicates
$existingOwners = [];
$result = $conn->query("SELECT email FROM users WHERE role = 'shop_owner'");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $existingOwners[] = $row['email'];
    }
}

// Insert shops that don't already exist
$shopsCreated = 0;
foreach ($sampleShops as $shop) {
    // Check if this shop owner already exists
    if (in_array($shop['email'], $existingOwners)) {
        echo "Shop owner with email {$shop['email']} already exists. Skipping.\n";
        continue;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Create user account for shop owner
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
        
        // Commit the transaction
        $conn->commit();
        
        echo "Created shop: {$shop['shop_name']} with owner: {$shop['owner_name']} (Email: {$shop['email']})\n";
        $shopsCreated++;
        
    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $conn->rollback();
        echo "Error creating shop {$shop['shop_name']}: " . $e->getMessage() . "\n";
    }
}

if ($shopsCreated > 0) {
    echo "\nSuccessfully created $shopsCreated shop owner accounts.\n";
    echo "\nShop Owner Login Credentials:\n";
    foreach ($sampleShops as $shop) {
        if (!in_array($shop['email'], $existingOwners)) {
            echo "Shop: {$shop['shop_name']}\n";
            echo "Email: {$shop['email']}\n";
            echo "Password: {$shop['password']}\n\n";
        }
    }
} else {
    echo "No new shop owner accounts were created.\n";
}
