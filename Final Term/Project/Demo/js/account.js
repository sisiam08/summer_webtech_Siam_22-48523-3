// Account page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    fetch('php/check_login.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                // Redirect to login page if not logged in
                window.location.href = 'login.html';
            } else {
                // Load user data
                loadUserData();
                // Load addresses
                loadAddresses();
                // Load order history
                loadOrders();
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            showMessage('Error checking login status. Please refresh the page.', 'error');
        });
    
    // Handle section navigation
    const navLinks = document.querySelectorAll('.account-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(navLink => navLink.classList.remove('active'));
            document.querySelectorAll('.account-section').forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show the corresponding section
            const sectionId = this.getAttribute('data-section');
            document.getElementById(sectionId).classList.add('active');
        });
    });
    
    // Handle profile form submission
    const profileForm = document.getElementById('profile-form');
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('php/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                // Update displayed data if needed
                if (data.user) {
                    document.getElementById('name').value = data.user.name || '';
                    document.getElementById('email').value = data.user.email || '';
                    document.getElementById('phone').value = data.user.phone || '';
                }
                
                // Clear password fields
                document.getElementById('current-password').value = '';
                document.getElementById('new-password').value = '';
                document.getElementById('confirm-password').value = '';
            } else {
                showMessage(data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            showMessage('Error updating profile. Please try again.', 'error');
        });
    });
    
    // Handle address form submission
    const addressForm = document.getElementById('address-form');
    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('php/update_address.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                // Reload addresses
                loadAddresses();
                // Reset form
                addressForm.reset();
            } else {
                showMessage(data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding address:', error);
            showMessage('Error adding address. Please try again.', 'error');
        });
    });
    
    // Handle logout
    document.getElementById('logout-link').addEventListener('click', function(e) {
        e.preventDefault();
        
        fetch('php/logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.html';
                }
            })
            .catch(error => {
                console.error('Error logging out:', error);
            });
    });
});

// Function to load user data
function loadUserData() {
    fetch('php/get_user_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate form fields
                document.getElementById('name').value = data.user.name || '';
                document.getElementById('email').value = data.user.email || '';
                document.getElementById('phone').value = data.user.phone || '';
            } else {
                showMessage(data.message || 'Failed to load user data', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            showMessage('Error loading user data. Please refresh the page.', 'error');
        });
}

// Function to load addresses
function loadAddresses() {
    fetch('php/get_addresses.php')
        .then(response => response.json())
        .then(data => {
            const addressesList = document.getElementById('addresses-list');
            
            if (data.success && data.addresses.length > 0) {
                // Create HTML for each address
                let html = '';
                
                data.addresses.forEach(address => {
                    html += `
                        <div class="address-card">
                            <h4>
                                ${address.label}
                                ${address.is_default ? '<span class="default-badge">Default</span>' : ''}
                            </h4>
                            <div class="address-actions">
                                ${!address.is_default ? `<button type="button" class="default-btn" data-id="${address.id}">Set Default</button>` : ''}
                                <button type="button" class="delete-btn" data-id="${address.id}">Delete</button>
                            </div>
                            <p>${address.line1}</p>
                            <p>${address.area}, ${address.city}, ${address.postal_code}</p>
                            <p>Phone: ${address.phone}</p>
                        </div>
                    `;
                });
                
                addressesList.innerHTML = html;
                
                // Add event listeners to address action buttons
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteAddress(this.getAttribute('data-id'));
                    });
                });
                
                document.querySelectorAll('.default-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        setDefaultAddress(this.getAttribute('data-id'));
                    });
                });
                
            } else {
                addressesList.innerHTML = '<p>No addresses found. Add a new address below.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading addresses:', error);
            document.getElementById('addresses-list').innerHTML = '<p>Error loading addresses. Please refresh the page.</p>';
        });
}

// Function to load order history
function loadOrders() {
    fetch('php/get_orders.php')
        .then(response => response.json())
        .then(data => {
            const ordersList = document.getElementById('orders-list');
            
            if (data.success && data.orders.length > 0) {
                // Create HTML for each order
                let html = '';
                
                data.orders.forEach(order => {
                    // Format date
                    const orderDate = new Date(order.created_at);
                    const formattedDate = orderDate.toLocaleDateString() + ' ' + orderDate.toLocaleTimeString();
                    
                    // Status class
                    const statusClass = 'status-' + order.status.toLowerCase();
                    
                    html += `
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-number">${order.order_number}</span>
                                <span class="order-date">${formattedDate}</span>
                            </div>
                            <div class="order-status ${statusClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</div>
                            <div class="order-items">Items: ${order.item_count}</div>
                            <div class="order-total">Total: $${parseFloat(order.total_amount).toFixed(2)}</div>
                            <div class="order-actions">
                                <a href="order_details.html?id=${order.id}" class="order-details-btn">View Details</a>
                            </div>
                        </div>
                    `;
                });
                
                ordersList.innerHTML = html;
                
            } else {
                ordersList.innerHTML = '<p>No orders found. Start shopping to see your order history here.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            document.getElementById('orders-list').innerHTML = '<p>Error loading order history. Please refresh the page.</p>';
        });
}

// Function to delete address
function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_address');
    formData.append('address_id', addressId);
    
    fetch('php/update_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Reload addresses
            loadAddresses();
        } else {
            showMessage(data.message || 'Failed to delete address', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting address:', error);
        showMessage('Error deleting address. Please try again.', 'error');
    });
}

// Function to set default address
function setDefaultAddress(addressId) {
    const formData = new FormData();
    formData.append('action', 'set_default_address');
    formData.append('address_id', addressId);
    
    fetch('php/update_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Reload addresses
            loadAddresses();
        } else {
            showMessage(data.message || 'Failed to set default address', 'error');
        }
    })
    .catch(error => {
        console.error('Error setting default address:', error);
        showMessage('Error setting default address. Please try again.', 'error');
    });
}

// Function to show message
function showMessage(message, type) {
    const messageContainer = document.getElementById('message-container');
    const messageClass = type === 'success' ? 'success-message' : 'error-message';
    
    messageContainer.innerHTML = `<div class="message ${messageClass}">${message}</div>`;
    
    // Auto-hide message after 5 seconds
    setTimeout(() => {
        messageContainer.innerHTML = '';
    }, 5000);
}
