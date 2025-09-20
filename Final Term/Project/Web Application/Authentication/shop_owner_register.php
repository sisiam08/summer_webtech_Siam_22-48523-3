<?php
require_once __DIR__ . '/../Includes/settings_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owner Registration - Nitto Proyojon</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../Includes/style.css">
    <link rel="stylesheet" href="../Users_Panel/Shop_Owner_Panel/shop_owner.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form" style="max-width: 600px;">
            <div class="login-header">
                <h2>Shop Owner Registration</h2>
                <p>Apply to become a vendor on our platform</p>
            </div>
            
            <form id="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shop_name">Shop Name</label>
                        <input type="text" id="shop_name" name="shop_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Shop Address</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Shop Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="../Includes/terms.html" target="_blank">Terms and Conditions</a></label>
                    </div>
                </div>
                
                <div id="error-message" class="error-message"></div>
                
                <div class="form-group">
                    <button type="submit" class="btn primary">Submit Application</button>
                </div>
            </form>
            
            <div class="login-footer">
                <p>Already have a shop owner account? <a href="login.html">Login</a></p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y') . " Nitto Proyojon"; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('register-form');
            const errorMessage = document.getElementById('error-message');
            
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear previous error messages
                errorMessage.textContent = '';
                
                // Get form values
                const name = document.getElementById('name').value.trim();
                const shopName = document.getElementById('shop_name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const address = document.getElementById('address').value.trim();
                const city = document.getElementById('city').value.trim();
                const description = document.getElementById('description').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                // Basic validation
                if (!document.getElementById('terms').checked) {
                    errorMessage.textContent = 'You must agree to the Terms and Conditions';
                    return;
                }
                
                if (password !== confirmPassword) {
                    errorMessage.textContent = 'Passwords do not match';
                    return;
                }
                
                if (password.length < 6) {
                    errorMessage.textContent = 'Password must be at least 6 characters long';
                    return;
                }
                
                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    errorMessage.textContent = 'Please enter a valid email address';
                    return;
                }
                
                // Show loading state
                const submitBtn = document.querySelector('.btn.primary');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Submitting...';
                submitBtn.disabled = true;
                
                // Send registration request
                fetch('shop_owner_register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name,
                        shop_name: shopName,
                        email: email,
                        phone: phone,
                        address: address,
                        city: city,
                        description: description,
                        password: password
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and redirect to login
                        alert(data.message || 'Registration successful! Please wait for admin approval.');
                        window.location.href = 'login.html';
                    } else {
                        // Display error message
                        errorMessage.textContent = data.message || 'An error occurred during registration';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred. Please try again later.';
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>