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
    <title>jQuery Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .result {
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
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
    </style>
</head>
<body>
    <h1>jQuery Test</h1>
    
    <div id="result"></div>
    
    <button id="test-jquery">Test jQuery</button>
    <button id="test-fetch">Test Fetch API</button>
    <button id="test-ajax">Test jQuery AJAX</button>
    
    <h2>Save Product File Contents</h2>
    <pre id="file-contents">Loading...</pre>
    
    <script>
        // Test jQuery
        document.getElementById('test-jquery').addEventListener('click', function() {
            const resultDiv = document.getElementById('result');
            
            try {
                if (typeof jQuery !== 'undefined') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '<strong>Success!</strong> jQuery is loaded (version ' + jQuery.fn.jquery + ')';
                    
                    // Test jQuery functionality
                    $('#result').append('<p>This text was added using jQuery.</p>');
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '<strong>Error!</strong> jQuery is not loaded';
                }
            } catch (e) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<strong>Error!</strong> ' + e.message;
            }
        });
        
        // Test Fetch API
        document.getElementById('test-fetch').addEventListener('click', function() {
            const resultDiv = document.getElementById('result');
            resultDiv.className = 'result';
            resultDiv.innerHTML = '<strong>Testing Fetch API...</strong>';
            
            fetch('save_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ test: true })
            })
            .then(response => {
                resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
                return response.text();
            })
            .then(data => {
                resultDiv.innerHTML += '<p>Response data:</p><pre>' + data + '</pre>';
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML += '<p>Error: ' + error.message + '</p>';
                resultDiv.className = 'result error';
            });
        });
        
        // Test jQuery AJAX
        document.getElementById('test-ajax').addEventListener('click', function() {
            const resultDiv = document.getElementById('result');
            resultDiv.className = 'result';
            resultDiv.innerHTML = '<strong>Testing jQuery AJAX...</strong>';
            
            $.ajax({
                url: 'save_product.php',
                type: 'POST',
                data: JSON.stringify({ test: true }),
                contentType: 'application/json',
                success: function(data) {
                    resultDiv.innerHTML += '<p>Success! Response data:</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    resultDiv.className = 'result success';
                },
                error: function(xhr, status, error) {
                    resultDiv.innerHTML += '<p>Error: ' + error + '</p>';
                    resultDiv.innerHTML += '<p>Response text:</p><pre>' + xhr.responseText + '</pre>';
                    resultDiv.className = 'result error';
                }
            });
        });
        
        // Load the contents of save_product.php
        fetch('save_product.php?view=1')
            .then(response => response.text())
            .then(data => {
                document.getElementById('file-contents').textContent = data;
            })
            .catch(error => {
                document.getElementById('file-contents').textContent = 'Error loading file: ' + error.message;
            });
    </script>
</body>
</html>
