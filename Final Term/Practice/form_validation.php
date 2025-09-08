<!DOCTYPE html>
<html>
<head>
    <title>PHP Form Handling</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registration Form</h1>
        
        <?php
        // Define variables and set to empty values
        $nameErr = $emailErr = $passwordErr = $confirmPasswordErr = $genderErr = "";
        $name = $email = $password = $confirmPassword = $gender = $comment = "";
        $formValid = true;
        $formSubmitted = false;
        
        // Form processing when submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $formSubmitted = true;
            
            // Validate Name
            if (empty($_POST["name"])) {
                $nameErr = "Name is required";
                $formValid = false;
            } else {
                $name = test_input($_POST["name"]);
                // Check if name only contains letters and whitespace
                if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
                    $nameErr = "Only letters and white space allowed";
                    $formValid = false;
                }
            }
            
            // Validate Email
            if (empty($_POST["email"])) {
                $emailErr = "Email is required";
                $formValid = false;
            } else {
                $email = test_input($_POST["email"]);
                // Check if email address is well-formed
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErr = "Invalid email format";
                    $formValid = false;
                }
            }
            
            // Validate Password
            if (empty($_POST["password"])) {
                $passwordErr = "Password is required";
                $formValid = false;
            } else {
                $password = test_input($_POST["password"]);
                // Check password length
                if (strlen($password) < 8) {
                    $passwordErr = "Password must be at least 8 characters";
                    $formValid = false;
                }
            }
            
            // Validate Confirm Password
            if (empty($_POST["confirmPassword"])) {
                $confirmPasswordErr = "Please confirm your password";
                $formValid = false;
            } else {
                $confirmPassword = test_input($_POST["confirmPassword"]);
                // Check if passwords match
                if ($password !== $confirmPassword) {
                    $confirmPasswordErr = "Passwords do not match";
                    $formValid = false;
                }
            }
            
            // Validate Gender
            if (empty($_POST["gender"])) {
                $genderErr = "Gender is required";
                $formValid = false;
            } else {
                $gender = test_input($_POST["gender"]);
            }
            
            // Get optional comment
            $comment = test_input($_POST["comment"]);
        }
        
        // Function to sanitize form data
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }
        ?>
        
        <?php if ($formSubmitted && $formValid): ?>
            <div class="success">
                <h3>Thank you for registering!</h3>
                <p>The following information has been submitted:</p>
                <ul>
                    <li><strong>Name:</strong> <?php echo $name; ?></li>
                    <li><strong>Email:</strong> <?php echo $email; ?></li>
                    <li><strong>Gender:</strong> <?php echo $gender; ?></li>
                    <?php if (!empty($comment)): ?>
                        <li><strong>Comment:</strong> <?php echo $comment; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <p><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">Submit another form</a></p>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $name; ?>">
                    <div class="error"><?php echo $nameErr; ?></div>
                </div>
                
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $email; ?>">
                    <div class="error"><?php echo $emailErr; ?></div>
                </div>
                
                <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password">
                    <div class="error"><?php echo $passwordErr; ?></div>
                </div>
                
                <div>
                    <label for="confirmPassword">Confirm Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword">
                    <div class="error"><?php echo $confirmPasswordErr; ?></div>
                </div>
                
                <div>
                    <label>Gender:</label>
                    <input type="radio" id="male" name="gender" value="Male" <?php if ($gender == "Male") echo "checked"; ?>>
                    <label for="male" style="display: inline;">Male</label>
                    <input type="radio" id="female" name="gender" value="Female" <?php if ($gender == "Female") echo "checked"; ?>>
                    <label for="female" style="display: inline;">Female</label>
                    <input type="radio" id="other" name="gender" value="Other" <?php if ($gender == "Other") echo "checked"; ?>>
                    <label for="other" style="display: inline;">Other</label>
                    <div class="error"><?php echo $genderErr; ?></div>
                </div>
                
                <div>
                    <label for="comment">Comment (Optional):</label>
                    <textarea id="comment" name="comment" rows="5"><?php echo $comment; ?></textarea>
                </div>
                
                <div>
                    <input type="submit" value="Submit">
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
