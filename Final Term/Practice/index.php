<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Practice Examples</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            color: #4a4a4a;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        
        .card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #0066cc;
            color: white;
            padding: 15px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .card-body p {
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            background-color: #0066cc;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0052a3;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Practice Examples</h1>
        
        <div class="grid">
            <div class="card">
                <div class="card-header">Basic PHP</div>
                <div class="card-body">
                    <p>Simple "Hello World" example and basic PHP syntax.</p>
                    <a href="hello.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">HTML in PHP</div>
                <div class="card-body">
                    <p>Demonstrates how to mix HTML and PHP code together.</p>
                    <a href="html_in_php.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Variables</div>
                <div class="card-body">
                    <p>Working with variables and different data types in PHP.</p>
                    <a href="variables.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Control Structures</div>
                <div class="card-body">
                    <p>If statements, loops, switch cases and other control structures.</p>
                    <a href="control_structures.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Functions</div>
                <div class="card-body">
                    <p>Creating and using functions, parameters, return values, and scope.</p>
                    <a href="functions.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Arrays</div>
                <div class="card-body">
                    <p>Working with indexed arrays, associative arrays, and array functions.</p>
                    <a href="arrays.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Form Handling</div>
                <div class="card-body">
                    <p>Creating forms and processing form submissions with validation.</p>
                    <a href="form_validation.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Sessions & Cookies</div>
                <div class="card-body">
                    <p>Working with sessions and cookies for state management.</p>
                    <a href="sessions_and_cookies.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">File Operations</div>
                <div class="card-body">
                    <p>Reading, writing, and manipulating files and directories.</p>
                    <a href="file_operations.php" class="btn">View Example</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">MySQL Database</div>
                <div class="card-body">
                    <p>Connecting to and working with MySQL databases.</p>
                    <a href="mysql_database.php" class="btn">View Example</a>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <p>These examples cover fundamental PHP concepts for learning web development.</p>
            <p>To run these examples, make sure you have PHP and a web server installed (like XAMPP, WAMP, or built-in PHP server).</p>
            <p>To start the built-in PHP server, run this command in the terminal/command prompt:</p>
            <pre style="background: #f1f1f1; padding: 10px; border-radius: 5px; text-align: left; display: inline-block;">php -S localhost:8000</pre>
        </div>
    </div>
</body>
</html>
