<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers
header("Content-Type: text/html; charset=UTF-8");

// Debug file
$debugFile = __DIR__ . '/direct_debug.log';

// Function to log debug info
function debug_log($message) {
    global $debugFile;
    file_put_contents($debugFile, date('Y-m-d H:i:s') . ": $message\n", FILE_APPEND);
}

debug_log("Script started");

// Database connection
$host = '127.0.0.1';
$username = 'root';
$password = 'Siam@MySQL2025';
$database = 'grocery_store';
$port = 3306;

// Track if form was submitted
$formSubmitted = false;
$success = false;
$errorMessage = '';
$productId = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    debug_log("Form submitted");
    
    try {
        // Create connection
        $conn = new mysqli($host, $username, $password, $database, $port);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        debug_log("Database connection successful");
        
        // Get form data
        $shopId = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 1;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $unit = isset($_POST['unit']) ? trim($_POST['unit']) : 'piece';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $hasDiscount = isset($_POST['has_discount']) ? 1 : 0;
        $discountPercent = $hasDiscount ? (isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0) : 0;
        
        debug_log("Form data received: " . json_encode([
            'shop_id' => $shopId,
            'name' => $name,
            'category_id' => $categoryId,
            'price' => $price,
            'stock' => $stock,
            'unit' => $unit,
            'is_active' => $isActive,
            'is_featured' => $isFeatured,
            'has_discount' => $hasDiscount,
            'discount_percent' => $discountPercent
        ]));
        
        // Validate required fields
        if (empty($name) || empty($categoryId) || $price <= 0 || $stock < 0 || empty($unit)) {
            throw new Exception('Please fill all required fields with valid values');
        }
        
        // Calculate discounted price
        $discountedPrice = $hasDiscount ? ($price * (1 - $discountPercent / 100)) : null;
        debug_log("Price: $price, Discounted price: " . ($discountedPrice ?? 'NULL'));
        
        // Start transaction
        $conn->begin_transaction();
        debug_log("Transaction started");
        
        // Check if the products table exists
        $tableResult = $conn->query("SHOW TABLES LIKE 'products'");
        if ($tableResult->num_rows == 0) {
            debug_log("Products table does not exist");
            throw new Exception('Products table does not exist in the database');
        }
        
        // Insert product into database
        $stmt = $conn->prepare("
            INSERT INTO products (
                shop_id, name, category_id, price, discounted_price, stock, unit, 
                description, image, is_active, is_featured, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            debug_log("Prepare statement failed: " . $conn->error);
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        
        debug_log("Prepare statement successful");
        
        // Default image
        $imageName = 'default.jpg';
        
        // Bind parameters - use the correct types
        // i - integer, s - string, d - double/decimal
        $bindResult = $stmt->bind_param(
            'isiddiissii',
            $shopId, $name, $categoryId, $price, $discountedPrice, $stock, $unit, 
            $description, $imageName, $isActive, $isFeatured
        );
        
        if (!$bindResult) {
            debug_log("Parameter binding failed: " . $stmt->error);
            throw new Exception('Parameter binding failed: ' . $stmt->error);
        }
        
        debug_log("Parameter binding successful");
        debug_log("About to execute query");
        
        if (!$stmt->execute()) {
            debug_log("Execute failed: " . $stmt->error);
            throw new Exception('Failed to add product: ' . $stmt->error);
        }
        
        $productId = $conn->insert_id;
        debug_log("Product added successfully with ID: $productId");
        
        // Commit transaction
        $conn->commit();
        debug_log("Transaction committed");
        
        // Success!
        $success = true;
        
    } catch (Exception $e) {
        debug_log("Error: " . $e->getMessage());
        $errorMessage = $e->getMessage();
        
        // Rollback transaction if one is active
        if (isset($conn) && $conn->connect_errno === 0) {
            $conn->rollback();
            debug_log("Transaction rolled back");
        }
    } finally {
        // Close connection
        if (isset($conn)) {
            $conn->close();
            debug_log("Database connection closed");
        }
    }
}

// Get categories for dropdown
$categories = [];
try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database, $port);
    
    // Check connection
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT * FROM categories ORDER BY name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        $conn->close();
    }
} catch (Exception $e) {
    // Do nothing, we'll just have an empty categories array
}

debug_log("Script ended");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Product Add</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2 {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .error {
            color: white;
            background-color: #f44336;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success {
            color: white;
            background-color: #4CAF50;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>Direct Product Add</h1>
    
    <?php if ($formSubmitted): ?>
        <?php if ($success): ?>
            <div class="success">
                <p>Product added successfully!</p>
                <p>Product ID: <?php echo $productId; ?></p>
            </div>
        <?php else: ?>
            <div class="error">
                <p>Error: <?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="shop_id">Shop ID*</label>
            <input type="number" id="shop_id" name="shop_id" required value="1">
        </div>
        
        <div class="form-group">
            <label for="name">Product Name*</label>
            <input type="text" id="name" name="name" required value="Test Product">
        </div>
        
        <div class="form-group">
            <label for="category_id">Category*</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php if (empty($categories)): ?>
                    <option value="1">Default Category (ID: 1)</option>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="price">Price*</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required value="9.99">
        </div>
        
        <div class="form-group">
            <label for="has_discount">Has Discount</label>
            <input type="checkbox" id="has_discount" name="has_discount">
        </div>
        
        <div class="form-group" id="discountGroup" style="display: none;">
            <label for="discount_percent">Discount Percentage</label>
            <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" step="0.01" value="10">
        </div>
        
        <div class="form-group">
            <label for="stock">Stock*</label>
            <input type="number" id="stock" name="stock" min="0" required value="10">
        </div>
        
        <div class="form-group">
            <label for="unit">Unit*</label>
            <input type="text" id="unit" name="unit" required value="piece">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">This is a test product</textarea>
        </div>
        
        <div class="form-group">
            <label for="is_active">Active</label>
            <input type="checkbox" id="is_active" name="is_active" checked>
        </div>
        
        <div class="form-group">
            <label for="is_featured">Featured</label>
            <input type="checkbox" id="is_featured" name="is_featured">
        </div>
        
        <button type="submit">Add Product</button>
    </form>
    
    <h2>Debug Log</h2>
    <?php if (file_exists($debugFile)): ?>
        <pre><?php echo htmlspecialchars(file_get_contents($debugFile)); ?></pre>
    <?php else: ?>
        <p>No debug log available yet.</p>
    <?php endif; ?>
    
    <script>
        // Toggle discount percentage field
        document.getElementById('has_discount').addEventListener('change', function() {
            document.getElementById('discountGroup').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>
