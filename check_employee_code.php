<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user has permission
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Check if employee code is provided
if (!isset($_POST['employee_code']) || empty(trim($_POST['employee_code']))) {
    echo json_encode(['exists' => false]);
    exit;
}

$employee_code = trim($_POST['employee_code']);

try {
    // Check if employee code already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = ?");
    $stmt->execute([$employee_code]);
    $count = $stmt->fetchColumn();
    
    echo json_encode(['exists' => $count > 0]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
