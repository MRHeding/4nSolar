<?php
require_once 'config.php';

// Get all suppliers
function getSuppliers($active_only = true) {
    global $pdo;
    
    $sql = "SELECT * FROM suppliers";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Get single supplier
function getSupplier($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Add new supplier
function addSupplier($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$data['name'], $data['contact_person'], $data['email'], $data['phone'], $data['address']]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update supplier
function updateSupplier($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        return $stmt->execute([$data['name'], $data['contact_person'], $data['email'], $data['phone'], $data['address'], $id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Delete supplier (soft delete)
function deleteSupplier($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get all categories
function getCategories($active_only = true) {
    global $pdo;
    
    $sql = "SELECT * FROM categories";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Get single category
function getCategory($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Add new category
function addCategory($name, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        return $stmt->execute([$name, $description]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update category
function updateCategory($id, $name, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Delete category (soft delete)
function deleteCategory($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}
?>
