// Main JavaScript for Online Grocery Store

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all add-to-cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    if (addToCartButtons) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                addToCart(productId);
            });
        });
    }

    // Function to add a product to the cart
    function addToCart(productId) {
        // Simple AJAX request to add item to cart
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `php/add_to_cart.php?id=${productId}`, true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                // Removed alert popup - silently add to cart
                // You could update a cart counter here
            } else {
                console.error('Error adding product to cart');
            }
        }
        
        xhr.send();
    }

    // Form validation for login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    }

    // Form validation for register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    }

    // Update quantity in cart
    const quantityInputs = document.querySelectorAll('.quantity-input');
    if (quantityInputs) {
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
            });
        });
    }
});
