<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$item_id = $_GET['item_id'] ?? null;

error_log("AJAX DEBUG - Item ID requested: " . $item_id);

if (!$item_id) {
    error_log("AJAX DEBUG - No item ID provided");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Item ID required']);
    exit();
}

try {
    error_log("AJAX DEBUG - Calling getAvailableSerials for item: " . $item_id);
    $serials = getAvailableSerials($item_id);
    error_log("AJAX DEBUG - Found " . count($serials) . " available serials");
    error_log("AJAX DEBUG - Serials: " . print_r($serials, true));
    
    echo json_encode([
        'success' => true,
        'serials' => $serials
    ]);
} catch (Exception $e) {
    error_log("AJAX DEBUG - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load serials: ' . $e->getMessage()
    ]);
}
?>
