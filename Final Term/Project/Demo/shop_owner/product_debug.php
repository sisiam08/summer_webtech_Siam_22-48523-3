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
    <title>Product Add Debugger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2, h3 {
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
            margin-right: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .error {
            color: red;
            background-color: #ffeeee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #ffcccc;
        }
        
        .success {
            color: green;
            background-color: #eeffee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #ccffcc;
        }
        
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        
        .log-container {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
        
        .tab {
            overflow: hidden;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
            margin-bottom: 20px;
        }
        
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
            margin: 0;
        }
        
        .tab button:hover {
            background-color: #ddd;
        }
        
        .tab button.active {
            background-color: #ccc;
        }
        
        .tabcontent {
            display: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-top: none;
        }
        
        .tabcontent.active {
            display: block;
        }
    </style>
</head>
<body>
    <h1>Product Add Debugger</h1>
    
    <div class="tab">
        <button class="tablinks active" onclick="openTab(event, 'simpleTester')">Simple Tester</button>
        <button class="tablinks" onclick="openTab(event, 'databaseInfo')">Database Info</button>
        <button class="tablinks" onclick="openTab(event, 'debugLog')">Debug Log</button>
        <button class="tablinks" onclick="openTab(event, 'codeView')">Code View</button>
    </div>
    
    <div id="simpleTester" class="tabcontent active">
        <h2>Test Product Add</h2>
        <p>This form will submit directly to save_product.php and show the response.</p>
        
        <div id="message"></div>
        
        <form id="addProductForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name*</label>
                <input type="text" id="name" name="name" required value="Test Product">
            </div>
            
            <div class="form-group">
                <label for="category_id">Category ID*</label>
                <input type="number" id="category_id" name="category_id" required value="1">
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
            
            <button type="submit">Test Add Product</button>
            <button type="button" id="resetLog">Clear Response</button>
        </form>
        
        <h3>Response:</h3>
        <div class="log-container" id="responseLog"></div>
    </div>
    
    <div id="databaseInfo" class="tabcontent">
        <h2>Database Connection Test</h2>
        <button id="testDatabase">Test Database Connection</button>
        <button id="checkProductsTable">Check Products Table</button>
        <button id="testInsert">Test Insert & Delete</button>
        
        <h3>Database Info:</h3>
        <div class="log-container" id="databaseLog"></div>
    </div>
    
    <div id="debugLog" class="tabcontent">
        <h2>Debug Log</h2>
        <button id="refreshLog">Refresh Log</button>
        <button id="clearLog">Clear Log File</button>
        
        <div class="log-container" id="debugLogContent"></div>
    </div>
    
    <div id="codeView" class="tabcontent">
        <h2>save_product.php Code</h2>
        <pre id="codeContent"></pre>
    </div>
    
    <script>
        // Toggle discount percentage field
        document.getElementById('has_discount').addEventListener('change', function() {
            document.getElementById('discountGroup').style.display = this.checked ? 'block' : 'none';
        });
        
        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Form submission
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const responseLog = document.getElementById('responseLog');
            
            responseLog.innerHTML = '<p>Submitting form...</p>';
            
            fetch('save_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    responseLog.innerHTML += `<div class="success">Success: ${data.message}</div>`;
                    responseLog.innerHTML += `<p>Product ID: ${data.product_id}</p>`;
                } else {
                    responseLog.innerHTML += `<div class="error">Error: ${data.message}</div>`;
                }
                
                // Refresh the debug log
                document.getElementById('refreshLog').click();
            })
            .catch(error => {
                responseLog.innerHTML += `<div class="error">AJAX Error: ${error.message}</div>`;
            });
        });
        
        // Reset response log
        document.getElementById('resetLog').addEventListener('click', function() {
            document.getElementById('responseLog').innerHTML = '';
        });
        
        // Test database connection
        document.getElementById('testDatabase').addEventListener('click', function() {
            const databaseLog = document.getElementById('databaseLog');
            databaseLog.innerHTML = '<p>Testing database connection...</p>';
            
            fetch('debug_tools/test_db_connection.php')
            .then(response => response.text())
            .then(data => {
                databaseLog.innerHTML += data;
            })
            .catch(error => {
                databaseLog.innerHTML += `<div class="error">Error: ${error.message}</div>`;
            });
        });
        
        // Check products table
        document.getElementById('checkProductsTable').addEventListener('click', function() {
            const databaseLog = document.getElementById('databaseLog');
            databaseLog.innerHTML = '<p>Checking products table...</p>';
            
            fetch('debug_tools/check_products_table.php')
            .then(response => response.text())
            .then(data => {
                databaseLog.innerHTML += data;
            })
            .catch(error => {
                databaseLog.innerHTML += `<div class="error">Error: ${error.message}</div>`;
            });
        });
        
        // Test insert
        document.getElementById('testInsert').addEventListener('click', function() {
            const databaseLog = document.getElementById('databaseLog');
            databaseLog.innerHTML = '<p>Testing insert operation...</p>';
            
            fetch('debug_tools/test_insert.php')
            .then(response => response.text())
            .then(data => {
                databaseLog.innerHTML += data;
            })
            .catch(error => {
                databaseLog.innerHTML += `<div class="error">Error: ${error.message}</div>`;
            });
        });
        
        // Refresh debug log
        document.getElementById('refreshLog').addEventListener('click', function() {
            const debugLogContent = document.getElementById('debugLogContent');
            debugLogContent.innerHTML = '<p>Loading log...</p>';
            
            fetch('debug_tools/get_debug_log.php')
            .then(response => response.text())
            .then(data => {
                if (data.trim() === '') {
                    debugLogContent.innerHTML = '<p>No log entries found.</p>';
                } else {
                    debugLogContent.innerHTML = `<pre>${data}</pre>`;
                }
            })
            .catch(error => {
                debugLogContent.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            });
        });
        
        // Clear debug log
        document.getElementById('clearLog').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear the log file?')) {
                fetch('debug_tools/clear_debug_log.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('debugLogContent').innerHTML = '<p>Log cleared.</p>';
                })
                .catch(error => {
                    document.getElementById('debugLogContent').innerHTML = `<div class="error">Error: ${error.message}</div>`;
                });
            }
        });
        
        // Load code
        fetch('debug_tools/get_save_product_code.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('codeContent').textContent = data;
        })
        .catch(error => {
            document.getElementById('codeContent').innerHTML = `<div class="error">Error: ${error.message}</div>`;
        });
        
        // Load debug log on init
        document.getElementById('refreshLog').click();
    </script>
</body>
</html>
