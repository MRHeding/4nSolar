<?php
require_once 'config.php';

// Require user to be logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Login function
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, username, password, role, full_name, email, is_active FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        return true;
    }
    
    return false;
}

// Logout function
function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get all users
function getUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, username, email, role, full_name, created_at, is_active FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Create new user
function createUser($username, $password, $email, $role, $full_name) {
    global $pdo;
    
    // Validate role
    $valid_roles = [ROLE_ADMIN, ROLE_HR, ROLE_SALES];
    if (!in_array($role, $valid_roles)) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $hashedPassword, $email, $role, $full_name]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update user
function updateUser($id, $username, $email, $role, $full_name, $is_active) {
    global $pdo;
    
    // Validate role
    $valid_roles = [ROLE_ADMIN, ROLE_HR, ROLE_SALES];
    if (!in_array($role, $valid_roles)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, full_name = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([$username, $email, $role, $full_name, $is_active, $id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Change password
function changePassword($user_id, $new_password) {
    global $pdo;
    
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $user_id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get user by ID
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
?>
