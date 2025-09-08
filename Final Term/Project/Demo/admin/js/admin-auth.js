// Admin authentication script
// This script checks if the user is logged in and has admin privileges
// If not, it redirects to the login page

// Function to check if user is logged in and has admin privileges
function checkAdminAuth() {
    // Make a fetch request to check admin auth status
    fetch('../php/check_admin_auth.php')
        .then(response => response.json())
        .then(data => {
            console.log('Auth check response:', data);
            
            if (!data.authenticated || data.role !== 'admin') {
                console.error('Not authenticated as admin, redirecting to login page');
                window.location.href = 'login.html';
            } else {
                console.log('Authenticated as admin');
                // Update username if available
                if (data.name && document.getElementById('admin-username')) {
                    document.getElementById('admin-username').textContent = data.name;
                }
            }
        })
        .catch(error => {
            console.error('Error checking authentication:', error);
            // Redirect to login page on error
            window.location.href = 'login.html';
        });
}

// Run auth check immediately
checkAdminAuth();
