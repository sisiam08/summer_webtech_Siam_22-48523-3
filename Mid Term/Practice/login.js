document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const messageDiv = document.getElementById('message');

    // Form validation
    function validateUsername(username) {
        if (username.trim() === '') {
            return 'Username or email is required';
        }
        if (username.length < 3) {
            return 'Username must be at least 3 characters long';
        }
        return '';
    }

    function validatePassword(password) {
        if (password.trim() === '') {
            return 'Password is required';
        }
        if (password.length < 6) {
            return 'Password must be at least 6 characters long';
        }
        return '';
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showError(element, message) {
        element.textContent = message;
        element.parentElement.querySelector('input').style.borderColor = '#e74c3c';
    }

    function clearError(element) {
        element.textContent = '';
        element.parentElement.querySelector('input').style.borderColor = '#ddd';
    }

    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Hide message after 5 seconds
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }

    // Simulate login for demo purposes when running from file://
    function simulateLogin(username, password, submitBtn, originalText) {
        // Demo credentials for file:// testing
        const demoUsers = {
            'admin': 'admin123',
            'user': 'user123',
            'demo': 'demo123',
            'admin@example.com': 'admin123',
            'user@example.com': 'user123',
            'demo@example.com': 'demo123'
        };

        setTimeout(() => {
            if (demoUsers[username] && demoUsers[username] === password) {
                showMessage('Login successful! (Demo Mode - No server needed)', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);
            } else {
                showMessage('Invalid username/email or password (Demo Mode)', 'error');
            }
            
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 1000); // Simulate network delay
    }

    // Real-time validation
    usernameInput.addEventListener('blur', function() {
        const error = validateUsername(this.value);
        if (error) {
            showError(usernameError, error);
        } else {
            clearError(usernameError);
        }
    });

    passwordInput.addEventListener('blur', function() {
        const error = validatePassword(this.value);
        if (error) {
            showError(passwordError, error);
        } else {
            clearError(passwordError);
        }
    });

    // Clear errors on input
    usernameInput.addEventListener('input', function() {
        if (usernameError.textContent) {
            clearError(usernameError);
        }
    });

    passwordInput.addEventListener('input', function() {
        if (passwordError.textContent) {
            clearError(passwordError);
        }
    });

    // Form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        clearError(usernameError);
        clearError(passwordError);

        const username = usernameInput.value;
        const password = passwordInput.value;

        // Validate all fields
        const usernameErr = validateUsername(username);
        const passwordErr = validatePassword(password);

        let hasErrors = false;

        if (usernameErr) {
            showError(usernameError, usernameErr);
            hasErrors = true;
        }

        if (passwordErr) {
            showError(passwordError, passwordErr);
            hasErrors = true;
        }

        // If no errors, submit the form
        if (!hasErrors) {
            // Show loading state
            const submitBtn = this.querySelector('.login-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Logging in...';
            submitBtn.disabled = true;

            // Check if we're running on a local server or file protocol
            if (window.location.protocol === 'file:') {
                // If running from file://, simulate login for demo purposes
                simulateLogin(username, password, submitBtn, originalText);
                return;
            }

            // Create FormData and submit via AJAX
            const formData = new FormData(this);

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Redirect after successful login
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Provide more specific error messages
                if (error.message.includes('Failed to fetch')) {
                    showMessage('Cannot connect to server. Please make sure you are running this on a web server (localhost) and not opening the file directly.', 'error');
                } else if (error.message.includes('HTTP error')) {
                    showMessage('Server error occurred. Please check if the PHP file exists and the server is running.', 'error');
                } else {
                    showMessage('An error occurred. Please try again. Error: ' + error.message, 'error');
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
    });

    // Show/Hide password functionality (optional)
    function addPasswordToggle() {
        const passwordGroup = passwordInput.parentElement;
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '👁';
        toggleBtn.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        `;
        
        passwordGroup.style.position = 'relative';
        passwordGroup.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '🙈';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '👁';
            }
        });
    }

    // Uncomment the line below to add password toggle functionality
    // addPasswordToggle();
});
