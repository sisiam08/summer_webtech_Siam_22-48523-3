// Main JavaScript for Online Grocery Store

// Global cart function
function addToCart(productId) {
    console.log('Adding product ID to cart:', productId);
    
    // Get the base URL for the Web Application
    const currentUrl = window.location.href;
    const webAppIndex = currentUrl.indexOf('/Web Application/');
    
    let addToCartPath;
    if (webAppIndex !== -1) {
        // Extract base path up to Web Application
        const basePath = currentUrl.substring(0, webAppIndex) + '/Web Application/';
        addToCartPath = basePath + 'Users_Panel/Customer_Panel/add_to_cart.php?id=' + productId;
    } else {
        // Fallback: try different relative paths based on current location
        const currentPath = window.location.pathname;
        if (currentPath.includes('/Customer_Panel/')) {
            addToCartPath = 'add_to_cart.php?id=' + productId;
        } else if (currentPath.includes('/Users_Panel/')) {
            addToCartPath = 'Customer_Panel/add_to_cart.php?id=' + productId;
        } else {
            addToCartPath = 'Users_Panel/Customer_Panel/add_to_cart.php?id=' + productId;
        }
    }
    
    console.log('Using path:', addToCartPath);
    
    // Simple AJAX request to add item to cart
    const xhr = new XMLHttpRequest();
    xhr.open('GET', addToCartPath, true);
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
                    showAddToCartNotification(response.message);
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
    notification.textContent = productName;
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

// Handle logout and clear cart data - Global function
function handleLogoutWithCartClear(logoutUrl = '../../Authentication/logout.php', redirectUrl = '../../Authentication/login.html') {
    // Clear client-side cart data
    localStorage.removeItem('cart_data');
    localStorage.removeItem('cart_count');
    
    // Update cart display to show empty cart
    if (typeof updateCartCountDisplay === 'function') {
        updateCartCountDisplay(0);
    }
    
    // Perform logout request
    fetch(logoutUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show logout notification
            showAddToCartNotification('Logged out successfully');
            
            // Redirect after a brief delay
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        } else {
            // Fallback redirect
            window.location.href = logoutUrl;
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Fallback redirect
        window.location.href = logoutUrl;
    });
}

// Auto-attach logout handlers to all logout links
function attachLogoutHandlers() {
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const logoutUrl = this.getAttribute('href');
            handleLogoutWithCartClear(logoutUrl);
        });
    });
}

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Attach logout handlers automatically
    attachLogoutHandlers();
    
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


