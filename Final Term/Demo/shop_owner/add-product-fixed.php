<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product (Fixed Version)</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/shop_owner.css">
    <style>
        .product-form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .checkbox-group {
            margin-top: 10px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .discount-fields {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }
        
        .image-preview {
            margin-top: 10px;
            border: 1px dashed #ddd;
            padding: 20px;
            text-align: center;
            color: #888;
            border-radius: 4px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #response-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }
        
        #response-container pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Product (Fixed Version)</h1>
        
        <form id="product-form" class="product-form">
            <div class="form-group">
                <label for="product-name">Product Name</label>
                <input type="text" id="product-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="product-category">Category</label>
                <select id="product-category" name="category_id" required>
                    <?php
                    // Connect to database
                    $host = 'localhost';
                    $user = 'root';
                    $pass = 'Siam@MySQL2025';
                    $db = 'grocery_store';
                    
                    $conn = new mysqli($host, $user, $pass, $db);
                    
                    if ($conn->connect_error) {
                        echo "<option value=''>Database connection failed</option>";
                    } else {
                        // Get categories
                        $query = "SELECT id, name FROM categories ORDER BY name";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($category = $result->fetch_assoc()) {
                                echo "<option value='{$category['id']}'>{$category['name']}</option>";
                            }
                        } else {
                            echo "<option value='1'>Default Category</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="product-price">Price</label>
                <input type="number" id="product-price" name="price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="product-stock">Stock</label>
                <input type="number" id="product-stock" name="stock" min="0" required>
            </div>
            <div class="form-group">
                <label for="product-unit">Unit</label>
                <select id="product-unit" name="unit" required>
                    <option value="piece">Piece</option>
                    <option value="kg">Kilogram</option>
                    <option value="g">Gram</option>
                    <option value="lb">Pound</option>
                    <option value="oz">Ounce</option>
                    <option value="l">Liter</option>
                    <option value="ml">Milliliter</option>
                    <option value="dozen">Dozen</option>
                    <option value="box">Box</option>
                    <option value="pack">Pack</option>
                    <option value="bunch">Bunch</option>
                </select>
            </div>
            <div class="form-group">
                <label for="product-description">Description</label>
                <textarea id="product-description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="product-image">Product Image</label>
                <input type="file" id="product-image" name="image" accept="image/*">
                <div id="image-preview" class="image-preview">Image Preview</div>
            </div>
            <div class="form-group">
                <label for="product-status">Status</label>
                <select id="product-status" name="is_active" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="product-featured" name="is_featured" value="1">
                        Feature this product on homepage
                    </label>
                </div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="product-discount" name="has_discount" value="1">
                        Apply discount
                    </label>
                </div>
                
                <div id="discount-fields" class="discount-fields">
                    <div class="form-group">
                        <label for="discount-percent">Discount Percentage (%)</label>
                        <input type="number" id="discount-percent" name="discount_percent" min="1" max="99" value="10">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="products.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
        
        <div id="response-container">
            <h3>Response:</h3>
            <pre id="response-content"></pre>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview
            const imageInput = document.getElementById('product-image');
            const imagePreview = document.getElementById('image-preview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%;">`;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imagePreview.innerHTML = 'Image Preview';
                }
            });
            
            // Discount fields toggle
            const discountCheckbox = document.getElementById('product-discount');
            const discountFields = document.getElementById('discount-fields');
            
            discountCheckbox.addEventListener('change', function() {
                discountFields.style.display = this.checked ? 'block' : 'none';
            });
            
            // Form submission with enhanced error handling
            const productForm = document.getElementById('product-form');
            const responseContainer = document.getElementById('response-container');
            const responseContent = document.getElementById('response-content');
            
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show response container
                responseContainer.style.display = 'block';
                responseContent.textContent = 'Submitting form...';
                
                const formData = new FormData(this);
                
                // Add dummy shop_id if not in session
                formData.append('shop_id', '1');
                
                fetch('save_product_fixed.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    responseContent.textContent += `\nResponse status: ${response.status}`;
                    return response.text();
                })
                .then(text => {
                    try {
                        responseContent.textContent += `\n\nRaw response:\n${text}`;
                        
                        const data = JSON.parse(text);
                        responseContent.textContent += `\n\nParsed JSON response:\n${JSON.stringify(data, null, 2)}`;
                        
                        if (data.success) {
                            alert('Product added successfully!');
                            // Uncomment to redirect after success
                            // window.location.href = 'products.php';
                        } else {
                            alert('Error: ' + (data.message || 'Failed to add product'));
                        }
                    } catch (e) {
                        responseContent.textContent += `\n\nError parsing JSON: ${e.message}`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    responseContent.textContent += `\n\nFetch error: ${error.message}`;
                    alert('An error occurred while adding the product. Please check the response.');
                });
            });
        });
    </script>
</body>
</html>
