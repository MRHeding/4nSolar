<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/suppliers.php';
require_once 'includes/installments.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$quote_id = $_GET['quote_id'] ?? null;

// Handle AJAX request for profit data
if ($action === 'get_profit_data' && $quote_id) {
    header('Content-Type: application/json');
    
    try {
        $quote = getQuoteWithProfitData($quote_id);
        if ($quote) {
            echo json_encode([
                'success' => true,
                'profit_data' => $quote
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Quotation not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading profit data: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for customer details
if ($action === 'get_customer_details' && $quote_id) {
    header('Content-Type: application/json');
    
    try {
        // Get quote information
        $quote = getQuote($quote_id);
        
        // Get customer information
        $customer_info = getCustomerInfo($quote_id);
        
        // Get solar project details
        $solar_details = getSolarProjectDetails($quote_id);
        
        // Get quote items to check for existing labor fee
        $quote_items = [];
        if ($quote && isset($quote['items'])) {
            $quote_items = $quote['items'];
        }
        
        echo json_encode([
            'success' => true,
            'quote_info' => $quote,
            'customer_info' => $customer_info,
            'solar_details' => $solar_details,
            'quote_items' => $quote_items
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading customer details: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for payment details
if ($action === 'get_payment_details' && isset($_GET['payment_id'])) {
    header('Content-Type: application/json');
    
    try {
        $payment_id = intval($_GET['payment_id']);
        
        $stmt = $pdo->prepare("SELECT * FROM installment_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            echo json_encode([
                'success' => true,
                'payment' => $payment
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Payment not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading payment details: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for inventory item details
if ($action === 'get_inventory_item' && isset($_GET['item_id'])) {
    header('Content-Type: application/json');
    
    try {
        $item_id = intval($_GET['item_id']);
        
        // Get inventory item details
        $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            // Get categories for dropdown
            $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get suppliers for dropdown
            $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
            $stmt->execute();
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $item['categories'] = $categories;
            $item['suppliers'] = $suppliers;
            
            echo json_encode([
                'success' => true,
                'item' => $item
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Inventory item not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading inventory item: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for checking stock availability
if ($action === 'check_quote_stock' && isset($_GET['quote_id'])) {
    header('Content-Type: application/json');
    
    try {
        $quote_id = intval($_GET['quote_id']);
        
        // Get all items in this quote with stock information (excluding Labor Fee items)
        $stmt = $pdo->prepare("SELECT qi.inventory_item_id, qi.quantity, 
                                     i.brand, i.model, i.stock_quantity, i.generate_serials
                              FROM quote_items qi 
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id 
                              WHERE qi.quote_id = ? AND qi.inventory_item_id IS NOT NULL 
                              AND NOT (i.brand = 'LABOR' AND i.model = 'Labor Fee')");
        $stmt->execute([$quote_id]);
        $quote_items = $stmt->fetchAll();
        
        $insufficient_stock_items = [];
        $sufficient_stock_items = [];
        
        foreach ($quote_items as $item) {
            $quantity_needed = $item['quantity'];
            $current_stock = $item['stock_quantity'];
            
            $item_info = [
                'item' => $item['brand'] . ' ' . $item['model'],
                'available' => $current_stock,
                'needed' => $quantity_needed,
                'shortage' => max(0, $quantity_needed - $current_stock)
            ];
            
            if ($current_stock < $quantity_needed) {
                $insufficient_stock_items[] = $item_info;
            } else {
                $sufficient_stock_items[] = $item_info;
            }
        }
        
        echo json_encode([
            'success' => true,
            'has_insufficient_stock' => !empty($insufficient_stock_items),
            'insufficient_items' => $insufficient_stock_items,
            'sufficient_items' => $sufficient_stock_items,
            'total_items' => count($quote_items)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error checking stock: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for plan details
if ($action === 'get_plan_details' && isset($_GET['plan_id'])) {
    header('Content-Type: application/json');
    
    try {
        $plan_id = intval($_GET['plan_id']);
        
        $stmt = $pdo->prepare("SELECT * FROM installment_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo json_encode([
                'success' => true,
                'plan' => $plan
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Plan not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading plan details: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Check for success messages from redirects
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'create_installment_plan':
            if ($quote_id) {
                // Debug: Log the form data being submitted
                error_log("Creating installment plan with data: " . print_r($_POST, true));
                $result = createInstallmentPlan($quote_id, $_POST);
                if ($result['success']) {
                    header("Location: ?action=installments&quote_id=" . $quote_id . "&message=" . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_installment_plan':
            if (isset($_POST['plan_id'])) {
                $result = updateInstallmentPlan($_POST['plan_id'], $_POST);
                if ($result['success']) {
                    header("Location: ?action=installments&quote_id=" . $quote_id . "&message=" . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_installment_payment':
            if (isset($_POST['payment_id'])) {
                $result = updateInstallmentPayment($_POST['payment_id'], $_POST);
                if ($result['success']) {
                    header("Location: ?action=installments&quote_id=" . $quote_id . "&message=" . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'delete_installment_plan':
            if (isset($_POST['plan_id'])) {
                $result = deleteInstallmentPlan($_POST['plan_id']);
                if ($result['success']) {
                    header("Location: ?action=installments&quote_id=" . $quote_id . "&message=" . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'record_payment':
            if (isset($_POST['payment_id'])) {
                // Debug information
                error_log("Recording payment for payment_id: " . $_POST['payment_id']);
                error_log("Payment data: " . print_r($_POST, true));
                
                $result = recordInstallmentPayment($_POST['payment_id'], $_POST);
                
                error_log("Payment result: " . print_r($result, true));
                
                if ($result['success']) {
                    $message = $result['message'];
                    if (isset($result['receipt_number']) && $result['receipt_number']) {
                        $message .= " Receipt #: " . $result['receipt_number'];
                    }
                    if (isset($result['reference_number']) && $result['reference_number']) {
                        $message .= " Reference: " . $result['reference_number'];
                    }
                    header("Location: ?action=installments&quote_id=" . $quote_id . "&message=" . urlencode($message));
                    exit();
                } else {
                    $error = $result['message'];
                    error_log("Payment recording error: " . $error);
                }
            } else {
                $error = "Payment ID not provided";
                error_log("Payment error: payment_id not provided");
            }
            break;
            
        case 'create_quote':
            $quote_id = createQuote($_POST['customer_name'] ?? null, $_POST['customer_phone'] ?? null, $_POST['proposal_name'] ?? null);
            if ($quote_id) {
                // Save customer information
                saveCustomerInfo($quote_id, $_POST);
                
                // Save solar project details
                saveSolarProjectDetails($quote_id, $_POST);
                
                // Check if KW and Labor Fee are provided
                $kw = floatval($_POST['kw'] ?? 0);
                $labor_fee = floatval($_POST['labor_fee'] ?? 0);
                
                if ($kw > 0 && $labor_fee > 0) {
                    $total_labor_cost = $kw * $labor_fee;
                    $labor_item_name = "Labor Fee Calculation";
                    
                    // Add the labor fee as a custom quote item
                    $labor_result = addCustomQuoteItem($quote_id, $labor_item_name, $kw, $labor_fee);
                    
                    if ($labor_result['success']) {
                        $message = 'New quotation created successfully with labor fee and additional details!';
                    } else {
                        $message = 'Quotation created with additional details, but failed to add labor fee: ' . $labor_result['message'];
                    }
                } else {
                    $message = 'New quotation created successfully with additional details!';
                }
                $action = 'quote';
            } else {
                $error = 'Failed to create quotation.';
            }
            break;
            
        case 'duplicate_quote':
            if (isset($_POST['original_quote_id'])) {
                $result = duplicateQuotation($_POST['original_quote_id']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $result['new_quote_id'] . "&message=" . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'Original quotation ID is required for duplication.';
            }
            break;
            
        case 'add_to_quote':
            if ($quote_id && isset($_POST['inventory_item_id']) && isset($_POST['quantity'])) {
                $item_id = $_POST['inventory_item_id'];
                $quantity = $_POST['quantity'];
                $selected_serials = $_POST['selected_quote_serials'] ?? [];
                
                // Filter out empty serial numbers
                $filtered_serials = array_filter($selected_serials, function($serial) {
                    return !empty(trim($serial));
                });
                
                // Check if item generates serials
                global $pdo;
                $stmt = $pdo->prepare("SELECT generate_serials FROM inventory_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $generates_serials = $stmt->fetchColumn();
                
                // Validate serial selection for serialized items
                if ($generates_serials) {
                    if (empty($filtered_serials)) {
                        $error = "Please select serial numbers for this item.";
                        break;
                    }
                    if (count($filtered_serials) != $quantity) {
                        $error = "Number of selected serials must match quantity. Expected: $quantity, Got: " . count($filtered_serials);
                        break;
                    }
                }
                
                $result = addQuoteItem($quote_id, $item_id, $quantity, $_POST['discount_percentage'] ?? 0);
                if ($result['success']) {
                    // Reserve specific serial numbers if item generates them
                    if ($generates_serials && !empty($filtered_serials)) {
                        $serial_result = reserveSpecificSerialsForQuote($quote_id, $item_id, $filtered_serials);
                        if (!$serial_result['success']) {
                            // Log error but don't fail the quote addition
                            error_log("Failed to reserve specific serials for quote $quote_id: " . $serial_result['message']);
                        }
                    }
                    
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Item added to quotation successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'add_direct_quote_item':
            if ($quote_id && isset($_POST['inventory_item_id']) && isset($_POST['custom_quantity']) && isset($_POST['custom_price'])) {
                $item_id = $_POST['inventory_item_id'];
                $custom_quantity = $_POST['custom_quantity'];
                $custom_price = $_POST['custom_price'];
                $discount_percentage = $_POST['discount_percentage'] ?? 0;
                
                $result = addDirectQuoteItem($quote_id, $item_id, $custom_quantity, $custom_price, $discount_percentage);
                if ($result['success']) {
                    $message = 'Direct quote item added successfully!';
                    if (!empty($result['generated_serials'])) {
                        $message .= ' Generated ' . count($result['generated_serials']) . ' serial numbers.';
                    }
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode($message));
                    exit();
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'Missing required fields for direct quote item.';
            }
            break;
            
        case 'create_purchase_order':
            if ($quote_id && isset($_POST['item_to_order'])) {
                $supplier_name = $_POST['supplier_name'] ?? '';
                $contact_person = $_POST['contact_person'] ?? '';
                $supplier_phone = $_POST['supplier_phone'] ?? '';
                $supplier_email = $_POST['supplier_email'] ?? '';
                $supplier_address = $_POST['supplier_address'] ?? '';
                $special_instructions = $_POST['special_instructions'] ?? '';
                $delivery_requirements = $_POST['delivery_requirements'] ?? '';
                $items_to_order = $_POST['item_to_order'];
                
                // Generate purchase order number
                $po_number = 'PO-' . date('Y') . '-' . str_pad($quote_id, 4, '0', STR_PAD_LEFT);
                
                // Create purchase order record
                $result = createPurchaseOrder($quote_id, $po_number, $supplier_name, $contact_person, $supplier_phone, $supplier_email, $supplier_address, $special_instructions, $delivery_requirements, $items_to_order);
                
                if ($result['success']) {
                    $message = 'Purchase Order created successfully! PO Number: ' . $po_number;
                    header("Location: ?action=purchase_order&quote_id=" . $quote_id . "&message=" . urlencode($message));
                    exit();
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'Please select at least one item to order and provide supplier information.';
            }
            break;
            
        case 'update_quote_quantity':
            if (isset($_POST['quote_item_id']) && isset($_POST['new_quantity'])) {
                $result = updateQuoteItemQuantity($_POST['quote_item_id'], $_POST['new_quantity']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Quantity updated successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_quote_discount':
            if (isset($_POST['quote_item_id']) && isset($_POST['new_discount_percentage'])) {
                $result = updateQuoteItemDiscount($_POST['quote_item_id'], $_POST['new_discount_percentage']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Discount updated successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_quote_unit_price':
            if (isset($_POST['quote_item_id']) && isset($_POST['new_unit_price'])) {
                $result = updateQuoteItemUnitPrice($_POST['quote_item_id'], $_POST['new_unit_price']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Unit price updated successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_customer_details':
            if ($quote_id) {
                $customer_updated = saveCustomerInfo($quote_id, $_POST);
                $solar_updated = saveSolarProjectDetails($quote_id, $_POST);
                
                // Update proposal name if provided
                $proposal_updated = true;
                if (isset($_POST['proposal_name'])) {
                    $proposal_updated = updateQuoteProposalName($quote_id, $_POST['proposal_name']);
                }
                
                // Check if KW and Labor Fee are provided and update/create Labor Fee item
                $kw = floatval($_POST['kw'] ?? 0);
                $labor_fee = floatval($_POST['labor_fee'] ?? 0);
                $labor_updated = true;
                
                if ($kw > 0 && $labor_fee > 0) {
                    // Update existing labor fee item or create new one
                    $labor_result = updateOrCreateLaborFeeItem($quote_id, $kw, $labor_fee);
                    if (!$labor_result['success']) {
                        $labor_updated = false;
                    }
                } else if ($kw == 0 && $labor_fee == 0) {
                    // Remove existing labor fee item if both are 0
                    $existing_labor_item = findLaborFeeItem($quote_id);
                    if ($existing_labor_item) {
                        removeQuoteItem($existing_labor_item['id']);
                    }
                }
                
                if ($customer_updated && $solar_updated && $proposal_updated) {
                    $message = 'Customer and solar project details updated successfully!';
                    if ($kw > 0 && $labor_fee > 0) {
                        if ($labor_updated) {
                            $message .= ' Labor fee has been updated in quote items.';
                        } else {
                            $message .= ' Note: Failed to update labor fee in quote items.';
                        }
                    } else if ($kw == 0 && $labor_fee == 0) {
                        $message .= ' Labor fee has been removed from quote items.';
                    }
                    
                    // Return JSON response for AJAX handling
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit();
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update customer or solar project details.'
                    ]);
                    exit();
                }
            }
            break;
            
        case 'update_quote_status':
            if ($quote_id && isset($_POST['new_status'])) {
                // Check if we should ignore stock levels
                $ignore_stock = isset($_POST['ignore_stock']) && $_POST['ignore_stock'] === '1';
                
                $result = updateQuoteStatus($quote_id, $_POST['new_status'], $ignore_stock);
                
                if (is_array($result) && $result['success']) {
                    $success_message = 'Status updated successfully!';
                    
                    // If status was changed to accepted, generate and assign a project number
                    if ($_POST['new_status'] === 'accepted') {
                        // Generate project number (format: PRJ-YYYYMM-XXXX)
                        $yearMonth = date('Ym');
                        $sql = "SELECT COUNT(*) as count FROM quotations WHERE project_number LIKE ?";
                        $like = "PRJ-$yearMonth-%";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$like]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $nextNum = str_pad(($row['count'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                        $projectNumber = "PRJ-$yearMonth-$nextNum";
                        
                        // Save project number
                        $updateSql = "UPDATE quotations SET project_number = ? WHERE id = ?";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute([$projectNumber, $quote_id]);
                        
                        $success_message .= " Project Number assigned: $projectNumber";
                        
                        // Add inventory deduction message
                        if (isset($result['inventory_result']) && $result['inventory_result']['success']) {
                            if ($ignore_stock) {
                                $success_message .= " Quote approved with stock levels ignored.";
                            } else {
                                $deducted_count = count($result['inventory_result']['deducted_items']);
                                $success_message .= " Inventory deducted for $deducted_count items.";
                            }
                        }
                        
                        // Add revenue tracking message
                        if (isset($result['project_created']) && $result['project_created']) {
                            $success_message .= " Project created and will be counted in today's revenue.";
                        }
                    }
                    
                    // If status was reverted from accepted to draft, show restoration message
                    if ($_POST['new_status'] === 'draft' && isset($result['restore_result']) && $result['restore_result']['success']) {
                        $restored_count = count($result['restore_result']['restored_items']);
                        $success_message .= " Inventory restored for $restored_count items.";
                    }
                    
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode($success_message));
                    exit();
                } elseif (is_array($result) && !$result['success']) {
                    // Handle inventory error specifically
                    if (isset($result['inventory_error']) && $result['inventory_error']) {
                        $error = $result['message'];
                    } else {
                        $error = 'Failed to update quotation status.';
                    }
                } elseif ($result === true) {
                    // Handle simple boolean success
                    $success_message = 'Status updated successfully!';
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode($success_message));
                    exit();
                } else {
                    $error = 'Failed to update quotation status.';
                }
            }
            break;
            
        case 'update_inventory_item':
            // Debug: Log that we hit this action
            error_log("Hit update_inventory_item action");
            
            // Start output buffering to catch any unwanted output
            ob_start();
            
            if (isset($_POST['inventory_item_id'])) {
                // Debug: Log the POST data (remove in production)
                error_log("Updating inventory item ID: " . $_POST['inventory_item_id']);
                error_log("POST data: " . print_r($_POST, true));
                
                try {
                    $result = updateInventoryItem($_POST['inventory_item_id'], $_POST);
                    
                    // Clear any buffered output
                    ob_clean();
                    
                    // Set JSON header
                    header('Content-Type: application/json');
                    
                    if ($result && isset($result['success'])) {
                        echo json_encode([
                            'success' => $result['success'],
                            'message' => $result['message']
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Unknown error occurred during update'
                        ]);
                    }
                } catch (Exception $e) {
                    // Clear any buffered output
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error: ' . $e->getMessage()
                    ]);
                }
                exit();
            } else {
                // Clear any buffered output
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'No inventory item ID provided'
                ]);
                exit();
            }
            break;
    }
}

// Handle quote actions
if ($action == 'remove_quote_item' && isset($_GET['item_id']) && $quote_id) {
    if (removeQuoteItem($_GET['item_id'])) {
        header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Item removed from quotation successfully!'));
        exit();
    } else {
        $error = 'Failed to remove item from quotation.';
    }
}

if ($action == 'delete_quote' && $quote_id) {
    if (deleteQuote($quote_id)) {
        $message = 'Quotation deleted successfully!';
        $action = 'list';
    } else {
        $error = 'Failed to delete quotation.';
    }
}

// Get data based on action
switch ($action) {
    case 'installments':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                $installment_plan = getInstallmentPlanWithAdjustments($quote_id);
                $customer_info = getCustomerInfo($quote_id);
            }
        }
        break;
        
    case 'quote':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                $inventory_items = getQuoteInventoryItems();
                // Check if quote has installment plan
                $installment_plan = getInstallmentPlanWithAdjustments($quote_id);
            }
        }
        break;
        
    case 'purchase_order':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                // Get customer information
                $customer_info = getCustomerInfo($quote_id);
            }
        } else {
            $error = 'Quote ID is required for purchase order.';
            $action = 'list';
        }
        break;
        
    case 'order_fulfillment':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                // Get customer information for the order fulfillment
                $customer_info = getCustomerInfo($quote_id);
            }
        }
        break;
        
    case 'new_quote':
        $inventory_items = getQuoteInventoryItems();
        break;
        
    default:
        // Get filter parameters from URL
        $status_filter = $_GET['status'] ?? null;
        $search_term = $_GET['search'] ?? null;
        $date_filter = $_GET['date'] ?? null;
        
        $quotes = getQuotes($status_filter, $search_term, $date_filter);
        break;
}

$page_title = 'Quotations Management';
$content_start = true;
include 'includes/header.php';
?>

<style>
/* Compact action buttons layout */
.actions-column {
    width: 140px;
    min-width: 140px;
    max-width: 140px;
}

.action-buttons {
    min-width: 140px;
    gap: 1px;
}

.action-buttons .inline-block {
    margin: 0;
}

.action-buttons button,
.action-buttons a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 16px;
    height: 20px;
    padding: 2px;
    text-align: center;
    font-size: 10px;
    border-radius: 2px;
}

.action-buttons i {
    font-size: 10px;
}

/* Customer name cell styling */
.customer-name-cell {
    max-width: 200px;
    min-width: 150px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.customer-name-text {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.customer-phone-text {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Ensure table doesn't break layout */
.compact-table {
    table-layout: fixed;
}

.compact-table th:last-child,
.compact-table td:last-child {
    width: 140px;
}

/* Ensure table columns have proper widths */
.compact-table th:nth-child(1), .compact-table td:nth-child(1) { width: 100px; min-width: 100px; max-width: 100px; } /* Quote # */
.compact-table th:nth-child(2), .compact-table td:nth-child(2) { width: 200px; min-width: 200px; max-width: 200px; } /* Customer */
.compact-table th:nth-child(3), .compact-table td:nth-child(3) { width: 150px; min-width: 150px; max-width: 150px; } /* Proposal */
.compact-table th:nth-child(4), .compact-table td:nth-child(4) { width: 80px; min-width: 80px; max-width: 80px; }  /* Items */
.compact-table th:nth-child(5), .compact-table td:nth-child(5) { width: 100px; min-width: 100px; max-width: 100px; } /* Total */
.compact-table th:nth-child(6), .compact-table td:nth-child(6) { width: 100px; min-width: 100px; max-width: 100px; } /* Status */
.compact-table th:nth-child(7), .compact-table td:nth-child(7) { width: 100px; min-width: 100px; max-width: 100px; } /* Date */
.compact-table th:nth-child(8), .compact-table td:nth-child(8) { width: 140px; min-width: 140px; max-width: 140px; } /* Actions */

/* Force table layout to respect column widths */
.compact-table {
    table-layout: fixed;
    width: 100%;
}

/* Truncate long text in proposal column */
.compact-table td:nth-child(3) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}


/* Make table more compact */
.compact-table td {
    padding: 8px 12px;
}

.compact-table th {
    padding: 8px 12px;
}
</style>

<?php if ($message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showNotification('<?php echo addslashes($message); ?>', 'success');
});
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showNotification('<?php echo addslashes($error); ?>', 'error');
});
</script>
<?php endif; ?>

<?php if ($action == 'list'): ?>
<!-- Quotations List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Quotations Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage all customer quotations</p>
        </div>
        <div class="space-x-2">
            <a href="?action=new_quote" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i>New Quote
            </a>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search Bar -->
        <div class="lg:col-span-2">
            <label for="quote-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Quotations</label>
            <input type="text" id="quote-search" placeholder="Search by customer name, quote number, or proposal..." 
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white"
                   oninput="filterQuotations()">
        </div>
        
        <!-- Status Filter -->
        <div>
            <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select id="status-filter" onchange="filterQuotations()" 
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="under_review">Under Review</option>
                <option value="accepted">Approved</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        
        <!-- Date Range Filter -->
        <div>
            <label for="date-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
            <select id="date-filter" onchange="filterQuotations()" 
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
            </select>
        </div>
    </div>
    
    <!-- Quick Filter Buttons -->
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Filters</label>
        <div class="flex flex-wrap gap-2">
            <button onclick="setQuickFilter('')" class="quick-filter-btn px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition active">
                All Quotations
            </button>
            <button onclick="setQuickFilter('draft')" class="quick-filter-btn px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                Draft
            </button>
            <button onclick="setQuickFilter('sent')" class="quick-filter-btn px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                Sent
            </button>
            <button onclick="setQuickFilter('under_review')" class="quick-filter-btn px-3 py-1 text-sm bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-md hover:bg-purple-200 dark:hover:bg-purple-800 transition">
                Under Review
            </button>
            <button onclick="setQuickFilter('accepted')" class="quick-filter-btn px-3 py-1 text-sm bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-md hover:bg-green-200 dark:hover:bg-green-800 transition">
                Approved
            </button>
            <button onclick="setQuickFilter('rejected')" class="quick-filter-btn px-3 py-1 text-sm bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-md hover:bg-red-200 dark:hover:bg-red-800 transition">
                Rejected
            </button>
            <button onclick="setQuickFilter('installment')" class="quick-filter-btn px-3 py-1 text-sm bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 rounded-md hover:bg-orange-200 dark:hover:bg-orange-800 transition">
                With Installments
            </button>
        </div>
    </div>
    
    <!-- Results Summary -->
    <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <span id="filtered-count">0</span> of <span id="total-count">0</span> quotations
        </div>
        <div class="flex gap-2">
            <button onclick="clearFilters()" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                Clear Filters
            </button>
            <button onclick="exportFilteredQuotations()" class="px-3 py-1 text-sm bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-300 rounded-md hover:bg-green-200 dark:hover:bg-green-600 transition">
                <i class="fas fa-download mr-1"></i>Export
            </button>
        </div>
    </div>
</div>

<!-- Quotations Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 compact-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote #</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider customer-name-cell">Customer</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-1 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider actions-column">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($quotes)): ?>
                <?php foreach ($quotes as $quote): ?>
                <tr class="hover:bg-gray-50 quote-row" 
                    data-quote-number="<?php echo htmlspecialchars($quote['quote_number']); ?>"
                    data-customer-name="<?php echo htmlspecialchars(strtolower($quote['customer_name'])); ?>"
                    data-proposal-name="<?php echo htmlspecialchars(strtolower($quote['proposal_name'] ?? '')); ?>"
                    data-status="<?php echo $quote['status']; ?>"
                    data-date="<?php echo date('Y-m-d', strtotime($quote['created_at'])); ?>"
                    data-has-installment="<?php echo $quote['has_installment_plan'] > 0 ? 'yes' : 'no'; ?>"
                    data-total-amount="<?php echo $quote['total_amount']; ?>"
                    data-items-count="<?php echo $quote['items_count']; ?>">
                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($quote['quote_number']); ?>
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-900 customer-name-cell">
                        <div class="customer-name-text" title="<?php echo htmlspecialchars($quote['customer_name']); ?>">
                            <?php echo htmlspecialchars($quote['customer_name']); ?>
                        </div>
                        <?php if ($quote['customer_phone']): ?>
                        <div class="text-xs text-gray-500 customer-phone-text" title="<?php echo htmlspecialchars($quote['customer_phone']); ?>">
                            <?php echo htmlspecialchars($quote['customer_phone']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($quote['proposal_name'] ?? '-'); ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $quote['items_count']; ?> items
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($quote['total_amount']); ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php 
                                switch($quote['status']) {
                                    case 'draft': echo 'bg-gray-100 text-gray-800 dark:text-gray-200'; break;
                                    case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'under_review': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'accepted': 
                                    case 'approved': echo 'bg-green-100 text-green-800'; break;
                                    case 'ongoing': echo 'bg-orange-100 text-orange-800'; break;
                                    case 'completed': echo 'bg-emerald-100 text-emerald-800'; break;
                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                    case 'expired': echo 'bg-yellow-100 text-yellow-800'; break;
                                }
                                ?>">
                                <?php 
                                $status_display = $quote['status'];
                                if ($status_display === 'accepted') {
                                    $status_display = 'Approved';
                                } else {
                                    $status_display = ucfirst(str_replace('_', ' ', $status_display));
                                }
                                echo $status_display;
                                ?>
                            </span>
                            <?php if ($quote['has_installment_plan'] > 0): ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 flex items-center">
                                <i class="fas fa-credit-card mr-1"></i>
                                <?php 
                                if ($quote['installment_status'] === 'completed') {
                                    echo 'Installment Completed';
                                } else {
                                    echo 'Installment Plan';
                                }
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                    </td>
                    <td class="px-1 py-3 whitespace-nowrap text-center actions-column">
                        <div class="flex justify-center items-center space-x-0 flex-nowrap action-buttons">
                            <a href="?action=quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-0.5 inline-block" title="View/Edit Quote">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <button onclick="viewCustomerDetails(<?php echo $quote['id']; ?>)" 
                                    class="text-green-600 hover:text-green-900 p-0.5 inline-block" title="View Customer Details">
                                <i class="fas fa-user text-xs"></i>
                            </button>
                            <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" 
                                    class="text-orange-600 hover:text-orange-900 p-0.5 inline-block" title="Edit Customer Details">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            
                            <!-- Status Update Buttons -->
                            <?php if ($quote['status'] == 'draft'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="sent">
                                <button type="submit" class="text-purple-600 hover:text-purple-900 p-0.5" title="Mark as Sent">
                                    <i class="fas fa-paper-plane text-xs"></i>
                                </button>
                            </form>
                            <button type="button" class="text-green-600 hover:text-green-900 p-0.5" title="Approve Quote" onclick="showQuoteApprovalModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                                <i class="fas fa-check text-xs"></i>
                            </button>
                            <?php elseif ($quote['status'] == 'sent'): ?>
                            <button type="button" class="text-green-600 hover:text-green-900 p-0.5" title="Approve Quote" onclick="showQuoteApprovalModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                                <i class="fas fa-check text-xs"></i>
                            </button>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="rejected">
                                <button type="submit" class="text-red-600 hover:text-red-900 p-0.5" title="Reject Quote">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </form>
                            <?php elseif ($quote['status'] == 'accepted' || $quote['status'] == 'approved'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="draft">
                                <button type="submit" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 p-0.5" title="Revert to Draft">
                                    <i class="fas fa-undo text-xs"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <button onclick="duplicateQuote(<?php echo $quote['id']; ?>)" 
                                    class="text-purple-600 hover:text-purple-900 p-0.5 inline-block" title="Duplicate Quote">
                                <i class="fas fa-copy text-xs"></i>
                            </button>
                            <a href="print_inventory_quote.php?id=<?php echo $quote['id']; ?>"
                               class="text-orange-600 hover:text-orange-900 p-0.5 inline-block" title="Print Quote">
                                <i class="fas fa-print text-xs"></i>
                            </a>
                            <a href="?action=delete_quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-red-600 hover:text-red-900 p-0.5 inline-block"
                               onclick="return confirm('Are you sure you want to delete this quotation?')"
                               title="Delete Quote">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No quotations found. <a href="?action=new_quote" class="text-blue-600 hover:text-blue-800">Create your first quotation</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action == 'new_quote'): ?>
<!-- New Quotation Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">New Quotation</h1>
            <p class="text-gray-600 dark:text-gray-400">Create a new quotation for customer</p>
        </div>
        <div class="space-x-2">
        </div>
    </div>
</div>

<!-- Create New Quote -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Create New Quotation</h2>
    <form method="POST" action="?action=create_quote" class="space-y-6">
        <!-- Basic Quotation Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                <input type="text" id="customer_name" name="customer_name" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter customer name">
            </div>
            <div>
                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Customer Phone</label>
                <input type="tel" id="customer_phone" name="customer_phone"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter phone number">
            </div>
            <div>
                <label for="proposal_name" class="block text-sm font-medium text-gray-700 mb-2">Proposal Name</label>
                <input type="text" id="proposal_name" name="proposal_name"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter proposal name">
            </div>
            <div>
                <label for="kw" class="block text-sm font-medium text-gray-700 mb-2">KW</label>
                <input type="number" id="kw" name="kw" min="0" step="any" 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                       placeholder="Enter KW value">
            </div>
            <div>
                <label for="labor_fee" class="block text-sm font-medium text-gray-700 mb-2">Labor Fee (PHP)</label>
                <input type="number" id="labor_fee" name="labor_fee" min="0" step="any" 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                       placeholder="Enter Labor Fee">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Total (KW * Labor Fee)</label>
                <input type="text" id="total_kw_labor" readonly 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100" 
                       value="" placeholder="Total will appear here">
            </div>
        </div>
        
        <!-- Customer Information Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter full name">
                </div>
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter phone number">
                </div>
                <div>
                    <label for="account_creation_date" class="block text-sm font-medium text-gray-700 mb-2">Account Creation Date</label>
                    <input type="date" id="account_creation_date" name="account_creation_date"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
            </div>
            
            <div class="mt-4">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                          placeholder="Enter complete address"></textarea>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Contact Method</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="email" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">Email</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="phone" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">Phone</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="sms" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">SMS</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Solar Project Details Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Solar Project Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">System Type</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="grid_tie" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Grid Tie</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="off_grid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Off Grid</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="hybrid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Hybrid</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label for="system_size" class="block text-sm font-medium text-gray-700 mb-2">System Size (kW)</label>
                    <input type="number" id="system_size" name="system_size" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter system size in kW">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installation Type</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="rooftop" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Rooftop</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="ground_mounted" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Ground Mounted</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="carport" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Carport</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="panel_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Panel Brand/Model</label>
                    <input type="text" id="panel_brand_model" name="panel_brand_model"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter panel brand and model">
                </div>
                <div>
                    <label for="inverter_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Inverter Brand/Model</label>
                    <input type="text" id="inverter_brand_model" name="inverter_brand_model"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter inverter brand and model">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="estimated_installation_date" class="block text-sm font-medium text-gray-700 mb-2">Estimated Installation Date</label>
                    <input type="date" id="estimated_installation_date" name="estimated_installation_date"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installation Status</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="planned" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Planned</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="in_progress" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">In Progress</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="completed" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Completed</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="maintenance" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Maintenance</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Battery Backup Capacity</label>
                        <div class="flex gap-4 mb-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="battery_backup_capacity" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleBatteryCapacityInput()">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="battery_backup_capacity" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleBatteryCapacityInput()">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                        <div id="battery_capacity_input" class="hidden">
                            <input type="text" name="battery_capacity_value" placeholder="Enter battery capacity (e.g., 10kWh, 5000Wh)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-solar-blue focus:border-transparent text-sm">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Net Metering</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="net_metering" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="net_metering" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmed</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="confirmed" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="confirmed" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="client_signature" class="block text-sm font-medium text-gray-700 mb-2">Client Signature Line</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50">
                        <p class="text-sm text-gray-500 mb-2">Client Signature</p>
                        <div class="border-b-2 border-gray-400 w-full h-12"></div>
                        <input type="text" id="client_signature" name="client_signature" placeholder="Type signature or leave blank for manual signing"
                               class="w-full mt-2 border-0 bg-transparent text-center focus:ring-0 text-sm text-gray-600 dark:text-gray-400">
                    </div>
                </div>
                
                <div>
                    <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                              placeholder="Enter any additional remarks or notes"></textarea>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end pt-6 border-t">
            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition text-lg">
                <i class="fas fa-plus mr-2"></i>Create Quotation
            </button>
        </div>
    </form>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTotal() {
        var kw = parseFloat(document.getElementById('kw').value) || 0;
        var labor = parseFloat(document.getElementById('labor_fee').value) || 0;
        document.getElementById('total_kw_labor').value = kw * labor ? (kw * labor).toLocaleString('en-PH', {style: 'currency', currency: 'PHP'}) : '';
    }
    document.getElementById('kw').addEventListener('input', updateTotal);
    document.getElementById('labor_fee').addEventListener('input', updateTotal);
    
});
</script>

<?php elseif (($action == 'quote') && isset($quote)): ?>
<!-- Quote Detail Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?></h1>
            <p class="text-gray-600 dark:text-gray-400">
                Customer: <?php echo htmlspecialchars($quote['customer_name']); ?>
                <?php if ($quote['customer_phone']): ?>
                - <?php echo htmlspecialchars($quote['customer_phone']); ?>
                <?php endif; ?>
                <?php if ($quote['proposal_name']): ?>
                | Proposal: <?php echo htmlspecialchars($quote['proposal_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" 
                    class="bg-orange-600 text-white px-3 py-2 rounded-lg hover:bg-orange-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-edit mr-1"></i>Edit Details
            </button>
            <?php if ($quote['status'] === 'accepted'): ?>
            <a href="?action=installments&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-credit-card mr-1"></i>Payment Plan
            </a>
            <?php endif; ?>
            <a href="?action=purchase_order&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-orange-600 text-white px-3 py-2 rounded-lg hover:bg-orange-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-shopping-cart mr-1"></i>Purchase Order
            </a>
            <a href="?action=order_fulfillment&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-clipboard-check mr-1"></i>Order Fulfillment
            </a>
            <button onclick="duplicateQuote(<?php echo $quote['id']; ?>)" 
                    class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-copy mr-1"></i>Duplicate Quote
            </button>
            <a href="print_inventory_quote.php?id=<?php echo $quote['id']; ?>"
               class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition text-sm whitespace-nowrap">
                <i class="fas fa-print mr-1"></i>Print Quote
            </a>
            <a href="?action=delete_quote&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm whitespace-nowrap"
               onclick="return confirm('Delete this quotation? All items will be removed.')">
                <i class="fas fa-trash mr-1"></i>Delete Quote
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Quote Items -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Quote Items</h2>
                    <?php if (!empty($quote['items'])): ?>
                    <span class="ml-3 bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo count($quote['items']); ?> item<?php echo count($quote['items']) !== 1 ? 's' : ''; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($inventory_items)): ?>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('add-quote-item-modal').classList.remove('hidden')" 
                            class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                    <button onclick="document.getElementById('add-direct-quote-modal').classList.remove('hidden')" 
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                        <i class="fas fa-edit mr-2"></i>Direct Quote
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($quote['items'])): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Qty</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unit Price</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Disc %</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($quote['items'] as $item): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                    <?php if (isset($item['is_direct_quote']) && $item['is_direct_quote']): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-edit mr-1"></i>Direct Quote
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                                <?php if (!isset($item['is_direct_quote']) || !$item['is_direct_quote']): ?>
                                <div class="text-xs text-blue-600">Stock: <?php echo $item['stock_quantity']; ?></div>
                                <?php else: ?>
                                <div class="text-xs text-green-600">Custom Pricing</div>
                                <?php endif; ?>
                                <?php if (!empty($item['serial_numbers'])): ?>
                                <div class="text-xs text-green-600 mt-1">
                                    <button type="button" onclick="toggleSerials('serials-<?php echo $item['id']; ?>')" 
                                            class="text-green-600 hover:text-green-800 font-medium underline">
                                        <i class="fas fa-chevron-down" id="serials-icon-<?php echo $item['id']; ?>"></i>
                                        Reserved Serials (<?php echo substr_count($item['serial_numbers'], ',') + 1; ?>)
                                    </button>
                                    <div id="serials-<?php echo $item['id']; ?>" class="hidden mt-1 text-xs text-gray-600 font-mono bg-gray-50 p-2 rounded border">
                                        <?php 
                                        $serials = explode(',', $item['serial_numbers']);
                                        foreach ($serials as $serial): 
                                        ?>
                                        <div class="mb-1"><?php echo htmlspecialchars(trim($serial)); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <?php if ($item['generate_serials']): ?>
                                    <!-- Serialized item - quantity is not editable -->
                                    <div class="w-16 px-2 py-1 bg-gray-100 border border-gray-300 rounded text-center text-sm text-gray-600" 
                                         title="Quantity is determined by selected serial numbers">
                                        <?php echo $item['quantity']; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Non-serialized item - quantity is editable -->
                                    <form method="POST" action="?action=update_quote_quantity&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                        <input type="hidden" name="quote_item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="w-16 px-2 py-1 border rounded text-center text-sm"
                                               onchange="this.form.submit()">
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <?php 
                                $original_price = $item['original_unit_price'] ?? $item['unit_price'];
                                $price_changed = $original_price != $item['unit_price'];
                                ?>
                                <form method="POST" action="?action=update_quote_unit_price&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                    <input type="hidden" name="quote_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_unit_price" value="<?php echo $item['unit_price']; ?>" 
                                           min="0" step="0.01" class="w-20 px-2 py-1 border rounded text-center text-sm <?php echo $price_changed ? 'text-blue-600 font-medium' : ''; ?>"
                                           onchange="this.form.submit()" 
                                           oninput="calculateItemTotal(this, <?php echo $item['quantity']; ?>, <?php echo $item['discount_percentage']; ?>)"
                                           title="Click to edit unit price">
                                </form>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <form method="POST" action="?action=update_quote_discount&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                    <input type="hidden" name="quote_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_discount_percentage" value="<?php echo $item['discount_percentage']; ?>" 
                                           min="0" max="100" step="0.01" class="w-16 px-2 py-1 border rounded text-center text-sm"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="px-3 py-4 text-sm font-medium text-gray-900">
                                <span id="total_<?php echo $item['id']; ?>"><?php echo formatCurrency($item['total_amount']); ?></span>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if (isset($item['inventory_item_id']) && $item['inventory_item_id'] && $_SESSION['role'] === ROLE_ADMIN): ?>
                                    <button onclick="editInventoryItem(<?php echo $item['inventory_item_id']; ?>, '<?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>')" 
                                            class="text-blue-600 hover:text-blue-900 p-2"
                                            title="Edit inventory item">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="?action=remove_quote_item&quote_id=<?php echo $quote['id']; ?>&item_id=<?php echo $item['id']; ?>" 
                                       class="text-red-600 hover:text-red-900 p-2"
                                       onclick="return confirm('Remove this item?')"
                                       title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No items added to this quotation yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quote Summary -->
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Quote Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                    <span class="font-medium"><?php echo formatCurrency($quote['subtotal']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                    <span class="font-medium text-green-600">-<?php echo formatCurrency($quote['total_discount']); ?></span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-3">
                    <span>Total:</span>
                    <span class="text-solar-blue"><?php echo formatCurrency($quote['total_amount']); ?></span>
                </div>
            </div>
            
            <!-- View Profit Button -->
            <div class="mt-6 pt-6 border-t">
                <button onclick="showProfitModal()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition mb-2">
                    <i class="fas fa-chart-line mr-2"></i>View Profit Breakdown
                </button>
                <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition mb-4">
                    <i class="fas fa-edit mr-2"></i>Edit Customer Details
                </button>
            </div>
            
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Quote Details</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($quote['status']) {
                                case 'draft': echo 'bg-gray-100 text-gray-800 dark:text-gray-200'; break;
                                case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                case 'under_review': echo 'bg-purple-100 text-purple-800'; break;
                                case 'accepted': 
                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                case 'ongoing': echo 'bg-orange-100 text-orange-800'; break;
                                case 'completed': echo 'bg-emerald-100 text-emerald-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'expired': echo 'bg-yellow-100 text-yellow-800'; break;
                            }
                            ?>">
                            <?php 
                            $status_display = $quote['status'];
                            if ($status_display === 'accepted') {
                                $status_display = 'Approved';
                            } else {
                                $status_display = ucfirst(str_replace('_', ' ', $status_display));
                            }
                            echo $status_display;
                            ?>
                        </span>
                        <?php if ($installment_plan): ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 ml-2 flex items-center inline-flex">
                            <i class="fas fa-credit-card mr-1"></i>
                            <?php 
                            if ($installment_plan['status'] === 'completed') {
                                echo 'Installment Completed';
                            } else {
                                echo 'Installment Plan';
                            }
                            ?>
                        </span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($quote['created_at'])); ?></p>
                    <p><strong>Created by:</strong> <?php echo htmlspecialchars($quote['created_by_name']); ?></p>
                </div>
            </div>
            
            <!-- Status Update Section -->
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Update Status</h3>
                <div class="space-y-2">
                    <?php if ($quote['status'] == 'draft'): ?>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="sent">
                        <button type="submit" class="w-full bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition text-sm">
                            <i class="fas fa-paper-plane mr-2"></i>Mark as Sent
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="under_review">
                        <button type="submit" class="w-full bg-purple-600 text-white px-3 py-2 rounded-md hover:bg-purple-700 transition text-sm">
                            <i class="fas fa-eye mr-2"></i>Mark Under Review
                        </button>
                    </form>
                    <button type="button" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm" onclick="showQuoteApprovalModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                        <i class="fas fa-check mr-2"></i>Approve Quote
                    </button>
                    <?php elseif ($quote['status'] == 'sent'): ?>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="under_review">
                        <button type="submit" class="w-full bg-purple-600 text-white px-3 py-2 rounded-md hover:bg-purple-700 transition text-sm">
                            <i class="fas fa-eye mr-2"></i>Mark Under Review
                        </button>
                    </form>
                    <button type="button" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm" onclick="showQuoteApprovalModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                        <i class="fas fa-check mr-2"></i>Approve Quote
                    </button>
                    <?php elseif ($quote['status'] == 'under_review'): ?>
                    <button type="button" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm" onclick="showQuoteApprovalModal(<?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                        <i class="fas fa-check mr-2"></i>Approve Quote
                    </button>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="rejected">
                        <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded-md hover:bg-red-700 transition text-sm">
                            <i class="fas fa-times mr-2"></i>Reject Quote
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'accepted' || $quote['status'] == 'approved'): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-green-800 font-medium">Quote <?php echo $quote['status'] == 'accepted' ? 'Complete' : ucfirst($quote['status']); ?></span>
                        </div>
                        <p class="text-green-700 text-sm mt-1">This quote has been <?php echo $quote['status'] == 'accepted' ? 'completed' : $quote['status']; ?> and is ready for processing.</p>
                    </div>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'rejected'): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 mr-2"></i>
                            <span class="text-red-800 font-medium">Quote Rejected</span>
                        </div>
                        <p class="text-red-700 text-sm mt-1">This quote has been rejected.</p>
                    </div>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Custom Status Selector -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Change to Custom Status:</label>
                        <form id="custom-status-form" method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="flex space-x-2" onsubmit="return handleCustomStatusChange(event, <?php echo $quote['id']; ?>, '<?php echo htmlspecialchars($quote['quote_number']); ?>', '<?php echo htmlspecialchars($quote['customer_name']); ?>', <?php echo $quote['total_amount']; ?>)">
                            <select name="new_status" id="custom-status-select" class="flex-1 text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                                <option value="">Select Status</option>
                                <option value="draft" <?php echo $quote['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="sent" <?php echo $quote['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                <option value="under_review" <?php echo $quote['status'] == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="accepted" <?php echo $quote['status'] == 'accepted' ? 'selected' : ''; ?>>Approved</option>
                                <option value="ongoing" <?php echo $quote['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $quote['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo $quote['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="expired" <?php echo $quote['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition text-sm">
                                Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Quote Item Modal -->
<div id="add-quote-item-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Item to Quotation</h3>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <label for="quote-item-search" class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                <input type="text" id="quote-item-search" placeholder="Search by brand, model, or category..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       oninput="filterQuoteItems()">
            </div>
            
            <!-- Category Filter -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filter by Category</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="filterByQuoteCategory('')" 
                            class="quote-category-filter-btn px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition active">
                        All Items
                    </button>
                    <?php 
                    $quote_categories = [];
                    foreach ($inventory_items as $item) {
                        if (!empty($item['category_name']) && !in_array($item['category_name'], $quote_categories)) {
                            $quote_categories[] = $item['category_name'];
                        }
                    }
                    foreach ($quote_categories as $category): ?>
                    <button type="button" onclick="filterByQuoteCategory('<?php echo strtolower($category); ?>')" 
                            class="quote-category-filter-btn px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Items Grid -->
            <div class="mb-4 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                <?php if (!empty($inventory_items)): ?>
                <div id="quote-items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <?php foreach ($inventory_items as $inv_item): ?>
                    <div class="quote-item-card border border-gray-200 dark:border-gray-600 rounded-lg p-3 hover:bg-blue-50 cursor-pointer transition" 
                         data-item-id="<?php echo $inv_item['id']; ?>"
                         data-brand="<?php echo strtolower($inv_item['brand']); ?>"
                         data-model="<?php echo strtolower($inv_item['model']); ?>"
                         data-category="<?php echo strtolower($inv_item['category_name'] ?? ''); ?>"
                         data-price="<?php echo $inv_item['selling_price']; ?>"
                         data-base-price="<?php echo $inv_item['base_price']; ?>"
                         data-stock="<?php echo $inv_item['stock_quantity']; ?>"
                         data-generates-serials="<?php echo $inv_item['generate_serials'] ? '1' : '0'; ?>"
                         onclick="selectQuoteItem(this)">
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-12 w-12 rounded-lg object-cover border" 
                                     src="<?php echo htmlspecialchars(getProductImageUrl($inv_item['image_path'])); ?>" 
                                     alt="<?php echo htmlspecialchars($inv_item['brand'] . ' ' . $inv_item['model']); ?>">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($inv_item['brand']); ?>
                                </div>
                                <div class="text-sm text-gray-500 truncate">
                                    <?php echo htmlspecialchars($inv_item['model']); ?>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo htmlspecialchars($inv_item['category_name'] ?? 'N/A'); ?>
                                </div>
                                <?php if ($inv_item['generate_serials']): ?>
                                <div class="text-xs text-blue-600">
                                    <i class="fas fa-barcode mr-1"></i>Serialized Item
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($inv_item['selling_price']); ?>
                                </div>
                                <div class="text-xs text-blue-600">
                                    Stock: <?php echo $inv_item['stock_quantity']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div id="no-quote-items-message" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No items found matching your search.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-quote-item-form" method="POST" action="?action=add_to_quote&quote_id=<?php echo $quote['id']; ?>" class="hidden" onsubmit="return validateQuoteFormSubmission()">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Selected Item:</h4>
                    <div id="selected-quote-item-display" class="text-sm text-gray-700"></div>
                </div>
                
                <input type="hidden" id="selected_quote_inventory_item_id" name="inventory_item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="quote_quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" min="1" id="quote_quantity" name="quantity" required value="1"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateQuoteTotalPreview(); if(selectedQuoteItemData && selectedQuoteItemData.generatesSerials) { loadQuoteAvailableSerials(); }">
                    </div>
                    <div>
                        <label for="quote_discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                        <input type="number" min="0" max="100" step="0.01" id="quote_discount_percentage" name="discount_percentage" value="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateQuoteTotalPreview()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Option</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="use_base_price" value="false" checked
                                       class="text-solar-blue border-gray-300 focus:ring-solar-blue"
                                       onchange="updateQuoteTotalPreview()">
                                <span class="ml-2 text-sm text-gray-700">Use Selling Price</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="use_base_price" value="true"
                                       class="text-solar-blue border-gray-300 focus:ring-solar-blue"
                                       onchange="updateQuoteTotalPreview()">
                                <span class="ml-2 text-sm text-gray-700">Use Base Price</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Serial Number Selection Section -->
                <div id="quote-serial-selection-section" class="mb-4 hidden">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Serial Number Selection</h4>
                    <div id="quote-available-serials" class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <!-- Serial numbers will be loaded here -->
                    </div>
                    <div id="quote-serial-selection-status" class="text-sm mt-2"></div>
                </div>
                
                <div id="quote-total-preview" class="bg-gray-50 border border-gray-200 dark:border-gray-600 rounded-lg p-3 mb-4 hidden">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal:</span>
                        <span id="quote-subtotal-amount">₱0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount:</span>
                        <span id="quote-discount-amount">₱0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between font-medium">
                        <span>Total:</span>
                        <span id="quote-total-amount">₱0.00</span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="clearQuoteSelection()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Clear Selection
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Add to Quote
                    </button>
                </div>
            </form>
            
            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('add-quote-item-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Direct Quote Item Modal -->
<div id="add-direct-quote-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Direct Quote Item</h3>
            <p class="text-sm text-gray-600 mb-4">Add items with custom quantities and prices while pulling item data from inventory.</p>
            
            <!-- Search and Filter Bar -->
            <div class="mb-4 space-y-3">
                <div>
                    <label for="direct-quote-item-search" class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                    <input type="text" id="direct-quote-item-search" placeholder="Search by brand, model, or category..."
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           onkeyup="filterDirectQuoteItems()">
                </div>
                <div>
                    <label for="direct-quote-category-filter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                    <select id="direct-quote-category-filter" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            onchange="filterDirectQuoteItems()">
                        <option value="">All Categories</option>
                        <?php 
                        $categories = array_unique(array_column($inventory_items, 'category_name'));
                        sort($categories);
                        foreach ($categories as $category): 
                        ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Items Grid -->
            <div id="direct-quote-items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 max-h-96 overflow-y-auto">
                <?php foreach ($inventory_items as $item): ?>
                <div class="direct-quote-item-card border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-500 hover:bg-green-50 transition"
                     data-item-id="<?php echo $item['id']; ?>"
                     data-brand="<?php echo htmlspecialchars($item['brand']); ?>"
                     data-model="<?php echo htmlspecialchars($item['model']); ?>"
                     data-category="<?php echo htmlspecialchars($item['category_name']); ?>"
                     data-generates-serials="<?php echo $item['generate_serials'] ? 'true' : 'false'; ?>"
                     data-serial-prefix="<?php echo htmlspecialchars($item['serial_prefix']); ?>"
                     data-selling-price="<?php echo $item['selling_price']; ?>"
                     data-base-price="<?php echo $item['base_price']; ?>"
                     data-image="<?php echo htmlspecialchars($item['image_path'] ?? ''); ?>"
                     onclick="selectDirectQuoteItem(this)">
                    <div class="flex gap-3 mb-3">
                        <!-- Item Image -->
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                            <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Item Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($item['brand']); ?></h4>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex-shrink-0 ml-2"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1 truncate"><?php echo htmlspecialchars($item['model']); ?></p>
                            <?php if (!empty($item['size_specification'])): ?>
                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($item['size_specification']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Price Information -->
                    <div class="mb-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">Selling Price:</span>
                            <span class="text-sm font-semibold text-green-600">₱<?php echo number_format($item['selling_price'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">Base Price:</span>
                            <span class="text-sm text-gray-600">₱<?php echo number_format($item['base_price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <!-- Serial Generation Badge -->
                    <?php if ($item['generate_serials']): ?>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-barcode mr-1"></i>Generates Serials
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- No Items Message -->
            <div id="direct-quote-no-items" class="hidden text-center py-8">
                <div class="text-gray-500">
                    <i class="fas fa-search text-4xl mb-4"></i>
                    <p>No items found matching your search.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-direct-quote-form" method="POST" action="?action=add_direct_quote_item&quote_id=<?php echo $quote['id']; ?>" class="hidden">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Selected Item:</h4>
                    <div id="selected-direct-quote-item-display" class="text-sm text-gray-700"></div>
                </div>
                
                <input type="hidden" id="selected_direct_quote_inventory_item_id" name="inventory_item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="direct_quote_quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" min="1" id="direct_quote_quantity" name="custom_quantity" required value="1"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               oninput="updateDirectQuoteTotalPreview()">
                    </div>
                    <div>
                        <label for="direct_quote_price" class="block text-sm font-medium text-gray-700 mb-2">Unit Price</label>
                        <input type="number" min="0" step="0.01" id="direct_quote_price" name="custom_price" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               oninput="updateDirectQuoteTotalPreview()">
                        <div class="mt-1 text-xs text-gray-500">
                            <span id="direct_quote_selling_price_info"></span>
                        </div>
                    </div>
                    <div>
                        <label for="direct_quote_discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                        <input type="number" min="0" max="100" step="0.01" id="direct_quote_discount_percentage" name="discount_percentage" value="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               oninput="updateDirectQuoteTotalPreview()">
                    </div>
                </div>
                
                <!-- Total Preview -->
                <div id="direct-quote-total-preview" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4 hidden">
                    <h4 class="font-medium text-gray-900 mb-2">Total Preview:</h4>
                    <div id="direct-quote-total-breakdown" class="text-sm text-gray-700"></div>
                </div>
                
                <!-- Serial Generation Notice -->
                <div id="direct-quote-serial-notice" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 hidden">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span class="text-sm text-blue-700">Serial numbers will be automatically generated for this item.</span>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="clearDirectQuoteSelection()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i>Add Direct Quote Item
                    </button>
                </div>
            </form>
            
            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('add-direct-quote-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Profit Breakdown Modal -->
<div id="profit-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Profit Breakdown - <?php echo htmlspecialchars($quote['quote_number']); ?></h3>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="printProfitBreakdown()" class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                    <button type="button" onclick="closeProfitModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div id="profit-content" class="mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                        <span class="text-blue-800">Loading profit data...</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeProfitModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

        <script>
let selectedQuoteItemData = null;
let currentQuoteCategoryFilter = '';

function filterQuoteItems() {
    const searchTerm = document.getElementById('quote-item-search').value.toLowerCase();
    const itemCards = document.querySelectorAll('.quote-item-card');
    let visibleCount = 0;
    
    itemCards.forEach(card => {
        const brand = card.getAttribute('data-brand');
        const model = card.getAttribute('data-model');
        const category = card.getAttribute('data-category');
        
        const matchesSearch = brand.includes(searchTerm) || 
                             model.includes(searchTerm) || 
                             category.includes(searchTerm);
        
        const matchesCategory = currentQuoteCategoryFilter === '' || category === currentQuoteCategoryFilter;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noItemsMessage = document.getElementById('no-quote-items-message');
    if (visibleCount === 0) {
        noItemsMessage.classList.remove('hidden');
    } else {
        noItemsMessage.classList.add('hidden');
    }
}

function filterByQuoteCategory(category) {
    currentQuoteCategoryFilter = category;
    
    // Update button states
    document.querySelectorAll('.quote-category-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-green-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
        
        // Restore original colors for category buttons
        if (btn.textContent.trim() !== 'All Items') {
            btn.classList.remove('bg-gray-100', 'text-gray-700');
            btn.classList.add('bg-green-100', 'text-green-700');
        }
    });
    
    // Highlight active button
    if (category === '') {
        // All Items button
        event.target.classList.remove('bg-gray-100', 'text-gray-700');
        event.target.classList.add('active', 'bg-green-600', 'text-white');
    } else {
        // Category button
        event.target.classList.remove('bg-green-100', 'text-green-700');
        event.target.classList.add('active', 'bg-green-600', 'text-white');
    }
    
    filterQuoteItems();
}

function selectQuoteItem(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.quote-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Mark current selection
    cardElement.classList.add('bg-blue-100', 'border-blue-500');
    
    // Store selected item data
    const dataGeneratesSerials = cardElement.getAttribute('data-generates-serials');
    selectedQuoteItemData = {
        id: cardElement.getAttribute('data-item-id'),
        brand: cardElement.querySelector('.text-sm.font-medium').textContent,
        model: cardElement.querySelector('.text-sm.text-gray-500').textContent,
        basePrice: parseFloat(cardElement.getAttribute('data-base-price')),
        sellingPrice: parseFloat(cardElement.getAttribute('data-price')),
        stock: parseInt(cardElement.getAttribute('data-stock')),
        generatesSerials: dataGeneratesSerials === '1'
    };
    
    // Update form
    document.getElementById('selected_quote_inventory_item_id').value = selectedQuoteItemData.id;
    document.getElementById('selected-quote-item-display').innerHTML = 
        `<strong>${selectedQuoteItemData.brand}</strong> - ${selectedQuoteItemData.model}<br>
         Base Price: ${formatCurrency(selectedQuoteItemData.basePrice)}<br>
         Selling Price: ${formatCurrency(selectedQuoteItemData.sellingPrice)}<br>
         Available: ${selectedQuoteItemData.stock}`;
    
    // Show form and update preview
    document.getElementById('add-quote-item-form').classList.remove('hidden');
    
    // Load serial numbers if item generates them and has stock
    if (selectedQuoteItemData.generatesSerials) {
        if (selectedQuoteItemData.stock > 0) {
            loadQuoteAvailableSerials();
        } else {
            // Show no stock available message
            const container = document.getElementById('quote-available-serials');
            const section = document.getElementById('quote-serial-selection-section');
            container.innerHTML = '<p class="text-red-500 text-sm font-medium">No stock available</p>';
            section.classList.remove('hidden');
        }
    } else {
        document.getElementById('quote-serial-selection-section').classList.add('hidden');
    }
    
    updateQuoteTotalPreview();
}

function clearQuoteSelection() {
    // Clear visual selection
    document.querySelectorAll('.quote-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Hide form
    document.getElementById('add-quote-item-form').classList.add('hidden');
    selectedQuoteItemData = null;
}

function loadQuoteAvailableSerials() {
    if (!selectedQuoteItemData || !selectedQuoteItemData.generatesSerials) {
        return;
    }
    
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 1;
    fetch(`get_available_serials.php?item_id=${selectedQuoteItemData.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayQuoteSerialSelection(data.serials, quantity);
            } else {
                console.error('Failed to load serials:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading serials:', error);
        });
}

function displayQuoteSerialSelection(serials, quantity) {
    const container = document.getElementById('quote-available-serials');
    const section = document.getElementById('quote-serial-selection-section');
    
    if (serials.length === 0) {
        container.innerHTML = '<p class="text-red-500 text-sm font-medium">No stock available</p>';
        section.classList.remove('hidden');
        return;
    }
    
    if (quantity > serials.length) {
        container.innerHTML = `<p class="text-red-500 text-sm">Only ${serials.length} serial numbers available, but ${quantity} requested</p>`;
        section.classList.add('hidden');
        return;
    }
    
    let html = '<div class="space-y-2">';
    
    // Add Select All button if quantity is less than or equal to available serials
    if (quantity <= serials.length) {
        html += `
            <div class="mb-3 pb-2 border-b border-gray-200">
                <button type="button" onclick="selectAllQuoteSerials(${quantity})" 
                        class="px-3 py-1 bg-solar-blue text-white text-sm rounded hover:bg-blue-700 transition-colors">
                    Select All (${quantity})
                </button>
                <button type="button" onclick="clearAllQuoteSerials()" 
                        class="ml-2 px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600 transition-colors">
                    Clear All
                </button>
            </div>
        `;
    }
    
    serials.forEach(serial => {
        html += `
            <label class="flex items-center">
                <input type="checkbox" name="selected_quote_serials[]" value="${serial.serial_number}" 
                       class="quote-serial-checkbox rounded border-gray-300 text-solar-blue focus:ring-solar-blue"
                       onchange="validateQuoteSerialSelection()">
                <span class="ml-2 text-sm font-mono">${serial.serial_number}</span>
            </label>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
    section.classList.remove('hidden');
    updateQuoteSubmitButtonState();
}

function validateQuoteSerialSelection() {
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.quote-serial-checkbox:checked');
    
    if (checkboxes.length > quantity) {
        alert(`You can only select ${quantity} serial number(s). Please uncheck some selections.`);
        checkboxes[checkboxes.length - 1].checked = false;
    }
    
    updateQuoteSubmitButtonState();
}

function selectAllQuoteSerials(maxQuantity) {
    const checkboxes = document.querySelectorAll('.quote-serial-checkbox');
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 1;
    const targetQuantity = Math.min(quantity, maxQuantity);
    
    // Clear all first
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Select up to the required quantity
    let selectedCount = 0;
    checkboxes.forEach(checkbox => {
        if (selectedCount < targetQuantity) {
            checkbox.checked = true;
            selectedCount++;
        }
    });
    
    updateQuoteSubmitButtonState();
}

function clearAllQuoteSerials() {
    const checkboxes = document.querySelectorAll('.quote-serial-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    updateQuoteSubmitButtonState();
}

function toggleSerials(serialId) {
    const serialDiv = document.getElementById(serialId);
    const iconId = serialId.replace('serials-', 'serials-icon-').replace('print-serials-', 'print-serials-icon-');
    const icon = document.getElementById(iconId);
    
    if (serialDiv.classList.contains('hidden')) {
        serialDiv.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        serialDiv.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function updateQuoteSubmitButtonState() {
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.quote-serial-checkbox:checked');
    const submitButton = document.querySelector('#add-quote-item-form button[type="submit"]');
    const serialSection = document.getElementById('quote-serial-selection-section');
    const statusElement = document.getElementById('quote-serial-selection-status');
    
    // Check if there's no stock available
    if (selectedQuoteItemData && selectedQuoteItemData.generatesSerials && selectedQuoteItemData.stock === 0) {
        if (statusElement) {
            statusElement.textContent = 'No stock available';
            statusElement.className = 'text-sm text-red-600 mt-2 font-medium';
        }
        submitButton.disabled = true;
        submitButton.textContent = 'No Stock Available';
        submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
        submitButton.classList.remove('bg-solar-blue', 'hover:bg-blue-800');
        return;
    }
    
    if (selectedQuoteItemData && selectedQuoteItemData.generatesSerials && !serialSection.classList.contains('hidden')) {
        const selectedCount = checkboxes.length;
        
        if (statusElement) {
            if (selectedCount === 0) {
                statusElement.textContent = `Please select ${quantity} serial number(s)`;
                statusElement.className = 'text-sm text-red-600 mt-2 font-medium';
            } else if (selectedCount < quantity) {
                statusElement.textContent = `Selected ${selectedCount} of ${quantity} serial number(s)`;
                statusElement.className = 'text-sm text-orange-600 mt-2 font-medium';
            } else if (selectedCount === quantity) {
                statusElement.textContent = `✓ Selected ${selectedCount} serial number(s) - Ready to add`;
                statusElement.className = 'text-sm text-green-600 mt-2 font-medium';
            } else {
                statusElement.textContent = `Too many selected (${selectedCount}/${quantity})`;
                statusElement.className = 'text-sm text-red-600 mt-2 font-medium';
            }
        }
        
        if (checkboxes.length !== quantity) {
            submitButton.disabled = true;
            submitButton.textContent = `Select ${quantity} Serial Number(s)`;
            submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            submitButton.classList.remove('bg-solar-blue', 'hover:bg-blue-800');
        } else {
            submitButton.disabled = false;
            submitButton.textContent = 'Add to Quote';
            submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            submitButton.classList.add('bg-solar-blue', 'hover:bg-blue-800');
        }
    } else {
        if (statusElement) {
            statusElement.textContent = '';
        }
        submitButton.disabled = false;
        submitButton.textContent = 'Add to Quote';
        submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
        submitButton.classList.add('bg-solar-blue', 'hover:bg-blue-800');
    }
}

function updateQuoteTotalPreview() {
    if (!selectedQuoteItemData) return;
    
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 0;
    const discountPercent = parseFloat(document.getElementById('quote_discount_percentage').value) || 0;
    const useBasePrice = document.querySelector('input[name="use_base_price"]:checked').value === 'true';
    
    const price = useBasePrice ? selectedQuoteItemData.basePrice : selectedQuoteItemData.sellingPrice;
    const subtotal = price * quantity;
    const discountAmount = subtotal * (discountPercent / 100);
    const total = subtotal - discountAmount;
    
    // Round all calculations to 2 decimal places
    const roundedSubtotal = Math.round(subtotal * 100) / 100;
    const roundedDiscountAmount = Math.round(discountAmount * 100) / 100;
    const roundedTotal = Math.round(total * 100) / 100;
    
    document.getElementById('quote-subtotal-amount').textContent = formatCurrency(roundedSubtotal);
    document.getElementById('quote-discount-amount').textContent = formatCurrency(roundedDiscountAmount);
    document.getElementById('quote-total-amount').textContent = formatCurrency(roundedTotal);
    
    document.getElementById('quote-total-preview').classList.remove('hidden');
    
    // Reload serials if quantity changed and item generates serials
    if (selectedQuoteItemData.generatesSerials) {
        loadQuoteAvailableSerials();
    }
    
    updateQuoteSubmitButtonState();
}

function validateQuoteFormSubmission() {
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.quote-serial-checkbox:checked');
    const serialSection = document.getElementById('quote-serial-selection-section');
    
    // Check if there's no stock available
    if (selectedQuoteItemData && selectedQuoteItemData.generatesSerials && selectedQuoteItemData.stock === 0) {
        alert('No stock available for this item. Please select a different item or update inventory.');
        return false;
    }
    
    if (selectedQuoteItemData && selectedQuoteItemData.generatesSerials && !serialSection.classList.contains('hidden')) {
        if (checkboxes.length !== quantity) {
            alert(`Please select exactly ${quantity} serial number(s). Currently selected: ${checkboxes.length}`);
            return false;
        }
    }
    
    return true;
}

function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Direct Quote Functions
let selectedDirectQuoteItemData = null;

function filterDirectQuoteItems() {
    const searchTerm = document.getElementById('direct-quote-item-search').value.toLowerCase();
    const categoryFilter = document.getElementById('direct-quote-category-filter').value;
    const items = document.querySelectorAll('.direct-quote-item-card');
    let visibleCount = 0;
    
    items.forEach(item => {
        const brand = item.dataset.brand.toLowerCase();
        const model = item.dataset.model.toLowerCase();
        const category = item.dataset.category.toLowerCase();
        
        const matchesSearch = brand.includes(searchTerm) || model.includes(searchTerm) || category.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter.toLowerCase();
        
        if (matchesSearch && matchesCategory) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    const noItemsMessage = document.getElementById('direct-quote-no-items');
    if (visibleCount === 0) {
        noItemsMessage.classList.remove('hidden');
    } else {
        noItemsMessage.classList.add('hidden');
    }
}

function selectDirectQuoteItem(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.direct-quote-item-card').forEach(card => {
        card.classList.remove('bg-green-100', 'border-green-500');
    });
    
    // Add selection styling
    cardElement.classList.add('bg-green-100', 'border-green-500');
    
    // Store selected item data
    selectedDirectQuoteItemData = {
        id: cardElement.dataset.itemId,
        brand: cardElement.dataset.brand,
        model: cardElement.dataset.model,
        category: cardElement.dataset.category,
        generatesSerials: cardElement.dataset.generatesSerials === 'true',
        serialPrefix: cardElement.dataset.serialPrefix,
        sellingPrice: parseFloat(cardElement.dataset.sellingPrice),
        basePrice: parseFloat(cardElement.dataset.basePrice),
        image: cardElement.dataset.image
    };
    
    // Update display with image
    const imageHtml = selectedDirectQuoteItemData.image ? 
        `<img src="${selectedDirectQuoteItemData.image}" alt="${selectedDirectQuoteItemData.brand} ${selectedDirectQuoteItemData.model}" class="w-12 h-12 object-cover rounded mr-3">` : 
        `<div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center mr-3"><i class="fas fa-image text-gray-400"></i></div>`;
    
    document.getElementById('selected-direct-quote-item-display').innerHTML = `
        <div class="flex items-center">
            ${imageHtml}
            <div>
                <strong>${selectedDirectQuoteItemData.brand} ${selectedDirectQuoteItemData.model}</strong><br>
                <span class="text-sm text-gray-600">Category: ${selectedDirectQuoteItemData.category}</span><br>
                <span class="text-sm text-green-600">Selling Price: ₱${selectedDirectQuoteItemData.sellingPrice.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span><br>
                <span class="text-xs text-gray-500">${selectedDirectQuoteItemData.generatesSerials ? 'Generates Serial Numbers' : 'No Serial Numbers'}</span>
            </div>
        </div>
    `;
    
    // Set hidden input
    document.getElementById('selected_direct_quote_inventory_item_id').value = selectedDirectQuoteItemData.id;
    
    // Pre-fill price with selling price
    document.getElementById('direct_quote_price').value = selectedDirectQuoteItemData.sellingPrice;
    
    // Update price info
    document.getElementById('direct_quote_selling_price_info').innerHTML = 
        `Current selling price: ₱${selectedDirectQuoteItemData.sellingPrice.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
    
    // Show form
    document.getElementById('add-direct-quote-form').classList.remove('hidden');
    
    // Show serial notice if applicable
    const serialNotice = document.getElementById('direct-quote-serial-notice');
    if (selectedDirectQuoteItemData.generatesSerials) {
        serialNotice.classList.remove('hidden');
    } else {
        serialNotice.classList.add('hidden');
    }
    
    // Update total preview
    updateDirectQuoteTotalPreview();
}

function clearDirectQuoteSelection() {
    // Remove selection styling
    document.querySelectorAll('.direct-quote-item-card').forEach(card => {
        card.classList.remove('bg-green-100', 'border-green-500');
    });
    
    // Reset form fields
    document.getElementById('direct_quote_price').value = '';
    document.getElementById('direct_quote_quantity').value = '1';
    document.getElementById('direct_quote_discount_percentage').value = '0';
    document.getElementById('direct_quote_selling_price_info').innerHTML = '';
    
    // Hide form
    document.getElementById('add-direct-quote-form').classList.add('hidden');
    selectedDirectQuoteItemData = null;
}

function updateDirectQuoteTotalPreview() {
    if (!selectedDirectQuoteItemData) return;
    
    const quantity = parseInt(document.getElementById('direct_quote_quantity').value) || 0;
    const price = parseFloat(document.getElementById('direct_quote_price').value) || 0;
    const discountPercent = parseFloat(document.getElementById('direct_quote_discount_percentage').value) || 0;
    
    const subtotal = price * quantity;
    const discountAmount = subtotal * (discountPercent / 100);
    const total = subtotal - discountAmount;
    
    // Round all calculations to 2 decimal places
    const roundedSubtotal = Math.round(subtotal * 100) / 100;
    const roundedDiscountAmount = Math.round(discountAmount * 100) / 100;
    const roundedTotal = Math.round(total * 100) / 100;
    
    document.getElementById('direct-quote-total-breakdown').innerHTML = `
        <div class="flex justify-between">
            <span>Subtotal (${quantity} × ${formatCurrency(price)}):</span>
            <span>${formatCurrency(roundedSubtotal)}</span>
        </div>
        <div class="flex justify-between">
            <span>Discount (${discountPercent}%):</span>
            <span>-${formatCurrency(roundedDiscountAmount)}</span>
        </div>
        <div class="flex justify-between font-semibold border-t pt-2 mt-2">
            <span>Total:</span>
            <span>${formatCurrency(roundedTotal)}</span>
        </div>
    `;
    
    document.getElementById('direct-quote-total-preview').classList.remove('hidden');
}

// Profit Modal Functions
function showProfitModal() {
    const modal = document.getElementById('profit-modal');
    modal.classList.remove('hidden');
    loadProfitData();
}

function closeProfitModal() {
    const modal = document.getElementById('profit-modal');
    modal.classList.add('hidden');
}

function loadProfitData() {
    const quote_id = <?php echo $quote['id']; ?>;
    const contentDiv = document.getElementById('profit-content');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                <span class="text-blue-800">Loading profit data...</span>
            </div>
        </div>
    `;
    
    // Make AJAX request to get profit data
    fetch(`quotations.php?action=get_profit_data&quote_id=${quote_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProfitData(data.profit_data);
            } else {
                showProfitError(data.message || 'Failed to load profit data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showProfitError('Network error occurred while loading profit data');
        });
}

function displayProfitData(profitData) {
    const contentDiv = document.getElementById('profit-content');
    
    let totalBaseCost = 0;
    let totalSellingPrice = 0;
    let totalProfit = 0;
    let totalProfitAfterDiscount = 0;
    
    let itemsHtml = '';
    
    profitData.items.forEach(item => {
        // Set base cost to 0 for Labor Fee items
        const isLaborItem = item.brand && item.brand.toLowerCase().includes('labor');
        const baseCost = isLaborItem ? 0 : (item.base_price * item.quantity);
        const sellingPrice = item.unit_price * item.quantity;
        const profit = sellingPrice - baseCost;
        const profitAfterDiscount = item.total_amount - baseCost;
        const profitMargin = baseCost > 0 ? ((profit / baseCost) * 100) : 0;
        const profitMarginAfterDiscount = baseCost > 0 ? ((profitAfterDiscount / baseCost) * 100) : 0;
        
        // Round all calculations to 2 decimal places
        const roundedBaseCost = Math.round(baseCost * 100) / 100;
        const roundedSellingPrice = Math.round(sellingPrice * 100) / 100;
        const roundedProfit = Math.round(profit * 100) / 100;
        const roundedProfitAfterDiscount = Math.round(profitAfterDiscount * 100) / 100;
        const roundedProfitMargin = Math.round(profitMargin * 10) / 10;
        const roundedProfitMarginAfterDiscount = Math.round(profitMarginAfterDiscount * 10) / 10;
        
        totalBaseCost += roundedBaseCost;
        totalSellingPrice += roundedSellingPrice;
        totalProfit += roundedProfit;
        totalProfitAfterDiscount += roundedProfitAfterDiscount;
        
        itemsHtml += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b">
                    <div class="text-sm font-medium text-gray-900">${item.brand} ${item.model}</div>
                    <div class="text-xs text-gray-500">${item.size_specification || ''}</div>
                </td>
                <td class="px-3 py-3 border-b text-center text-sm">${item.quantity}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(isLaborItem ? 0 : item.base_price)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(item.unit_price)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(roundedBaseCost)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(roundedSellingPrice)}</td>
                <td class="px-3 py-3 border-b text-sm text-right font-medium ${roundedProfit >= 0 ? 'text-green-600' : 'text-red-600'}">
                    ${formatCurrency(roundedProfit)}
                    <div class="text-xs ${roundedProfitMargin >= 0 ? 'text-green-500' : 'text-red-500'}">(${roundedProfitMargin.toFixed(1)}%)</div>
                </td>
                <td class="px-3 py-3 border-b text-sm text-center">
                    <span class="text-xs ${item.discount_percentage > 0 ? 'text-orange-600' : 'text-gray-400'}">${item.discount_percentage}%</span>
                </td>
                <td class="px-3 py-3 border-b text-sm text-right font-medium ${roundedProfitAfterDiscount >= 0 ? 'text-green-600' : 'text-red-600'}">
                    ${formatCurrency(roundedProfitAfterDiscount)}
                    <div class="text-xs ${roundedProfitMarginAfterDiscount >= 0 ? 'text-green-500' : 'text-red-500'}">(${roundedProfitMarginAfterDiscount.toFixed(1)}%)</div>
                </td>
            </tr>
        `;
    });
    
    const overallProfitMargin = totalBaseCost > 0 ? ((totalProfit / totalBaseCost) * 100) : 0;
    const overallProfitMarginAfterDiscount = totalBaseCost > 0 ? ((totalProfitAfterDiscount / totalBaseCost) * 100) : 0;
    
    // Round overall totals to 2 decimal places
    const roundedTotalBaseCost = Math.round(totalBaseCost * 100) / 100;
    const roundedTotalSellingPrice = Math.round(totalSellingPrice * 100) / 100;
    const roundedTotalProfit = Math.round(totalProfit * 100) / 100;
    const roundedTotalProfitAfterDiscount = Math.round(totalProfitAfterDiscount * 100) / 100;
    const roundedOverallProfitMargin = Math.round(overallProfitMargin * 10) / 10;
    const roundedOverallProfitMarginAfterDiscount = Math.round(overallProfitMarginAfterDiscount * 10) / 10;
    
    contentDiv.innerHTML = `
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-800">Total Base Cost</div>
                <div class="text-xl font-bold text-blue-900">${formatCurrency(roundedTotalBaseCost)}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-sm font-medium text-green-800">Total Selling Price</div>
                <div class="text-xl font-bold text-green-900">${formatCurrency(roundedTotalSellingPrice)}</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-800">Gross Profit</div>
                <div class="text-xl font-bold ${roundedTotalProfit >= 0 ? 'text-purple-900' : 'text-red-600'}">${formatCurrency(roundedTotalProfit)}</div>
                <div class="text-sm ${roundedOverallProfitMargin >= 0 ? 'text-purple-600' : 'text-red-500'}">(${roundedOverallProfitMargin.toFixed(1)}% margin)</div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="text-sm font-medium text-orange-800">Net Profit (After Discounts)</div>
                <div class="text-xl font-bold ${roundedTotalProfitAfterDiscount >= 0 ? 'text-orange-900' : 'text-red-600'}">${formatCurrency(roundedTotalProfitAfterDiscount)}</div>
                <div class="text-sm ${roundedOverallProfitMarginAfterDiscount >= 0 ? 'text-orange-600' : 'text-red-500'}">(${roundedOverallProfitMarginAfterDiscount.toFixed(1)}% margin)</div>
            </div>
        </div>
        
        <!-- Detailed Item Breakdown -->
        <div class="bg-white border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                <h4 class="text-sm font-medium text-gray-900">Item-wise Profit Breakdown</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base Price</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selling Price</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Base Cost</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Selling</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Discount</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        ${itemsHtml}
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr class="font-medium">
                            <td class="px-4 py-3 text-sm font-bold text-gray-900" colspan="4">TOTAL</td>
                            <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">${formatCurrency(roundedTotalBaseCost)}</td>
                            <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">${formatCurrency(roundedTotalSellingPrice)}</td>
                            <td class="px-3 py-3 text-sm font-bold ${roundedTotalProfit >= 0 ? 'text-green-600' : 'text-red-600'} text-right">
                                ${formatCurrency(roundedTotalProfit)}
                                <div class="text-xs ${roundedOverallProfitMargin >= 0 ? 'text-green-500' : 'text-red-500'}">(${roundedOverallProfitMargin.toFixed(1)}%)</div>
                            </td>
                            <td class="px-3 py-3 text-sm text-center">-</td>
                            <td class="px-3 py-3 text-sm font-bold ${roundedTotalProfitAfterDiscount >= 0 ? 'text-orange-600' : 'text-red-600'} text-right">
                                ${formatCurrency(roundedTotalProfitAfterDiscount)}
                                <div class="text-xs ${roundedOverallProfitMarginAfterDiscount >= 0 ? 'text-orange-500' : 'text-red-500'}">(${roundedOverallProfitMarginAfterDiscount.toFixed(1)}%)</div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex">
                <i class="fas fa-info-circle text-yellow-600 mr-2 mt-0.5"></i>
                <div class="text-sm text-yellow-800">
                    <strong>Note:</strong> Gross Profit = Selling Price - Base Price. Net Profit accounts for discounts applied to individual items.
                </div>
            </div>
        </div>
    `;
}

function showProfitError(message) {
    const contentDiv = document.getElementById('profit-content');
    contentDiv.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                <span class="text-red-800">${message}</span>
            </div>
        </div>
    `;
}

function printProfitBreakdown() {
    const profitContent = document.getElementById('profit-content');
    const quoteNumber = '<?php echo htmlspecialchars($quote['quote_number']); ?>';
    const currentDate = new Date().toLocaleDateString();
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Get the current profit data by making an AJAX call
    const quote_id = <?php echo $quote['id']; ?>;
    
    fetch(`quotations.php?action=get_profit_data&quote_id=${quote_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Generate the print content
                const printContent = generatePrintContent(data.profit_data, quoteNumber, currentDate);
                printWindow.document.write(printContent);
                printWindow.document.close();
                
                // Wait for content to load, then print
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            } else {
                alert('Failed to load profit data for printing');
                printWindow.close();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading profit data for printing');
            printWindow.close();
        });
}

function generatePrintContent(profitData, quoteNumber, currentDate) {
    let totalBaseCost = 0;
    let totalSellingPrice = 0;
    let totalProfit = 0;
    let totalProfitAfterDiscount = 0;
    
    let itemsHtml = '';
    
    profitData.items.forEach(item => {
        const isLaborItem = item.brand && item.brand.toLowerCase().includes('labor');
        const baseCost = isLaborItem ? 0 : (item.base_price * item.quantity);
        const sellingPrice = item.unit_price * item.quantity;
        const profit = sellingPrice - baseCost;
        const profitAfterDiscount = item.total_amount - baseCost;
        const profitMargin = baseCost > 0 ? ((profit / baseCost) * 100) : 0;
        const profitMarginAfterDiscount = baseCost > 0 ? ((profitAfterDiscount / baseCost) * 100) : 0;
        
        const roundedBaseCost = Math.round(baseCost * 100) / 100;
        const roundedSellingPrice = Math.round(sellingPrice * 100) / 100;
        const roundedProfit = Math.round(profit * 100) / 100;
        const roundedProfitAfterDiscount = Math.round(profitAfterDiscount * 100) / 100;
        const roundedProfitMargin = Math.round(profitMargin * 10) / 10;
        const roundedProfitMarginAfterDiscount = Math.round(profitMarginAfterDiscount * 10) / 10;
        
        totalBaseCost += roundedBaseCost;
        totalSellingPrice += roundedSellingPrice;
        totalProfit += roundedProfit;
        totalProfitAfterDiscount += roundedProfitAfterDiscount;
        
        itemsHtml += `
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <div style="font-weight: 500; color: #111;">${item.brand} ${item.model}</div>
                    <div style="font-size: 12px; color: #666;">${item.size_specification || ''}</div>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">${item.quantity}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">${formatCurrency(isLaborItem ? 0 : item.base_price)}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">${formatCurrency(item.unit_price)}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">${formatCurrency(roundedBaseCost)}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">${formatCurrency(roundedSellingPrice)}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right; font-weight: 500; color: ${roundedProfit >= 0 ? '#059669' : '#dc2626'};">
                    ${formatCurrency(roundedProfit)}
                    <div style="font-size: 11px; color: ${roundedProfitMargin >= 0 ? '#10b981' : '#ef4444'};">(${roundedProfitMargin.toFixed(1)}%)</div>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                    <span style="font-size: 11px; color: ${item.discount_percentage > 0 ? '#ea580c' : '#9ca3af'};">${item.discount_percentage}%</span>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right; font-weight: 500; color: ${roundedProfitAfterDiscount >= 0 ? '#059669' : '#dc2626'};">
                    ${formatCurrency(roundedProfitAfterDiscount)}
                    <div style="font-size: 11px; color: ${roundedProfitMarginAfterDiscount >= 0 ? '#10b981' : '#ef4444'};">(${roundedProfitMarginAfterDiscount.toFixed(1)}%)</div>
                </td>
            </tr>
        `;
    });
    
    const overallProfitMargin = totalBaseCost > 0 ? ((totalProfit / totalBaseCost) * 100) : 0;
    const overallProfitMarginAfterDiscount = totalBaseCost > 0 ? ((totalProfitAfterDiscount / totalBaseCost) * 100) : 0;
    
    const roundedTotalBaseCost = Math.round(totalBaseCost * 100) / 100;
    const roundedTotalSellingPrice = Math.round(totalSellingPrice * 100) / 100;
    const roundedTotalProfit = Math.round(totalProfit * 100) / 100;
    const roundedTotalProfitAfterDiscount = Math.round(totalProfitAfterDiscount * 100) / 100;
    const roundedOverallProfitMargin = Math.round(overallProfitMargin * 10) / 10;
    const roundedOverallProfitMarginAfterDiscount = Math.round(overallProfitMarginAfterDiscount * 10) / 10;
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Profit Breakdown - ${quoteNumber}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #1f2937; margin: 0; font-size: 24px; }
                .header p { color: #6b7280; margin: 5px 0; }
                .summary-cards { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; }
                .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; flex: 1; margin: 0 5px; text-align: center; }
                .summary-card h3 { margin: 0 0 8px 0; font-size: 14px; color: #4b5563; }
                .summary-card .amount { font-size: 18px; font-weight: bold; color: #1f2937; }
                .summary-card .margin { font-size: 12px; color: #6b7280; margin-top: 4px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #f3f4f6; padding: 12px 8px; text-align: left; font-weight: 600; font-size: 12px; color: #374151; border-bottom: 1px solid #d1d5db; }
                td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .font-bold { font-weight: bold; }
                .note { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 12px; margin-top: 20px; }
                .note strong { color: #92400e; }
                .note-text { color: #92400e; font-size: 14px; }
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    .print-content, .print-content * {
                        visibility: visible;
                    }
                    .print-content {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        margin: 0;
                        padding: 0;
                    }
                    
                    /* Remove browser print headers and footers */
                    @page {
                        margin: 0.5in;
                        size: A4;
                    }
                    
                    /* Hide URL and other browser print info */
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-content">
                <div class="header">
                    <h1>Profit Breakdown Report</h1>
                    <p><strong>Quote Number:</strong> ${quoteNumber}</p>
                    <p><strong>Generated:</strong> ${currentDate}</p>
                </div>
            
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Base Cost</h3>
                    <div class="amount">${formatCurrency(roundedTotalBaseCost)}</div>
                </div>
                <div class="summary-card">
                    <h3>Total Selling Price</h3>
                    <div class="amount">${formatCurrency(roundedTotalSellingPrice)}</div>
                </div>
                <div class="summary-card">
                    <h3>Gross Profit</h3>
                    <div class="amount" style="color: ${roundedTotalProfit >= 0 ? '#059669' : '#dc2626'};">
                        ${formatCurrency(roundedTotalProfit)}
                    </div>
                    <div class="margin">(${roundedOverallProfitMargin.toFixed(1)}% margin)</div>
                </div>
                <div class="summary-card">
                    <h3>Net Profit (After Discounts)</h3>
                    <div class="amount" style="color: ${roundedTotalProfitAfterDiscount >= 0 ? '#ea580c' : '#dc2626'};">
                        ${formatCurrency(roundedTotalProfitAfterDiscount)}
                    </div>
                    <div class="margin">(${roundedOverallProfitMarginAfterDiscount.toFixed(1)}% margin)</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Base Price</th>
                        <th class="text-right">Selling Price</th>
                        <th class="text-right">Total Base Cost</th>
                        <th class="text-right">Total Selling</th>
                        <th class="text-right">Gross Profit</th>
                        <th class="text-center">Discount</th>
                        <th class="text-right">Net Profit</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
                <tfoot style="background: #f3f4f6; border-top: 2px solid #d1d5db;">
                    <tr class="font-bold">
                        <td colspan="4">TOTAL</td>
                        <td class="text-right">${formatCurrency(roundedTotalBaseCost)}</td>
                        <td class="text-right">${formatCurrency(roundedTotalSellingPrice)}</td>
                        <td class="text-right" style="color: ${roundedTotalProfit >= 0 ? '#059669' : '#dc2626'};">
                            ${formatCurrency(roundedTotalProfit)}
                            <div style="font-size: 11px; color: ${roundedOverallProfitMargin >= 0 ? '#10b981' : '#ef4444'};">(${roundedOverallProfitMargin.toFixed(1)}%)</div>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-right" style="color: ${roundedTotalProfitAfterDiscount >= 0 ? '#ea580c' : '#dc2626'};">
                            ${formatCurrency(roundedTotalProfitAfterDiscount)}
                            <div style="font-size: 11px; color: ${roundedOverallProfitMarginAfterDiscount >= 0 ? '#f59e0b' : '#ef4444'};">(${roundedOverallProfitMarginAfterDiscount.toFixed(1)}%)</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
            
                <div class="note">
                    <strong>Note:</strong> <span class="note-text">Gross Profit = Selling Price - Base Price. Net Profit accounts for discounts applied to individual items.</span>
                </div>
            </div>
        </body>
        </html>
    `;
}
</script>

<?php elseif ($action == 'order_fulfillment' && isset($quote)): ?>
<!-- Order Fulfillment Checklist Screen -->
<div class="mb-6 order-fulfillment-page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Order Fulfillment Checklist</h1>
            <p class="text-gray-600 dark:text-gray-400">
                Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?>
                <?php if (!empty($quote['project_number'])): ?>
                <br>Project Number: <span class="font-semibold"><?php echo htmlspecialchars($quote['project_number']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="space-x-2">
            <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Checklist
            </button>
        </div>
    </div>
</div>

<!-- Order Fulfillment Form -->
<div id="order-fulfillment-form-container" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 print:shadow-none print:p-0">
    <form id="fulfillment-form" method="POST" action="">
        <!-- Header Section -->
        <div class="text-center mb-8 border-b-2 border-gray-300 pb-4">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">Order Fulfillment Checklist</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Date: <?php echo date('Y-m-d'); ?><br>
                Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?>
                <?php if (!empty($quote['project_number'])): ?>
                <br>Project Number: <strong><?php echo htmlspecialchars($quote['project_number']); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Code:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['quote_number']); ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['customer_name']); ?></p>
                </div>
            </div>
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location:</label>
                    <p class="text-gray-900"><?php echo $customer_info && $customer_info['address'] ? htmlspecialchars($customer_info['address']) : 'Not specified'; ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number:</label>
                    <p class="text-gray-900"><?php echo $quote['customer_phone'] ? htmlspecialchars($quote['customer_phone']) : ($customer_info && $customer_info['phone_number'] ? htmlspecialchars($customer_info['phone_number']) : 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Items Checklist Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            No.
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Description
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Check
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($quote['items'])): ?>
                        <?php $item_no = 1; ?>
                        <?php foreach ($quote['items'] as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium"><?php echo htmlspecialchars($item['brand'] ?? 'Custom Item'); ?></div>
                                <div class="text-gray-500"><?php echo htmlspecialchars($item['model'] ?? $item['custom_item_name'] ?? ''); ?></div>
                                <?php if (!empty($item['category'])): ?>
                                <div class="text-xs text-blue-600 mt-1"><?php echo htmlspecialchars($item['category']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['serial_numbers'])): ?>
                                <div class="text-xs text-green-600 mt-1">
                                    <button type="button" onclick="toggleSerials('print-serials-<?php echo $item['id']; ?>')" 
                                            class="text-green-600 hover:text-green-800 font-medium underline">
                                        <i class="fas fa-chevron-down" id="print-serials-icon-<?php echo $item['id']; ?>"></i>
                                        Serials (<?php echo substr_count($item['serial_numbers'], ',') + 1; ?>)
                                    </button>
                                    <div id="print-serials-<?php echo $item['id']; ?>" class="hidden mt-1 text-xs text-gray-600 font-mono bg-gray-50 p-2 rounded border">
                                        <?php 
                                        $serials = explode(',', $item['serial_numbers']);
                                        foreach ($serials as $serial): 
                                        ?>
                                        <div class="mb-1"><?php echo htmlspecialchars(trim($serial)); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="item_checked[]" value="<?php echo isset($item['quote_item_id']) ? $item['quote_item_id'] : $item['id']; ?>" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo number_format($item['quantity'], 0); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Static Extra Items for Fulfillment Checklist -->
                        <tr class="bg-yellow-50 border-t-2 border-yellow-300">
                            <td colspan="4" class="px-4 py-2 text-center text-sm font-semibold text-yellow-800">
                                <strong>EXTRA ITEMS FOR FULFILLMENT</strong>
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Grinder Disc 2pcs</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="grinder_disc_2" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Rivets</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="rivets" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Reviter</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="reviter" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Anchor bolt 3/8 10pcs</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="anchor_bolt_3_8_10pcs" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Barena bala</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="barena_bala" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Screw 2 inches 20pcs</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="screw_2inches_20" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Stuckers</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="stuckers" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Solar Out</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="solar_out" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Deye AC IN</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="deye_ac_in" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        <tr class="bg-yellow-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium">Battery Inverter</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="static_item_checked[]" value="battery_inverter" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                
                            </td>
                        </tr>
                        
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                No items found in this quotation.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Section -->
        <div class="mt-8 pt-6 border-t-2 border-gray-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Notes:</h4>
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">All items checked and verified</span>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Customer signature obtained</span>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Delivery completed</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Signatures:</h4>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">Prepared By:</label>
                            <div class="border-b-2 border-gray-300 h-12"></div>
                            <p class="text-xs text-gray-500 mt-1">Name & Signature</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">Customer Signature:</label>
                            <div class="border-b-2 border-gray-300 h-12"></div>
                            <p class="text-xs text-gray-500 mt-1">Name & Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons (Hidden when printing) -->
        <div class="mt-8 pt-6 border-t flex justify-between print:hidden">
            <button type="button" onclick="checkAllItems()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-check-double mr-2"></i>Check All Items
            </button>
            <div class="space-x-2">
                <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-print mr-2"></i>Print Checklist
                </button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Progress
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function checkAllItems() {
    const checkboxes = document.querySelectorAll('input[name="item_checked[]"]');
    const staticCheckboxes = document.querySelectorAll('input[name="static_item_checked[]"]');
    const allCheckboxes = [...checkboxes, ...staticCheckboxes];
    const allChecked = allCheckboxes.every(cb => cb.checked);
    
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Auto-save functionality
document.getElementById('fulfillment-form').addEventListener('change', function() {
    // Could implement auto-save functionality here
    console.log('Form changed - could auto-save progress');
});
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:shadow-none, .print\:shadow-none * {
        visibility: visible;
    }
    .print\:shadow-none {
        position: absolute;
        left: 0;
        top: 0;
    }
    
    /* Remove browser print headers and footers */
    @page {
        margin: 0.5in;
        size: A4;
    }
    
    /* Hide URL and other browser print info */
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Ensure clean print layout */
    .print\:shadow-none {
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>

<?php elseif ($action == 'purchase_order' && isset($quote)): ?>
<!-- Purchase Order Screen -->
<div class="mb-6 purchase-order-page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Purchase Order</h1>
            <p class="text-gray-600 dark:text-gray-400">
                Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?>
                <?php if (!empty($quote['project_number'])): ?>
                <br>Project Number: <span class="font-semibold"><?php echo htmlspecialchars($quote['project_number']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="space-x-2">
            <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Purchase Order
            </button>
        </div>
    </div>
</div>

<!-- Purchase Order Form -->
<div id="purchase-order-form-container" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 print:shadow-none print:p-0">
    <?php if (isset($message)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <form id="purchase-order-form" method="POST" action="">
        <input type="hidden" name="action" value="create_purchase_order">
        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
        
        <!-- Header Section -->
        <div class="text-center mb-8 border-b-2 border-gray-300 pb-4">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">Purchase Order</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Date: <?php echo date('Y-m-d'); ?><br>
                Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?>
                <?php if (!empty($quote['project_number'])): ?>
                <br>Project Number: <strong><?php echo htmlspecialchars($quote['project_number']); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Code:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['quote_number']); ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['customer_name']); ?></p>
                </div>
            </div>
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location:</label>
                    <p class="text-gray-900"><?php echo $customer_info && $customer_info['address'] ? htmlspecialchars($customer_info['address']) : 'Not specified'; ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number:</label>
                    <p class="text-gray-900"><?php echo $quote['customer_phone'] ? htmlspecialchars($quote['customer_phone']) : ($customer_info && $customer_info['phone_number'] ? htmlspecialchars($customer_info['phone_number']) : 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Items to Purchase Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            No.
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Description
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Order
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Quantity
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Unit Amount
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Amount
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($quote['items'])): ?>
                        <?php $item_no = 1; ?>
                        <?php foreach ($quote['items'] as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium"><?php echo htmlspecialchars($item['brand'] ?? 'Custom Item'); ?></div>
                                <div class="text-gray-500"><?php echo htmlspecialchars($item['model'] ?? $item['custom_item_name'] ?? ''); ?></div>
                                <?php if (!empty($item['category'])): ?>
                                <div class="text-xs text-blue-600 mt-1"><?php echo htmlspecialchars($item['category']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['serial_numbers'])): ?>
                                <div class="text-xs text-green-600 mt-1">
                                    <button type="button" onclick="toggleSerials('po-serials-<?php echo $item['id']; ?>')" 
                                            class="text-green-600 hover:text-green-800 font-medium underline">
                                        <i class="fas fa-chevron-down" id="po-serials-icon-<?php echo $item['id']; ?>"></i>
                                        Serials (<?php echo substr_count($item['serial_numbers'], ',') + 1; ?>)
                                    </button>
                                    <div id="po-serials-<?php echo $item['id']; ?>" class="hidden mt-1 text-xs text-gray-600 font-mono bg-gray-50 p-2 rounded border">
                                        <?php 
                                        $serials = explode(',', $item['serial_numbers']);
                                        foreach ($serials as $serial): 
                                        ?>
                                        <div class="mb-1"><?php echo htmlspecialchars(trim($serial)); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="item_to_order[]" value="<?php echo isset($item['quote_item_id']) ? $item['quote_item_id'] : $item['id']; ?>" 
                                       class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 print:hidden" checked>
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400 bg-orange-100"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r border-gray-300">
                                <?php echo number_format($item['quantity'], 0); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-gray-900 border-r border-gray-300">
                                <?php echo formatCurrency($item['unit_price']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                <?php echo formatCurrency($item['total_amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Total Row -->
                        <tr class="bg-gray-50 font-semibold border-t-2 border-gray-400">
                            <td colspan="5" class="px-4 py-4 text-right text-sm text-gray-900 border-r border-gray-300">
                                <strong>Grand Total:</strong>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-lg font-bold text-orange-600">
                                <?php echo formatCurrency($quote['total_amount']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No items found in this quotation.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Supplier Information -->
        <div class="mt-8 pt-6 border-t-2 border-gray-300">
            <h4 class="text-lg font-medium text-gray-700 mb-4">Supplier Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Name:</label>
                    <input type="text" name="supplier_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter supplier name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person:</label>
                    <input type="text" name="contact_person" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter contact person">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number:</label>
                    <input type="text" name="supplier_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter phone number">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                    <input type="email" name="supplier_email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter email address">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Address:</label>
                <textarea name="supplier_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter supplier address"></textarea>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="mt-8 pt-6 border-t-2 border-gray-300">
            <h4 class="text-lg font-medium text-gray-700 mb-4">Purchase Order Notes</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions:</label>
                    <textarea name="special_instructions" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter any special instructions for the supplier"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Requirements:</label>
                    <textarea name="delivery_requirements" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter delivery requirements and timeline"></textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 pt-6 border-t flex justify-between">
            <button type="button" onclick="selectAllItems()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-check-double mr-2"></i>Select All Items
            </button>
            <div class="space-x-2">
                <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-print mr-2"></i>Print Purchase Order
                </button>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-save mr-2"></i>Create Purchase Order
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function selectAllItems() {
    const checkboxes = document.querySelectorAll('input[name="item_to_order[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

function toggleSerials(elementId) {
    const element = document.getElementById(elementId);
    const icon = document.getElementById(elementId.replace('po-serials-', 'po-serials-icon-'));
    
    if (element.classList.contains('hidden')) {
        element.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        element.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:shadow-none, .print\:shadow-none * {
        visibility: visible;
    }
    .print\:shadow-none {
        position: absolute;
        left: 0;
        top: 0;
    }
    
    /* Remove browser print headers and footers */
    @page {
        margin: 0.5in;
        size: A4;
    }
    
    /* Hide URL and other browser print info */
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Ensure clean print layout */
    .print\:shadow-none {
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>

<?php elseif ($action == 'installments' && isset($quote)): ?>
<!-- Installment Management Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Payment Plan: <?php echo htmlspecialchars($quote['quote_number']); ?></h1>
            <p class="text-gray-600 dark:text-gray-400">
                Customer: <?php echo htmlspecialchars($quote['customer_name']); ?>
                | Total Amount: <?php echo formatCurrency($quote['total_amount']); ?>
            </p>
        </div>
        <div class="space-x-2">
            <a href="print_installments.php?quote_id=<?php echo $quote_id; ?>" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Plan
            </a>
        </div>
    </div>
</div>

<?php if ($installment_plan): ?>
<!-- Existing Installment Plan -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Plan Summary Cards -->
    <div class="lg:col-span-3">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-800">Plan Status</div>
                <div class="text-xl font-bold text-blue-900"><?php echo ucfirst($installment_plan['status']); ?></div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-sm font-medium text-green-800">Total Paid</div>
                <div class="text-xl font-bold text-green-900"><?php echo formatCurrency($installment_plan['summary']['total_paid'] ?? 0); ?></div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-800">Remaining to Pay</div>
                <div class="text-xl font-bold text-purple-900"><?php echo formatCurrency($installment_plan['summary']['total_remaining'] ?? 0); ?></div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="text-sm font-medium text-orange-800">Pending</div>
                <div class="text-xl font-bold text-orange-900"><?php echo formatCurrency($installment_plan['summary']['pending_amount'] ?? 0); ?></div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-sm font-medium text-red-800">Overdue</div>
                <div class="text-xl font-bold text-red-900"><?php echo formatCurrency($installment_plan['summary']['overdue_amount'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Plan Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Payment Schedule -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Payment Schedule</h2>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                    <div class="text-sm text-blue-800">
                        <strong>Overpayment Feature:</strong> If you pay more than the required amount, the extra payment will automatically be applied to future installments, reducing your remaining balance.
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Remaining</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($installment_plan['payments'] as $payment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900"><?php echo $payment['installment_number']; ?></td>
                            <td class="px-4 py-4 text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($payment['due_date'])); ?>
                                <?php if ($payment['status'] === 'pending' && strtotime($payment['due_date']) < time()): ?>
                                <span class="text-xs text-red-600 block">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-right text-gray-900">
                                <?php echo formatCurrency($payment['due_amount']); ?>
                                <?php if ($payment['late_fee_applied'] > 0): ?>
                                <div class="text-xs text-red-600">+<?php echo formatCurrency($payment['late_fee_applied']); ?> late fee</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-right text-gray-900">
                                <?php echo formatCurrency($payment['paid_amount']); ?>
                                <?php if ($payment['payment_date']): ?>
                                <div class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-right text-gray-900">
                                <?php 
                                $total_due = floatval($payment['due_amount']) + floatval($payment['late_fee_applied']);
                                $remaining = $total_due - floatval($payment['paid_amount']);
                                ?>
                                <?php if ($remaining > 0): ?>
                                    <span class="font-medium text-orange-600"><?php echo formatCurrency($remaining); ?></span>
                                    <div class="text-xs text-gray-500">to pay</div>
                                <?php else: ?>
                                    <span class="font-medium text-green-600">₱0.00</span>
                                    <div class="text-xs text-green-600">Fully paid</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php 
                                    switch($payment['status']) {
                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                        case 'partial': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800 dark:text-gray-200'; break;
                                    }
                                    ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($payment['status'] !== 'paid'): ?>
                                <button onclick="recordPayment(<?php echo $payment['id']; ?>, <?php echo $remaining; ?>)" 
                                        class="text-green-600 hover:text-green-900 p-0.5" title="Record Payment - Remaining: <?php echo formatCurrency($remaining); ?>">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($payment['paid_amount'] > 0): ?>
                                <button onclick="editPayment(<?php echo $payment['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900 p-0.5 ml-1" title="Edit Payment">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($payment['receipt_number']): ?>
                                <button onclick="viewReceipt('<?php echo $payment['receipt_number']; ?>')" 
                                        class="text-purple-600 hover:text-purple-900 p-1 ml-1" title="View Receipt">
                                    <i class="fas fa-receipt"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Plan Information -->
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Plan Details</h2>
                <div class="space-x-2">
                    <button onclick="editInstallmentPlan(<?php echo $installment_plan['id']; ?>)" 
                            class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-edit mr-1"></i>Edit Plan
                    </button>
                    <button onclick="deleteInstallmentPlan(<?php echo $installment_plan['id']; ?>)" 
                            class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Plan Name:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($installment_plan['plan_name']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Total Amount:</span>
                    <span class="font-medium"><?php echo formatCurrency($installment_plan['total_amount']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Down Payment:</span>
                    <span class="font-medium"><?php echo formatCurrency($installment_plan['down_payment']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Monthly Payment:</span>
                    <span class="font-medium"><?php echo formatCurrency($installment_plan['installment_amount']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Installments:</span>
                    <span class="font-medium"><?php echo $installment_plan['number_of_installments']; ?> payments</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Interest Rate:</span>
                    <span class="font-medium"><?php echo $installment_plan['interest_rate']; ?>% monthly</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Frequency:</span>
                    <span class="font-medium"><?php echo ucfirst($installment_plan['payment_frequency']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Start Date:</span>
                    <span class="font-medium"><?php echo date('M d, Y', strtotime($installment_plan['start_date'])); ?></span>
                </div>
            </div>
            
            <?php if ($installment_plan['notes']): ?>
            <div class="mt-4 pt-4 border-t">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Notes:</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo nl2br(htmlspecialchars($installment_plan['notes'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Payment Summary -->
            <div class="mt-4 pt-4 border-t">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Summary:</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total Paid:</span>
                        <span class="font-medium text-green-600"><?php echo formatCurrency($installment_plan['total_paid']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Remaining Balance:</span>
                        <span class="font-medium text-orange-600"><?php echo formatCurrency($installment_plan['total_remaining']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Create New Installment Plan -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Create Payment Plan</h2>
    
    <!-- Payment Options Calculator -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Payment Options</h3>
        <div id="payment-options" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <!-- Options will be populated by JavaScript -->
        </div>
    </div>
    
    <form method="POST" action="?action=create_installment_plan&quote_id=<?php echo $quote['id']; ?>" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-2">Plan Name</label>
                <input type="text" id="plan_name" name="plan_name" 
                       value="Payment Plan for <?php echo htmlspecialchars($quote['customer_name']); ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                <input type="number" id="total_amount" name="total_amount" step="0.01" 
                       value="<?php echo $quote['total_amount']; ?>" readonly
                       class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="down_payment" class="block text-sm font-medium text-gray-700 mb-2">Down Payment</label>
                <input type="number" id="down_payment" name="down_payment" step="0.01" min="0" 
                       value="<?php echo $quote['total_amount'] * 0.2; ?>" 
                       oninput="calculateInstallments()"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="number_of_installments" class="block text-sm font-medium text-gray-700 mb-2">Number of Payments</label>
                <select id="number_of_installments" name="number_of_installments" onchange="calculateInstallments()"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="6">6 months</option>
                    <option value="12" selected>12 months</option>
                    <option value="18">18 months</option>
                    <option value="24">24 months</option>
                    <option value="36">36 months</option>
                </select>
            </div>
            <div>
                <label for="payment_frequency" class="block text-sm font-medium text-gray-700 mb-2">Payment Frequency</label>
                <select id="payment_frequency" name="payment_frequency"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="monthly" selected>Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="interest_rate" class="block text-sm font-medium text-gray-700 mb-2">Interest Rate (% monthly)</label>
                <input type="number" id="interest_rate" name="interest_rate" step="0.01" min="0" 
                       value="0.21" oninput="calculateInstallments()"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="late_fee_amount" class="block text-sm font-medium text-gray-700 mb-2">Late Fee Amount</label>
                <input type="number" id="late_fee_amount" name="late_fee_amount" step="0.01" min="0" 
                       value="500"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" id="start_date" name="start_date" 
                       value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>
        
        <!-- Calculation Preview -->
        <div id="calculation-preview" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-medium text-blue-900 mb-2">Payment Calculation</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-blue-700">Remaining Amount:</span>
                    <div class="font-medium text-blue-900" id="remaining-amount">₱0.00</div>
                </div>
                <div>
                    <span class="text-blue-700">Monthly Payment:</span>
                    <div class="font-medium text-blue-900" id="monthly-payment">₱0.00</div>
                </div>
                <div>
                    <span class="text-blue-700">Total Interest:</span>
                    <div class="font-medium text-blue-900" id="total-interest">₱0.00</div>
                </div>
            </div>
        </div>
        
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea id="notes" name="notes" rows="3"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Additional notes about the payment plan..."></textarea>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="window.history.back()" 
                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i>Create Payment Plan
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Record Payment Modal -->
<div id="payment-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Record Payment</h3>
            
            <form id="payment-form" method="POST" action="">
                <input type="hidden" id="payment_id" name="payment_id">
                <input type="hidden" name="action" value="record_payment">
                
                <div class="mb-4">
                    <label for="paid_amount" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                    <input type="number" id="paid_amount" name="paid_amount" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select id="payment_method" name="payment_method" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                    <input type="date" id="payment_date" name="payment_date" 
                           value="<?php echo date('Y-m-d'); ?>" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" id="reference_number" name="reference_number" readonly
                           class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100 text-gray-600 dark:text-gray-400"
                           placeholder="Auto-generated reference number">
                    <p class="text-xs text-gray-500 mt-1">Reference number will be auto-generated when payment is recorded</p>
                </div>
                
                <div class="mb-4">
                    <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="payment_notes" name="notes" rows="2"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Optional notes about this payment..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePaymentModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div id="edit-payment-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Payment</h3>
            
            <form id="edit-payment-form" method="POST" action="">
                <input type="hidden" id="edit_payment_id" name="payment_id">
                <input type="hidden" name="action" value="update_installment_payment">
                
                <div class="mb-4">
                    <label for="edit_paid_amount" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                    <input type="number" id="edit_paid_amount" name="paid_amount" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label for="edit_payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select id="edit_payment_method" name="payment_method" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="edit_payment_date" class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                    <input type="date" id="edit_payment_date" name="payment_date" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label for="edit_reference_number" class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" id="edit_reference_number" name="reference_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter reference number">
                </div>
                
                <div class="mb-4">
                    <label for="edit_receipt_number" class="block text-sm font-medium text-gray-700 mb-2">Receipt Number</label>
                    <input type="text" id="edit_receipt_number" name="receipt_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter receipt number">
                </div>
                
                <div class="mb-4">
                    <label for="edit_payment_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="edit_payment_notes" name="notes" rows="2"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Optional notes about this payment..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditPaymentModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Installment Plan Modal -->
<div id="edit-plan-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Payment Plan</h3>
            
            <form id="edit-plan-form" method="POST" action="" class="space-y-6">
                <input type="hidden" id="edit_plan_id" name="plan_id">
                <input type="hidden" name="action" value="update_installment_plan">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="edit_plan_name" class="block text-sm font-medium text-gray-700 mb-2">Plan Name</label>
                        <input type="text" id="edit_plan_name" name="plan_name" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="edit_total_amount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                        <input type="number" id="edit_total_amount" name="total_amount" step="0.01" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="edit_down_payment" class="block text-sm font-medium text-gray-700 mb-2">Down Payment</label>
                        <input type="number" id="edit_down_payment" name="down_payment" step="0.01" min="0" 
                               oninput="calculateEditInstallments()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="edit_number_of_installments" class="block text-sm font-medium text-gray-700 mb-2">Number of Payments</label>
                        <select id="edit_number_of_installments" name="number_of_installments" onchange="calculateEditInstallments()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="6">6 months</option>
                            <option value="12">12 months</option>
                            <option value="18">18 months</option>
                            <option value="24">24 months</option>
                            <option value="36">36 months</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_payment_frequency" class="block text-sm font-medium text-gray-700 mb-2">Payment Frequency</label>
                        <select id="edit_payment_frequency" name="payment_frequency"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="edit_interest_rate" class="block text-sm font-medium text-gray-700 mb-2">Interest Rate (% monthly)</label>
                        <input type="number" id="edit_interest_rate" name="interest_rate" step="0.01" min="0" 
                               oninput="calculateEditInstallments()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="edit_late_fee_amount" class="block text-sm font-medium text-gray-700 mb-2">Late Fee Amount</label>
                        <input type="number" id="edit_late_fee_amount" name="late_fee_amount" step="0.01" min="0" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="edit_start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" id="edit_start_date" name="start_date" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Calculation Preview -->
                <div id="edit-calculation-preview" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2">Payment Calculation</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-blue-700">Remaining Amount:</span>
                            <div class="font-medium text-blue-900" id="edit-remaining-amount">₱0.00</div>
                        </div>
                        <div>
                            <span class="text-blue-700">Monthly Payment:</span>
                            <div class="font-medium text-blue-900" id="edit-monthly-payment">₱0.00</div>
                        </div>
                        <div>
                            <span class="text-blue-700">Total Interest:</span>
                            <div class="font-medium text-blue-900" id="edit-total-interest">₱0.00</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-2"></i>
                        <div class="text-sm text-yellow-800">
                            <strong>Warning:</strong> Changing the payment schedule will regenerate all payment dates. This can only be done if no payments have been made yet.
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="edit_regenerate_payments" name="regenerate_payments" value="1"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-yellow-800">Regenerate payment schedule</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label for="edit_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="edit_notes" name="notes" rows="3"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Additional notes about the payment plan..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditPlanModal()"
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Payment Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Currency formatting function
function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Installment calculation functions
function calculateInstallments() {
    const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
    const downPayment = parseFloat(document.getElementById('down_payment').value) || 0;
    const numInstallments = parseInt(document.getElementById('number_of_installments').value) || 12;
    const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
    
    let remainingAmount = totalAmount - downPayment;
    const monthlyInterestRate = interestRate / 100;
    
    // Debug logging
    console.log('Calculation inputs:', {
        totalAmount,
        downPayment,
        numInstallments,
        interestRate,
        remainingAmount,
        monthlyInterestRate
    });
    
    let monthlyPayment;
    let totalInterest;
    
    if (interestRate > 0) {
        monthlyPayment = remainingAmount * 
            (monthlyInterestRate * Math.pow(1 + monthlyInterestRate, numInstallments)) /
            (Math.pow(1 + monthlyInterestRate, numInstallments) - 1);
        totalInterest = (monthlyPayment * numInstallments) - remainingAmount;
    } else {
        monthlyPayment = remainingAmount / numInstallments;
        totalInterest = 0;
    }
    
    // Round all calculations to 2 decimal places
    // Round monthly payment to nearest 0.50 (e.g., 17713.59 becomes 17713.50)
    monthlyPayment = Math.floor(monthlyPayment * 2) / 2;
    totalInterest = Math.round(totalInterest * 100) / 100;
    remainingAmount = Math.round(remainingAmount * 100) / 100;
    
    // Debug logging for calculated values
    console.log('Calculated values:', {
        monthlyPayment,
        totalInterest,
        remainingAmount
    });
    
    document.getElementById('remaining-amount').textContent = formatCurrency(remainingAmount);
    document.getElementById('monthly-payment').textContent = formatCurrency(monthlyPayment);
    document.getElementById('total-interest').textContent = formatCurrency(totalInterest);
}

// Payment modal functions
function recordPayment(paymentId, dueAmount) {
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('paid_amount').value = dueAmount;
    
    // Update form action to include quote_id
    const quoteId = new URLSearchParams(window.location.search).get('quote_id');
    if (quoteId) {
        document.getElementById('payment-form').action = `?action=record_payment&quote_id=${quoteId}`;
    }
    
    // Clear previous values
    document.getElementById('payment_notes').value = '';
    document.getElementById('reference_number').value = '';
    document.getElementById('payment_method').selectedIndex = 0;
    
    document.getElementById('payment-modal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('payment-modal').classList.add('hidden');
}

function viewReceipt(receiptNumber) {
    // Implement receipt viewing functionality
    alert('Receipt #' + receiptNumber + ' - Receipt viewing functionality to be implemented');
}

// Edit Payment Modal Functions
function editPayment(paymentId) {
    // Load payment data and populate the edit form
    fetch(`quotations.php?action=get_payment_details&payment_id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditPaymentForm(data.payment);
                document.getElementById('edit-payment-modal').classList.remove('hidden');
            } else {
                alert('Failed to load payment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred while loading payment details');
        });
}

function populateEditPaymentForm(payment) {
    document.getElementById('edit_payment_id').value = payment.id;
    document.getElementById('edit_paid_amount').value = payment.paid_amount;
    document.getElementById('edit_payment_method').value = payment.payment_method || 'cash';
    document.getElementById('edit_payment_date').value = payment.payment_date || '';
    document.getElementById('edit_reference_number').value = payment.reference_number || '';
    document.getElementById('edit_receipt_number').value = payment.receipt_number || '';
    document.getElementById('edit_payment_notes').value = payment.notes || '';
    
    // Update form action
    const quoteId = new URLSearchParams(window.location.search).get('quote_id');
    if (quoteId) {
        document.getElementById('edit-payment-form').action = `?action=update_installment_payment&quote_id=${quoteId}`;
    }
}

function closeEditPaymentModal() {
    document.getElementById('edit-payment-modal').classList.add('hidden');
}

// Edit Installment Plan Modal Functions
function editInstallmentPlan(planId) {
    // Load plan data and populate the edit form
    fetch(`quotations.php?action=get_plan_details&plan_id=${planId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditPlanForm(data.plan);
                document.getElementById('edit-plan-modal').classList.remove('hidden');
            } else {
                alert('Failed to load plan details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred while loading plan details');
        });
}

function populateEditPlanForm(plan) {
    document.getElementById('edit_plan_id').value = plan.id;
    document.getElementById('edit_plan_name').value = plan.plan_name || '';
    document.getElementById('edit_total_amount').value = plan.total_amount || '';
    document.getElementById('edit_down_payment').value = plan.down_payment || '';
    document.getElementById('edit_number_of_installments').value = plan.number_of_installments || '12';
    document.getElementById('edit_payment_frequency').value = plan.payment_frequency || 'monthly';
    document.getElementById('edit_interest_rate').value = plan.interest_rate || '';
    document.getElementById('edit_late_fee_amount').value = plan.late_fee_amount || '';
    document.getElementById('edit_start_date').value = plan.start_date || '';
    document.getElementById('edit_notes').value = plan.notes || '';
    
    // Update form action
    const quoteId = new URLSearchParams(window.location.search).get('quote_id');
    if (quoteId) {
        document.getElementById('edit-plan-form').action = `?action=update_installment_plan&quote_id=${quoteId}`;
    }
    
    // Calculate and display current values
    calculateEditInstallments();
}

function closeEditPlanModal() {
    document.getElementById('edit-plan-modal').classList.add('hidden');
}

// Delete Installment Plan Function
function deleteInstallmentPlan(planId) {
    if (confirm('Are you sure you want to delete this installment plan? This action cannot be undone and can only be done if no payments have been made.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `?action=delete_installment_plan&quote_id=${new URLSearchParams(window.location.search).get('quote_id')}`;
        
        const planIdInput = document.createElement('input');
        planIdInput.type = 'hidden';
        planIdInput.name = 'plan_id';
        planIdInput.value = planId;
        
        form.appendChild(planIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Edit Installment Calculation Function
function calculateEditInstallments() {
    const totalAmount = parseFloat(document.getElementById('edit_total_amount').value) || 0;
    const downPayment = parseFloat(document.getElementById('edit_down_payment').value) || 0;
    const numInstallments = parseInt(document.getElementById('edit_number_of_installments').value) || 12;
    const interestRate = parseFloat(document.getElementById('edit_interest_rate').value) || 0;
    
    let remainingAmount = totalAmount - downPayment;
    const monthlyInterestRate = interestRate / 100;
    
    let monthlyPayment;
    let totalInterest;
    
    if (interestRate > 0) {
        monthlyPayment = remainingAmount * 
            (monthlyInterestRate * Math.pow(1 + monthlyInterestRate, numInstallments)) /
            (Math.pow(1 + monthlyInterestRate, numInstallments) - 1);
        totalInterest = (monthlyPayment * numInstallments) - remainingAmount;
    } else {
        monthlyPayment = remainingAmount / numInstallments;
        totalInterest = 0;
    }
    
    // Round all calculations to 2 decimal places
    // Round monthly payment to nearest 0.50
    monthlyPayment = Math.floor(monthlyPayment * 2) / 2;
    totalInterest = Math.round(totalInterest * 100) / 100;
    remainingAmount = Math.round(remainingAmount * 100) / 100;
    
    document.getElementById('edit-remaining-amount').textContent = formatCurrency(remainingAmount);
    document.getElementById('edit-monthly-payment').textContent = formatCurrency(monthlyPayment);
    document.getElementById('edit-total-interest').textContent = formatCurrency(totalInterest);
}

// Currency formatting function
// Real-time calculation for unit price changes
function calculateItemTotal(unitPriceInput, quantity, discountPercentage) {
    const unitPrice = parseFloat(unitPriceInput.value) || 0;
    const qty = parseFloat(quantity) || 0;
    const discount = parseFloat(discountPercentage) || 0;
    
    // Calculate subtotal
    const subtotal = unitPrice * qty;
    
    // Calculate discount amount
    const discountAmount = subtotal * (discount / 100);
    
    // Calculate total amount
    const totalAmount = subtotal - discountAmount;
    
    // Round all calculations to 2 decimal places
    const roundedSubtotal = Math.round(subtotal * 100) / 100;
    const roundedDiscountAmount = Math.round(discountAmount * 100) / 100;
    const roundedTotalAmount = Math.round(totalAmount * 100) / 100;
    
    // Find the item ID from the input's form
    const form = unitPriceInput.closest('form');
    const quoteItemId = form.querySelector('input[name="quote_item_id"]').value;
    
    // Update the total display
    const totalElement = document.getElementById('total_' + quoteItemId);
    if (totalElement) {
        totalElement.textContent = formatCurrency(roundedTotalAmount);
    }
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('total_amount')) {
        calculateInstallments();
        
        // Add event listeners for real-time updates
        const inputs = ['total_amount', 'down_payment', 'number_of_installments', 'interest_rate'];
        inputs.forEach(inputId => {
            const element = document.getElementById(inputId);
            if (element) {
                element.addEventListener('input', calculateInstallments);
                element.addEventListener('change', calculateInstallments);
            }
        });
    }
    
    // Add event listeners for edit form calculations
    const editInputs = ['edit_total_amount', 'edit_down_payment', 'edit_number_of_installments', 'edit_interest_rate'];
    editInputs.forEach(inputId => {
        const element = document.getElementById(inputId);
        if (element) {
            element.addEventListener('input', calculateEditInstallments);
            element.addEventListener('change', calculateEditInstallments);
        }
    });
});
</script>

<?php endif; ?>

<!-- Customer Details Modal -->
<div id="customer-details-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Customer & Solar Project Details</h3>
                <button type="button" onclick="closeCustomerDetailsModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="customer-details-content" class="mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                        <span class="text-blue-800">Loading customer details...</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeCustomerDetailsModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Details Modal -->
<div id="edit-customer-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Customer & Solar Project Details</h3>
                <button type="button" onclick="closeEditCustomerModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="edit-customer-form" method="POST" class="space-y-6">
                <input type="hidden" id="edit_quote_id" name="quote_id" value="">
                
                <!-- Basic Quotation Info Section -->
                <div class="border-b pb-6">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quotation Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_proposal_name" class="block text-sm font-medium text-gray-700 mb-2">Proposal Name</label>
                            <input type="text" id="edit_proposal_name" name="proposal_name"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter proposal name">
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information Section -->
                <div class="border-b pb-6">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Customer Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="edit_full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="edit_full_name" name="full_name"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter full name">
                        </div>
                        <div>
                            <label for="edit_phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="edit_phone_number" name="phone_number"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter phone number">
                        </div>
                        <div>
                            <label for="edit_account_creation_date" class="block text-sm font-medium text-gray-700 mb-2">Account Creation Date</label>
                            <input type="date" id="edit_account_creation_date" name="account_creation_date"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="edit_address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea id="edit_address" name="address" rows="2"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                  placeholder="Enter complete address"></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Contact Method</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_email" name="contact_method[]" value="email" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Email</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_phone" name="contact_method[]" value="phone" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Phone</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_sms" name="contact_method[]" value="sms" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">SMS</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Solar Project Details Section -->
                <div class="pb-6">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Solar Project Details</h4>
                    
                    <!-- KW and Labor Fee Section -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <label for="edit_kw" class="block text-sm font-medium text-gray-700 mb-2">KW</label>
                            <input type="number" id="edit_kw" name="kw" min="0" step="any" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                                   placeholder="Enter KW value">
                        </div>
                        <div>
                            <label for="edit_labor_fee" class="block text-sm font-medium text-gray-700 mb-2">Labor Fee (PHP)</label>
                            <input type="number" id="edit_labor_fee" name="labor_fee" min="0" step="any" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                                   placeholder="Enter Labor Fee">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total (KW * Labor Fee)</label>
                            <input type="text" id="edit_total_kw_labor" readonly 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100" 
                                   value="" placeholder="Total will appear here">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">System Type</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_grid_tie" name="system_type[]" value="grid_tie" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Grid Tie</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_off_grid" name="system_type[]" value="off_grid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Off Grid</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_hybrid" name="system_type[]" value="hybrid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Hybrid</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label for="edit_system_size" class="block text-sm font-medium text-gray-700 mb-2">System Size (kW)</label>
                            <input type="number" id="edit_system_size" name="system_size" min="0" step="0.01"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter system size in kW">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Installation Type</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_rooftop" name="installation_type[]" value="rooftop" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Rooftop</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_ground" name="installation_type[]" value="ground_mounted" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Ground Mounted</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_carport" name="installation_type[]" value="carport" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Carport</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="edit_panel_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Panel Brand/Model</label>
                            <input type="text" id="edit_panel_brand_model" name="panel_brand_model"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter panel brand and model">
                        </div>
                        <div>
                            <label for="edit_inverter_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Inverter Brand/Model</label>
                            <input type="text" id="edit_inverter_brand_model" name="inverter_brand_model"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter inverter brand and model">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="edit_estimated_installation_date" class="block text-sm font-medium text-gray-700 mb-2">Estimated Installation Date</label>
                            <input type="date" id="edit_estimated_installation_date" name="estimated_installation_date"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Installation Status</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_planned" name="installation_status[]" value="planned" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Planned</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_progress" name="installation_status[]" value="in_progress" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">In Progress</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_completed" name="installation_status[]" value="completed" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Completed</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_maintenance" name="installation_status[]" value="maintenance" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Maintenance</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Battery Backup Capacity</label>
                                <div class="flex gap-4 mb-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_battery_backup_capacity_yes" name="battery_backup_capacity" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleEditBatteryCapacityInput()">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_battery_backup_capacity_no" name="battery_backup_capacity" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleEditBatteryCapacityInput()">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                                <div id="edit_battery_capacity_input" class="hidden">
                                    <input type="text" id="edit_battery_capacity_value" name="battery_capacity_value" placeholder="Enter battery capacity (e.g., 10kWh, 5000Wh)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-solar-blue focus:border-transparent text-sm">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Net Metering</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_net_metering_yes" name="net_metering" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_net_metering_no" name="net_metering" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmed</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_confirmed_yes" name="confirmed" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_confirmed_no" name="confirmed" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="edit_client_signature" class="block text-sm font-medium text-gray-700 mb-2">Client Signature</label>
                            <input type="text" id="edit_client_signature" name="client_signature"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Type signature or leave blank for manual signing">
                        </div>
                        
                        <div>
                            <label for="edit_remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                            <textarea id="edit_remarks" name="remarks" rows="4"
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                      placeholder="Enter any additional remarks or notes"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button type="button" onclick="closeEditCustomerModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quote Approval Modal -->
<div id="quote-approval-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative p-5 border w-full max-w-md shadow-lg rounded-md bg-white mx-4">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
            
            <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Approve Quote</h3>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="text-sm text-blue-800">
                    <div class="mb-2"><strong>Quote Number:</strong> <span id="approval_quote_number"></span></div>
                    <div class="mb-2"><strong>Customer:</strong> <span id="approval_customer_name"></span></div>
                    <div class="mb-2"><strong>Total Amount:</strong> <span id="approval_total_amount"></span></div>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">Important Notice</h4>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Approving this quote will:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Automatically deduct quoted items from inventory stock</li>
                                <li>Mark serial numbers as "sold" (if applicable)</li>
                                <li>Generate a project number</li>
                                <li>Change quote status to "accepted"</li>
                            </ul>
                            <p class="mt-2 font-medium">Please ensure you have sufficient stock before proceeding.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeQuoteApprovalModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="confirmQuoteApproval()"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                    <i class="fas fa-check mr-2"></i>Approve Quote
                </button>
            </div>
            
            <input type="hidden" id="approval_quote_id" value="">
        </div>
    </div>
</div>

<!-- Stock Notification Modal -->
<div id="stock-notification-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white mx-4">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            
            <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Insufficient Stock Alert</h3>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800">Stock Shortage Detected</h4>
                        <div class="mt-2 text-sm text-red-700">
                            <p>The following items have insufficient stock to fulfill this quotation:</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stock Details Table -->
            <div class="mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Needed</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Shortage</th>
                            </tr>
                        </thead>
                        <tbody id="stock-items-list" class="bg-white divide-y divide-gray-200">
                            <!-- Stock items will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Action Options -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-lightbulb text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">Recommended Actions</h4>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Update inventory stock levels for the items listed above</li>
                                <li>Contact suppliers to restock these items</li>
                                <li>Modify the quotation quantities if appropriate</li>
                                <li>Consider alternative products with sufficient stock</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeStockNotificationModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="ignoreStockAndApprove()"
                        class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition">
                    <i class="fas fa-times-circle mr-2"></i>Ignore
                </button>
                <button type="button" onclick="proceedWithApproval()"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Proceed Anyway
                </button>
                <button type="button" onclick="goToInventory()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-warehouse mr-2"></i>Update Inventory
                </button>
            </div>
            
            <input type="hidden" id="stock_notification_quote_id" value="">
        </div>
    </div>
</div>

<script>
// Customer Details Modal Functions
function viewCustomerDetails(quoteId) {
    const modal = document.getElementById('customer-details-modal');
    modal.classList.remove('hidden');
    loadCustomerDetails(quoteId);
}

function closeCustomerDetailsModal() {
    const modal = document.getElementById('customer-details-modal');
    modal.classList.add('hidden');
}

function duplicateQuote(quoteId) {
    if (confirm('Are you sure you want to duplicate this quotation? This will create a new quotation with the same items, but serialized items will be excluded and need to be added manually.')) {
        // Create a form to submit the duplication request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=duplicate_quote';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'original_quote_id';
        input.value = quoteId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function loadCustomerDetails(quoteId) {
    const contentDiv = document.getElementById('customer-details-content');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                <span class="text-blue-800">Loading customer details...</span>
            </div>
        </div>
    `;
    
    // Make AJAX request to get customer details
    fetch(`quotations.php?action=get_customer_details&quote_id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCustomerDetails(data.customer_info, data.solar_details, data.quote_info);
            } else {
                showCustomerDetailsError(data.message || 'Failed to load customer details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCustomerDetailsError('Network error occurred while loading customer details');
        });
}

function displayCustomerDetails(customerInfo, solarDetails, quoteInfo) {
    const contentDiv = document.getElementById('customer-details-content');
    
    // Helper function to display boolean as Yes/No ewwwwwwwwwwwwwwwwwwwwww                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
    const boolToYesNo = (value) => value ? 'Yes' : 'No';
    
    // Helper function to display checkbox arrays
    const displayCheckboxes = (obj, prefix) => {
        const values = [];
        for (const key in obj) {
            if (key.startsWith(prefix) && obj[key]) {
                values.push(key.replace(prefix, '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
            }
        }
        return values.length > 0 ? values.join(', ') : 'None selected';
    };
    
    contentDiv.innerHTML = `
        <!-- Quote Information -->
        <div class="bg-gray-50 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Quote Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div><strong>Quote Number:</strong> ${quoteInfo?.quote_number || 'N/A'}</div>
                <div><strong>Customer Name:</strong> ${quoteInfo?.customer_name || 'N/A'}</div>
                <div><strong>Proposal:</strong> ${quoteInfo?.proposal_name || 'N/A'}</div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="bg-white border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Customer Information</h4>
            ${customerInfo ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><strong>Full Name:</strong> ${customerInfo.full_name || 'Not provided'}</div>
                    <div><strong>Phone Number:</strong> ${customerInfo.phone_number || 'Not provided'}</div>
                    <div><strong>Account Creation Date:</strong> ${customerInfo.account_creation_date || 'Not provided'}</div>
                    <div class="md:col-span-2"><strong>Address:</strong> ${customerInfo.address || 'Not provided'}</div>
                    <div class="md:col-span-2">
                        <strong>Preferred Contact Methods:</strong> 
                        ${[
                            customerInfo.contact_method_email ? 'Email' : null,
                            customerInfo.contact_method_phone ? 'Phone' : null,
                            customerInfo.contact_method_sms ? 'SMS' : null
                        ].filter(Boolean).join(', ') || 'None selected'}
                    </div>
                </div>
            ` : `
                <p class="text-gray-500 italic">No customer information provided for this quote.</p>
            `}
        </div>
        
        <!-- Solar Project Details -->
        <div class="bg-white border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Solar Project Details</h4>
            ${solarDetails ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <div class="mb-3">
                            <strong>System Type:</strong><br>
                            <span class="text-gray-600 dark:text-gray-400">
                                ${[
                                    solarDetails.system_type_grid_tie ? 'Grid Tie' : null,
                                    solarDetails.system_type_off_grid ? 'Off Grid' : null,
                                    solarDetails.system_type_hybrid ? 'Hybrid' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>System Size:</strong> ${solarDetails.system_size_kw ? solarDetails.system_size_kw + ' kW' : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Installation Type:</strong><br>
                            <span class="text-gray-600 dark:text-gray-400">
                                ${[
                                    solarDetails.installation_type_rooftop ? 'Rooftop' : null,
                                    solarDetails.installation_type_ground_mounted ? 'Ground Mounted' : null,
                                    solarDetails.installation_type_carport ? 'Carport' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Panel Brand/Model:</strong> ${solarDetails.panel_brand_model || 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Inverter Brand/Model:</strong> ${solarDetails.inverter_brand_model || 'Not specified'}
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-3">
                            <strong>Estimated Installation Date:</strong> ${solarDetails.estimated_installation_date || 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Installation Status:</strong><br>
                            <span class="text-gray-600 dark:text-gray-400">
                                ${[
                                    solarDetails.installation_status_planned ? 'Planned' : null,
                                    solarDetails.installation_status_in_progress ? 'In Progress' : null,
                                    solarDetails.installation_status_completed ? 'Completed' : null,
                                    solarDetails.installation_status_maintenance ? 'Maintenance' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Battery Backup Capacity:</strong> ${solarDetails.battery_backup_capacity ? solarDetails.battery_backup_capacity.charAt(0).toUpperCase() + solarDetails.battery_backup_capacity.slice(1) : 'Not specified'}${solarDetails.battery_backup_capacity === 'yes' && solarDetails.battery_capacity_value ? ' - ' + solarDetails.battery_capacity_value : ''}
                        </div>
                        <div class="mb-3">
                            <strong>Net Metering:</strong> ${solarDetails.net_metering ? solarDetails.net_metering.charAt(0).toUpperCase() + solarDetails.net_metering.slice(1) : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Confirmed:</strong> ${solarDetails.confirmed ? solarDetails.confirmed.charAt(0).toUpperCase() + solarDetails.confirmed.slice(1) : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Client Signature:</strong> ${solarDetails.client_signature || 'Not provided'}
                        </div>
                    </div>
                </div>
                
                ${solarDetails.remarks ? `
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <strong>Remarks:</strong><br>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-line">${solarDetails.remarks}</p>
                    </div>
                ` : ''}
            ` : `
                <p class="text-gray-500 italic">No solar project details provided for this quote.</p>
            `}
        </div>
    `;
}

function showCustomerDetailsError(message) {
    const contentDiv = document.getElementById('customer-details-content');
    contentDiv.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                <span class="text-red-800">${message}</span>
            </div>
        </div>
    `;
}

// Edit modal calculation function
function updateEditTotal() {
    var kw = parseFloat(document.getElementById('edit_kw').value) || 0;
    var labor = parseFloat(document.getElementById('edit_labor_fee').value) || 0;
    var total = kw * labor;
    document.getElementById('edit_total_kw_labor').value = total > 0 ? 
        total.toLocaleString('en-PH', {style: 'currency', currency: 'PHP'}) : '';
}

// Edit Customer Details Modal Functions
function editCustomerDetails(quoteId) {
    const modal = document.getElementById('edit-customer-modal');
    modal.classList.remove('hidden');
    
    // Add event listeners for KW and Labor Fee calculation
    const editKwField = document.getElementById('edit_kw');
    const editLaborFeeField = document.getElementById('edit_labor_fee');
    
    if (editKwField && editLaborFeeField) {
        // Remove existing listeners to avoid duplicates
        editKwField.removeEventListener('input', updateEditTotal);
        editLaborFeeField.removeEventListener('input', updateEditTotal);
        
        // Add new listeners
        editKwField.addEventListener('input', updateEditTotal);
        editLaborFeeField.addEventListener('input', updateEditTotal);
    }
    
    loadCustomerDetailsForEdit(quoteId);
}

function closeEditCustomerModal() {
    const modal = document.getElementById('edit-customer-modal');
    modal.classList.add('hidden');
}

function loadCustomerDetailsForEdit(quoteId) {
    // Set the quote ID in the form
    document.getElementById('edit_quote_id').value = quoteId;
    
    // Set form action
    const form = document.getElementById('edit-customer-form');
    form.action = `?action=update_customer_details&quote_id=${quoteId}`;
    
    // Add form submission handler
    form.onsubmit = function(e) {
        e.preventDefault();
        submitEditCustomerForm(quoteId);
    };
    
    // Load existing data
    fetch(`quotations.php?action=get_customer_details&quote_id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.customer_info, data.solar_details, data.quote_items, data.quote_info);
            } else {
                alert('Failed to load customer details for editing');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred while loading customer details');
        });
}

function submitEditCustomerForm(quoteId) {
    const form = document.getElementById('edit-customer-form');
    const formData = new FormData(form);
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton ? submitButton.textContent : '';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Updating...';
    }
    
    fetch(`quotations.php?action=update_customer_details&quote_id=${quoteId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success popup
            showNotification(data.message, 'success');
            
            // Close modal
            closeEditCustomerModal();
            
            // Reload the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Show error popup
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred while updating details', 'error');
    })
    .finally(() => {
        // Reset button state
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

function populateEditForm(customerInfo, solarDetails, quoteItems, quoteInfo) {
    // Populate quotation information
    if (quoteInfo) {
        document.getElementById('edit_proposal_name').value = quoteInfo.proposal_name || '';
    }
    
    // Populate customer information
    if (customerInfo) {
        document.getElementById('edit_full_name').value = customerInfo.full_name || '';
        document.getElementById('edit_phone_number').value = customerInfo.phone_number || '';
        document.getElementById('edit_account_creation_date').value = customerInfo.account_creation_date || '';
        document.getElementById('edit_address').value = customerInfo.address || '';
        
        // Contact methods
        document.getElementById('edit_contact_email').checked = customerInfo.contact_method_email == 1;
        document.getElementById('edit_contact_phone').checked = customerInfo.contact_method_phone == 1;
        document.getElementById('edit_contact_sms').checked = customerInfo.contact_method_sms == 1;
    }
    
    // Populate solar project details
    if (solarDetails) {
        document.getElementById('edit_system_size').value = solarDetails.system_size_kw || '';
        document.getElementById('edit_panel_brand_model').value = solarDetails.panel_brand_model || '';
        document.getElementById('edit_inverter_brand_model').value = solarDetails.inverter_brand_model || '';
        document.getElementById('edit_estimated_installation_date').value = solarDetails.estimated_installation_date || '';
        document.getElementById('edit_client_signature').value = solarDetails.client_signature || '';
        document.getElementById('edit_remarks').value = solarDetails.remarks || '';
        
        // System types
        document.getElementById('edit_system_grid_tie').checked = solarDetails.system_type_grid_tie == 1;
        document.getElementById('edit_system_off_grid').checked = solarDetails.system_type_off_grid == 1;
        document.getElementById('edit_system_hybrid').checked = solarDetails.system_type_hybrid == 1;
        
        // Installation types
        document.getElementById('edit_install_rooftop').checked = solarDetails.installation_type_rooftop == 1;
        document.getElementById('edit_install_ground').checked = solarDetails.installation_type_ground_mounted == 1;
        document.getElementById('edit_install_carport').checked = solarDetails.installation_type_carport == 1;
        
        // Installation statuses
        document.getElementById('edit_status_planned').checked = solarDetails.installation_status_planned == 1;
        document.getElementById('edit_status_progress').checked = solarDetails.installation_status_in_progress == 1;
        document.getElementById('edit_status_completed').checked = solarDetails.installation_status_completed == 1;
        document.getElementById('edit_status_maintenance').checked = solarDetails.installation_status_maintenance == 1;
        
        // Battery backup capacity radio buttons
        if (solarDetails.battery_backup_capacity === 'yes') {
            document.getElementById('edit_battery_backup_capacity_yes').checked = true;
        } else if (solarDetails.battery_backup_capacity === 'no') {
            document.getElementById('edit_battery_backup_capacity_no').checked = true;
        }
        
        // Net metering and confirmed radio buttons
        if (solarDetails.net_metering === 'yes') {
            document.getElementById('edit_net_metering_yes').checked = true;
        } else if (solarDetails.net_metering === 'no') {
            document.getElementById('edit_net_metering_no').checked = true;
        }
        
        if (solarDetails.confirmed === 'yes') {
            document.getElementById('edit_confirmed_yes').checked = true;
        } else if (solarDetails.confirmed === 'no') {
            document.getElementById('edit_confirmed_no').checked = true;
        }
        
        // Populate battery capacity value if exists
        if (solarDetails.battery_capacity_value) {
            document.getElementById('edit_battery_capacity_value').value = solarDetails.battery_capacity_value;
        }
        
        // Show/hide battery capacity input based on selection
        toggleEditBatteryCapacityInput();
    }
    
    // Try to populate KW and Labor Fee from existing labor fee quote items
    if (quoteItems && Array.isArray(quoteItems)) {
        const laborFeeItem = quoteItems.find(item => 
            item.brand && item.brand.toLowerCase() === 'labor'
        );
        
        if (laborFeeItem) {
            // Extract KW from quantity and Labor Fee from unit price
            document.getElementById('edit_kw').value = laborFeeItem.quantity || '';
            document.getElementById('edit_labor_fee').value = laborFeeItem.unit_price || '';
            
            // Trigger calculation
            updateEditTotal();
        }
    }
    
    // Always trigger calculation at the end to ensure total is updated
    updateEditTotal();
}

// Battery Backup Capacity Input Toggle Functions
function toggleBatteryCapacityInput() {
    const yesRadio = document.querySelector('input[name="battery_backup_capacity"][value="yes"]');
    const inputDiv = document.getElementById('battery_capacity_input');
    
    if (yesRadio && yesRadio.checked) {
        inputDiv.classList.remove('hidden');
    } else {
        inputDiv.classList.add('hidden');
    }
}

function toggleEditBatteryCapacityInput() {
    const yesRadio = document.getElementById('edit_battery_backup_capacity_yes');
    const inputDiv = document.getElementById('edit_battery_capacity_input');
    
    if (yesRadio && yesRadio.checked) {
        inputDiv.classList.remove('hidden');
    } else {
        inputDiv.classList.add('hidden');
    }
}

// Quote approval modal functions
function showQuoteApprovalModal(quoteId, quoteNumber, customerName, totalAmount) {
    // First check stock availability
    checkQuoteStock(quoteId, quoteNumber, customerName, totalAmount);
}

function checkQuoteStock(quoteId, quoteNumber, customerName, totalAmount) {
    // Show loading state
    const loadingModal = document.getElementById('quote-approval-modal');
    const quoteIdInput = document.getElementById('approval_quote_id');
    const quoteNumberSpan = document.getElementById('approval_quote_number');
    const customerNameSpan = document.getElementById('approval_customer_name');
    const totalAmountSpan = document.getElementById('approval_total_amount');
    
    // Populate modal with quote details
    quoteIdInput.value = quoteId;
    quoteNumberSpan.textContent = quoteNumber;
    customerNameSpan.textContent = customerName;
    totalAmountSpan.textContent = formatCurrency(totalAmount);
    
    // Show loading state in modal
    const modalContent = loadingModal.querySelector('.mt-3');
    const originalContent = modalContent.innerHTML;
    
    modalContent.innerHTML = `
        <div class="flex items-center justify-center">
            <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
            <span class="text-blue-800">Checking stock availability...</span>
        </div>
    `;
    
    loadingModal.classList.remove('hidden');
    
    // Check stock via AJAX
    fetch(`?action=check_quote_stock&quote_id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.has_insufficient_stock) {
                    // Show stock notification modal
                    loadingModal.classList.add('hidden');
                    showStockNotificationModal(quoteId, quoteNumber, customerName, totalAmount, data.insufficient_items);
                } else {
                    // Show normal approval modal
                    modalContent.innerHTML = originalContent;
                }
            } else {
                // Show error and proceed with normal approval
                console.error('Stock check failed:', data.message);
                modalContent.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error checking stock:', error);
            // Show normal approval modal on error
            modalContent.innerHTML = originalContent;
        });
}

function closeQuoteApprovalModal() {
    const modal = document.getElementById('quote-approval-modal');
    modal.classList.add('hidden');
}

function confirmQuoteApproval() {
    const quoteId = document.getElementById('approval_quote_id').value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `?action=update_quote_status&quote_id=${quoteId}`;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = 'accepted';
    
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}

// Stock notification modal functions
function showStockNotificationModal(quoteId, quoteNumber, customerName, totalAmount, insufficientItems) {
    const modal = document.getElementById('stock-notification-modal');
    const quoteIdInput = document.getElementById('stock_notification_quote_id');
    const stockItemsList = document.getElementById('stock-items-list');
    
    // Set quote ID
    quoteIdInput.value = quoteId;
    
    // Populate stock items table
    stockItemsList.innerHTML = '';
    insufficientItems.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900">${item.item}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900">${item.available}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900">${item.needed}</td>
            <td class="px-4 py-3 text-center text-sm font-medium text-red-600">${item.shortage}</td>
        `;
        stockItemsList.appendChild(row);
    });
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeStockNotificationModal() {
    const modal = document.getElementById('stock-notification-modal');
    modal.classList.add('hidden');
}

function proceedWithApproval() {
    const quoteId = document.getElementById('stock_notification_quote_id').value;
    
    // Close stock notification modal
    closeStockNotificationModal();
    
    // Proceed with normal approval process
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `?action=update_quote_status&quote_id=${quoteId}`;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = 'accepted';
    
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}

function goToInventory() {
    // Close stock notification modal
    closeStockNotificationModal();
    
    // Redirect to inventory page
    window.location.href = 'inventory.php';
}

function ignoreStockAndApprove() {
    const quoteId = document.getElementById('stock_notification_quote_id').value;
    
    // Close stock notification modal
    closeStockNotificationModal();
    
    // Proceed with approval process ignoring stock levels
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `?action=update_quote_status&quote_id=${quoteId}`;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = 'accepted';
    
    // Add a flag to indicate we're ignoring stock levels
    const ignoreStockInput = document.createElement('input');
    ignoreStockInput.type = 'hidden';
    ignoreStockInput.name = 'ignore_stock';
    ignoreStockInput.value = '1';
    
    form.appendChild(statusInput);
    form.appendChild(ignoreStockInput);
    document.body.appendChild(form);
    form.submit();
}

// Handle custom status change with stock checking for Complete status
function handleCustomStatusChange(event, quoteId, quoteNumber, customerName, totalAmount) {
    event.preventDefault();
    
    const statusSelect = document.getElementById('custom-status-select');
    const selectedStatus = statusSelect.value;
    
    if (selectedStatus === 'accepted') {
        // For Complete status, use the same stock checking as Approve Quote
        showQuoteApprovalModal(quoteId, quoteNumber, customerName, totalAmount);
    } else {
        // For other statuses, proceed with normal form submission
        event.target.submit();
    }
    
    return false;
}

// Quotations Filtering Functions
let allQuotes = [];
let filteredQuotes = [];

// Initialize filtering on page load
document.addEventListener('DOMContentLoaded', function() {
    // Store all quotes data
    const quoteRows = document.querySelectorAll('.quote-row');
    allQuotes = Array.from(quoteRows).map(row => ({
        element: row,
        quoteNumber: row.getAttribute('data-quote-number'),
        customerName: row.getAttribute('data-customer-name'),
        proposalName: row.getAttribute('data-proposal-name'),
        status: row.getAttribute('data-status'),
        date: row.getAttribute('data-date'),
        hasInstallment: row.getAttribute('data-has-installment'),
        totalAmount: parseFloat(row.getAttribute('data-total-amount')),
        itemsCount: parseInt(row.getAttribute('data-items-count'))
    }));
    
    filteredQuotes = [...allQuotes];
    updateResultsCount();
});

function filterQuotations() {
    const searchTerm = document.getElementById('quote-search').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const dateFilter = document.getElementById('date-filter').value;
    
    filteredQuotes = allQuotes.filter(quote => {
        // Search filter
        const matchesSearch = !searchTerm || 
            quote.quoteNumber.toLowerCase().includes(searchTerm) ||
            quote.customerName.includes(searchTerm) ||
            quote.proposalName.includes(searchTerm);
        
        // Status filter
        const matchesStatus = !statusFilter || quote.status === statusFilter;
        
        // Date filter
        const matchesDate = !dateFilter || isDateInRange(quote.date, dateFilter);
        
        return matchesSearch && matchesStatus && matchesDate;
    });
    
    updateTableDisplay();
    updateResultsCount();
}

function setQuickFilter(filterType) {
    // Update button states
    document.querySelectorAll('.quick-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white', 'dark:bg-blue-600', 'dark:text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300');
    });
    
    // Highlight active button
    event.target.classList.remove('bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300');
    event.target.classList.add('active', 'bg-blue-600', 'text-white', 'dark:bg-blue-600', 'dark:text-white');
    
    // Set filters based on quick filter type
    if (filterType === 'installment') {
        document.getElementById('status-filter').value = '';
        filteredQuotes = allQuotes.filter(quote => quote.hasInstallment === 'yes');
    } else {
        document.getElementById('status-filter').value = filterType;
        filterQuotations();
    }
    
    updateTableDisplay();
    updateResultsCount();
}

function isDateInRange(dateString, range) {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    switch (range) {
        case 'today':
            return date.toDateString() === today.toDateString();
        
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(today.getDate() - 7);
            return date >= weekAgo && date <= today;
        
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);
            return date >= monthAgo && date <= today;
        
        case 'quarter':
            const quarterAgo = new Date(today);
            quarterAgo.setMonth(today.getMonth() - 3);
            return date >= quarterAgo && date <= today;
        
        case 'year':
            const yearAgo = new Date(today);
            yearAgo.setFullYear(today.getFullYear() - 1);
            return date >= yearAgo && date <= today;
        
        default:
            return true;
    }
}

function updateTableDisplay() {
    // Hide all rows first
    allQuotes.forEach(quote => {
        quote.element.style.display = 'none';
    });
    
    // Show filtered rows
    filteredQuotes.forEach(quote => {
        quote.element.style.display = '';
    });
    
    // Show/hide no results message
    const noResultsRow = document.querySelector('.no-results-row');
    if (filteredQuotes.length === 0) {
        if (!noResultsRow) {
            const tbody = document.querySelector('tbody');
            const noResultsTr = document.createElement('tr');
            noResultsTr.className = 'no-results-row';
            noResultsTr.innerHTML = `
                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No quotations found matching your criteria.</p>
                </td>
            `;
            tbody.appendChild(noResultsTr);
        }
    } else if (noResultsRow) {
        noResultsRow.remove();
    }
}

function updateResultsCount() {
    const filteredCountEl = document.getElementById('filtered-count');
    const totalCountEl = document.getElementById('total-count');
    
    if (filteredCountEl) {
        filteredCountEl.textContent = filteredQuotes.length;
    }
    if (totalCountEl) {
        totalCountEl.textContent = allQuotes.length;
    }
}

function clearFilters() {
    document.getElementById('quote-search').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('date-filter').value = '';
    
    // Reset quick filter buttons
    document.querySelectorAll('.quick-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white', 'dark:bg-blue-600', 'dark:text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300');
    });
    
    // Show all quotations
    filteredQuotes = [...allQuotes];
    updateTableDisplay();
    updateResultsCount();
}

function exportFilteredQuotations() {
    if (filteredQuotes.length === 0) {
        alert('No quotations to export. Please adjust your filters.');
        return;
    }
    
    // Create CSV content
    let csvContent = 'Quote Number,Customer Name,Proposal,Items Count,Total Amount,Status,Date Created,Has Installment\n';
    
    filteredQuotes.forEach(quote => {
        const row = [
            quote.quoteNumber,
            quote.customerName,
            quote.proposalName || '',
            quote.itemsCount,
            quote.totalAmount.toFixed(2),
            quote.status,
            quote.date,
            quote.hasInstallment === 'yes' ? 'Yes' : 'No'
        ];
        csvContent += row.map(field => `"${field}"`).join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `quotations_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Edit inventory item functionality
function editInventoryItem(itemId, itemName) {
    // Show loading state
    document.getElementById('edit-item-modal').classList.remove('hidden');
    document.getElementById('edit-item-content').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Loading item details...</p></div>';
    
    // Fetch item details
    fetch(`?action=get_inventory_item&item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEditForm(data.item);
            } else {
                document.getElementById('edit-item-content').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Error loading item: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('edit-item-content').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Error loading item details</p>
                </div>
            `;
        });
}

function displayEditForm(item) {
    // Debug: Log the item data to console
    console.log('Item data:', item);
    console.log('Suppliers available:', item.suppliers);
    
    const content = `
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-2"></h3>
            <p class="text-sm text-gray-600">${item.brand} ${item.model}</p>
        </div>
        
        <form id="edit-inventory-form" class="space-y-4" onsubmit="return handleInventoryUpdate(this, event)">
            <input type="hidden" name="inventory_item_id" value="${item.id}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text" name="brand" value="${item.brand}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand/Model</label>
                    <input type="text" name="model" value="${item.model}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Size/Specification</label>
                <input type="text" name="size_specification" value="${item.size_specification || ''}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base Price</label>
                    <input type="number" name="base_price" value="${item.base_price}" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price</label>
                    <input type="number" name="selling_price" value="${item.selling_price}" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                    <input type="number" name="stock_quantity" value="${item.stock_quantity}" min="0" required readonly
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100 cursor-not-allowed">
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Stock quantity cannot be edited directly. Use inventory management to modify stock levels.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Category</option>
                        ${item.categories.map(cat => 
                            `<option value="${cat.id}" ${cat.id == item.category_id ? 'selected' : ''}>${cat.name}</option>`
                        ).join('')}
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                    <input type="number" name="discount_percentage" value="${item.discount_percentage || 0}" 
                           min="0" max="100" step="0.01"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Stock</label>
                    <input type="number" name="minimum_stock" value="${item.minimum_stock || 0}" 
                           min="0"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <select name="supplier_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">No Supplier</option>
                        ${item.suppliers && item.suppliers.length > 0 ? 
                            item.suppliers.map(supplier => 
                                `<option value="${supplier.id}" ${supplier.id == item.supplier_id ? 'selected' : ''}>${supplier.name}</option>`
                            ).join('') : 
                            '<option value="" disabled>No active suppliers available</option>'
                        }
                    </select>
                    ${item.suppliers && item.suppliers.length === 0 ? 
                        '<p class="text-sm text-amber-600 mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>No active suppliers found. You can still save without a supplier.</p>' : 
                        ''
                    }
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">${item.description || ''}</textarea>
            </div>
            
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" name="generate_serials" value="1" ${item.generate_serials ? 'checked' : ''}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Generate Serial Numbers</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" ${item.is_active ? 'checked' : ''}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    Update Item
                </button>
            </div>
        </form>
    `;
    
    document.getElementById('edit-item-content').innerHTML = content;
}

function closeEditModal() {
    document.getElementById('edit-item-modal').classList.add('hidden');
}

function validateInventoryForm(form) {
    // Get form data
    const formData = new FormData(form);
    const supplierIdValue = formData.get('supplier_id');
    
    // Debug: Log form submission data
    console.log('Form submission data:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Validate supplier_id if provided
    if (supplierIdValue && supplierIdValue !== '') {
        const supplierId = parseInt(supplierIdValue);
        if (isNaN(supplierId) || supplierId <= 0) {
            alert('Please select a valid supplier or choose "No Supplier"');
            return false;
        }
    }
    
    // Validate required numeric fields
    const basePrice = parseFloat(formData.get('base_price'));
    const sellingPrice = parseFloat(formData.get('selling_price'));
    const stockQuantity = parseInt(formData.get('stock_quantity'));
    
    if (isNaN(basePrice) || basePrice < 0) {
        alert('Please enter a valid base price');
        return false;
    }
    
    if (isNaN(sellingPrice) || sellingPrice < 0) {
        alert('Please enter a valid selling price');
        return false;
    }
    
    if (isNaN(stockQuantity) || stockQuantity < 0) {
        alert('Please enter a valid stock quantity');
        return false;
    }
    
    return true;
}

function handleInventoryUpdate(form, event) {
    event.preventDefault();
    
    // Validate form first
    if (!validateInventoryForm(form)) {
        return false;
    }
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Updating...';
    submitButton.disabled = true;
    
    // Get form data
    const formData = new FormData(form);
    
    // Make AJAX request with action as URL parameter
    fetch(`quotations.php?action=update_inventory_item&quote_id=<?php echo $quote_id; ?>`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, get text to see what we received
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('Server returned non-JSON response. This might be an error page.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            // Close the modal
            closeEditModal();
            
            // Show success message briefly
            showNotification(data.message, 'success');
            
            // Refresh the page after a short delay to show the notification
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Handle error response
            showNotification(data.message || 'Failed to update inventory item. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating inventory item:', error);
        showNotification('An error occurred while updating the item. Please try again.', 'error');
    })
    .finally(() => {
        // Re-enable button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    });
    
    return false;
}

// Note: Real-time update functions removed since we're using page refresh instead

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    
    // Add icon based on type
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                 type === 'error' ? 'fas fa-exclamation-circle' : 
                 'fas fa-info-circle';
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${icon} mr-3 text-lg"></i>
            <span class="font-medium">${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 2 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}
</script>

<!-- Edit Inventory Item Modal -->
<div id="edit-item-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Inventory Item</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="edit-item-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
