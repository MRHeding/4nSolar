<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '4nsolar_inventory');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Currency configuration
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_HR', 'hr');
define('ROLE_SALES', 'sales');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Check if user has permission (admin has all permissions)
function hasPermission($roles) {
    if (!isLoggedIn()) return false;
    if ($_SESSION['role'] === ROLE_ADMIN) return true;
    return in_array($_SESSION['role'], $roles);
}

// Format currency
function formatCurrency($amount, $decimals = 2) {
    return CURRENCY_SYMBOL . number_format($amount, $decimals);
}
?>
