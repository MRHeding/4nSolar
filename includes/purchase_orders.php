<?php
require_once 'config.php';
require_once 'suppliers.php';

/**
 * Generate a unique purchase order number using the format PO-YYYY-XXXX
 *
 * @return string
 */
function generatePurchaseOrderNumber(): string
{
    global $pdo;

    $yearPrefix = date('Y');
    $prefix = 'PO-' . $yearPrefix . '-';

    $stmt = $pdo->prepare("SELECT po_number FROM purchase_orders WHERE po_number LIKE ? ORDER BY po_number DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastPoNumber = $stmt->fetchColumn();

    if ($lastPoNumber) {
        $lastSequence = (int)substr($lastPoNumber, strrpos($lastPoNumber, '-') + 1);
        $nextSequence = $lastSequence + 1;
    } else {
        $nextSequence = 1;
    }

    return sprintf('%s%04d', $prefix, $nextSequence);
}

/**
 * Retrieve all purchase orders linked to a quotation.
 *
 * @param int $quote_id
 * @return array
 */
function getPurchaseOrdersByQuote(int $quote_id): array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                po.*, 
                COUNT(poi.id) AS item_count,
                COALESCE(SUM(poi.quantity), 0) AS total_quantity,
                COALESCE(SUM(poi.total_amount), 0) AS total_amount
            FROM purchase_orders po
            LEFT JOIN purchase_order_items poi ON poi.po_id = po.id
            WHERE po.quote_id = ?
            GROUP BY po.id
            ORDER BY po.created_at DESC
        ");
        $stmt->execute([$quote_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to load purchase orders for quote ' . $quote_id . ': ' . $e->getMessage());
        return [];
    }
}

/**
 * Retrieve a purchase order with its associated items and computed totals.
 *
 * @param int $po_id
 * @return array|null
 */
function getPurchaseOrderWithItems(int $po_id): ?array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                po.*,
                q.quote_number,
                q.customer_name,
                q.customer_phone,
                q.proposal_name
            FROM purchase_orders po
            LEFT JOIN quotations q ON q.id = po.quote_id
            WHERE po.id = ?
            LIMIT 1
        ");
        $stmt->execute([$po_id]);
        $purchase_order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$purchase_order) {
            return null;
        }

        $itemsStmt = $pdo->prepare("
            SELECT *
            FROM purchase_order_items
            WHERE po_id = ?
            ORDER BY id ASC
        ");
        $itemsStmt->execute([$po_id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalQuantity = 0;
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalQuantity += (float)($item['quantity'] ?? 0);
            $totalAmount += (float)($item['total_amount'] ?? 0);
        }

        $purchase_order['items'] = $items;
        $purchase_order['items_count'] = count($items);
        $purchase_order['total_quantity'] = $totalQuantity;
        $purchase_order['total_amount'] = $totalAmount;

        return $purchase_order;
    } catch (PDOException $e) {
        error_log('Failed to load purchase order ' . $po_id . ': ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a purchase order for a quotation using the provided data payload.
 *
 * @param int   $quote_id
 * @param array $data
 * @return array
 */
function createPurchaseOrderFromQuote(int $quote_id, array $data): array
{
    global $pdo;

    if ($quote_id <= 0) {
        return ['success' => false, 'message' => 'Invalid quotation reference.'];
    }

    $items = $data['items'] ?? [];
    if (empty($items) || !is_array($items)) {
        return ['success' => false, 'message' => 'Please select at least one item for the purchase order.'];
    }

    $supplierName = trim($data['supplier_name'] ?? '');
    if ($supplierName === '') {
        return ['success' => false, 'message' => 'Supplier name is required.'];
    }

    try {
        $pdo->beginTransaction();

        $poNumber = generatePurchaseOrderNumber();

        $contactPerson = trim($data['contact_person'] ?? '');
        $supplierPhone = trim($data['supplier_phone'] ?? '');
        $supplierEmail = trim($data['supplier_email'] ?? '');
        $supplierAddress = trim($data['supplier_address'] ?? '');

        $expectedDelivery = trim($data['expected_delivery_date'] ?? '');
        $deliveryMethod = trim($data['delivery_method'] ?? '');
        $deliveryNotes = trim($data['delivery_notes'] ?? '');

        $deliveryRequirementsParts = [];
        if ($expectedDelivery !== '') {
            $deliveryRequirementsParts[] = 'Expected delivery date: ' . $expectedDelivery;
        }
        if ($deliveryMethod !== '') {
            $deliveryRequirementsParts[] = 'Delivery method: ' . $deliveryMethod;
        }
        if ($deliveryNotes !== '') {
            $deliveryRequirementsParts[] = $deliveryNotes;
        }
        $deliveryRequirements = implode(' | ', array_filter($deliveryRequirementsParts));

        $specialInstructions = trim($data['special_instructions'] ?? '');
        $status = $data['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'ordered', 'received', 'cancelled'], true)) {
            $status = 'pending';
        }

        $stmt = $pdo->prepare("INSERT INTO purchase_orders 
            (quote_id, po_number, supplier_name, contact_person, supplier_phone, supplier_email, supplier_address, special_instructions, delivery_requirements, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $quote_id,
            $poNumber,
            $supplierName,
            $contactPerson,
            $supplierPhone,
            $supplierEmail,
            $supplierAddress,
            $specialInstructions,
            $deliveryRequirements,
            $status
        ]);

        $poId = (int)$pdo->lastInsertId();

        $itemInsertStmt = $pdo->prepare("INSERT INTO purchase_order_items 
            (po_id, quote_item_id, inventory_item_id, brand, model, category_name, quantity, unit_price, total_amount, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $itemsInserted = 0;

        foreach ($items as $itemRow) {
            $quoteItemId = isset($itemRow['quote_item_id']) ? (int)$itemRow['quote_item_id'] : 0;
            $quantity = isset($itemRow['quantity']) ? (float)$itemRow['quantity'] : 0;
            $unitPrice = isset($itemRow['unit_price']) ? (float)$itemRow['unit_price'] : 0;
            $notes = trim($itemRow['notes'] ?? '');

            if ($quoteItemId <= 0 || $quantity <= 0) {
                continue;
            }

            // Verify the quote item belongs to this quote and obtain canonical data
            $quoteItemStmt = $pdo->prepare("SELECT qi.id, qi.quote_id, qi.inventory_item_id, qi.unit_price, qi.quantity,
                       ii.brand, ii.model, ii.category_id, c.name AS category_name
                       FROM quote_items qi
                       LEFT JOIN inventory_items ii ON qi.inventory_item_id = ii.id
                       LEFT JOIN categories c ON ii.category_id = c.id
                       WHERE qi.id = ? AND qi.quote_id = ?");
            $quoteItemStmt->execute([$quoteItemId, $quote_id]);
            $quoteItem = $quoteItemStmt->fetch(PDO::FETCH_ASSOC);

            if (!$quoteItem) {
                continue;
            }

            $inventoryItemId = $quoteItem['inventory_item_id'] ? (int)$quoteItem['inventory_item_id'] : null;
            $brand = $quoteItem['brand'] ?? ($itemRow['brand'] ?? '');
            $model = $quoteItem['model'] ?? ($itemRow['model'] ?? '');
            $categoryName = $quoteItem['category_name'] ?? ($itemRow['category_name'] ?? '');

            if ($unitPrice <= 0) {
                $unitPrice = isset($quoteItem['unit_price']) ? (float)$quoteItem['unit_price'] : 0;
            }

            $totalAmount = $unitPrice * $quantity;

            $itemInsertStmt->execute([
                $poId,
                $quoteItemId,
                $inventoryItemId,
                $brand,
                $model,
                $categoryName,
                $quantity,
                $unitPrice,
                $totalAmount,
                $notes
            ]);

            $itemsInserted++;
        }

        if ($itemsInserted === 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'No valid items were provided for the purchase order.'];
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Purchase order ' . $poNumber . ' created successfully.',
            'po_id' => $poId,
            'po_number' => $poNumber
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Failed to create purchase order: ' . $e->getMessage()];
    }
}

