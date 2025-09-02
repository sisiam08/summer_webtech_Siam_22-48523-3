<?php
// Auto login as admin script
session_start();

// Set admin session directly
$_SESSION['user_id'] = 1; // Assuming admin ID is 1
$_SESSION['user_role'] = 'admin';

echo "Successfully logged in as admin!";
echo "<p>Click <a href='admin/index.html'>here</a> to go to admin panel.</p>";
?>
