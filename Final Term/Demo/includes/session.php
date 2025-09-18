<?php
// Initialize session with proper configuration
require "./session_init.php";

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Role-specific check functions
function isAdmin() {
    return hasRole('admin');
}

function isShopOwner() {
    return hasRole('shop_owner');
}

function isDelivery() {
    return hasRole('delivery_man');
}

function isCustomer() {
    return hasRole('customer');
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}
?>