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
        console.log('Adding product ID to cart:', productId);
        
        // Simple AJAX request to add item to cart
        const xhr = new XMLHttpRequest();
        // Use relative path to ensure it works from any page
        xhr.open('GET', 'api/add_to_cart.php?id=' + productId, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            console.log('Status:', this.status);
            console.log('Response text:', this.responseText);
            
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        // Save cart directly from response data
                        localStorage.setItem('cart_data', JSON.stringify(response.cart_items));
                        
                        updateCartCountDisplay(response.cart_count);
                        // Show a brief notification
                        showAddToCartNotification(response.product_name + ' added to cart');
                        
                        // Force a reload of the cart count
                        updateCartCountFromSession(response.cart_count);
                    } else {
                        console.error('Error response:', response.message);
                        showAddToCartNotification('Error adding to cart: ' + response.message);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e, this.responseText);
                    // Try to show the product was added anyway
                    updateCartCountFromDOM();
                    showAddToCartNotification('Product added to cart');
                }
            } else {
                console.error('Error adding product to cart');
                showAddToCartNotification('Error adding product to cart');
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error when adding to cart');
            showAddToCartNotification('Network error when adding to cart');
        };
        
        xhr.send();
    }
    
    // Update cart count from session
    function updateCartCountFromSession(count) {
        // Update cart count in all relevant places
        updateCartCountDisplay(count);
        
        // If we're on the cart page, try to reload it
        if (window.location.pathname.includes('cart.php')) {
            window.location.reload();
        }
    }
    
    // Update cart count from DOM if API fails
    function updateCartCountFromDOM() {
        const currentCount = document.querySelector('.cart-badge');
        if (currentCount) {
            let count = parseInt(currentCount.textContent || '0');
            count += 1;
            updateCartCountDisplay(count);
        }
    }
    
    // Function to update cart count display
    function updateCartCountDisplay(count) {
        // Update cart count badge in header
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
        
        // Update floating cart count
        const floatingCartCount = document.querySelector('.floating-cart-count');
        if (floatingCartCount) {
            floatingCartCount.textContent = count;
            floatingCartCount.style.display = count > 0 ? 'flex' : 'none';
        }
    }
    
    // Function to show a brief notification when product is added to cart
    function showAddToCartNotification(productName) {
        const notification = document.createElement('div');
        notification.className = 'add-to-cart-notification';
        notification.textContent = `${productName} added to cart`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 2000);
        }, 10);
    }

    // Form validation for login form
    // const loginForm = document.getElementById('login-form');
    // if (loginForm) {
    //     loginForm.addEventListener('submit', function(e) {
    //         const email = document.getElementById('email').value;
    //         const password = document.getElementById('password').value;
            
    //         if (!email || !password) {
    //             e.preventDefault();
    //             alert('Please fill in all fields');
    //         }
    //     });
    // }

    // Form validation for register form
    // const registerForm = document.getElementById('register-form');
    // if (registerForm) {
    //     registerForm.addEventListener('submit', function(e) {
    //         const name = document.getElementById('name').value;
    //         const email = document.getElementById('email').value;
    //         const password = document.getElementById('password').value;
    //         const confirmPassword = document.getElementById('confirm-password').value;
            
    //         if (!name || !email || !password || !confirmPassword) {
    //             e.preventDefault();
    //             alert('Please fill in all fields');
    //             return;
    //         }
            
    //         if (password !== confirmPassword) {
    //             e.preventDefault();
    //             alert('Passwords do not match');
    //         }
    //     });
    // }

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


