<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';
require_once 'includes/pos.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get a sample sale for testing
$sale_id = 156; // The sale ID from your URL
$sale = getPOSSaleWithSerials($sale_id);

if (!$sale) {
    die('Sale not found. Please check the sale ID.');
}

$page_title = 'Thermal Receipt Test';
$content_start = true;
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Thermal Receipt Test</h1>
            <p class="text-gray-600 dark:text-gray-400">Testing thermal printer receipt for Sale #<?php echo htmlspecialchars($sale['receipt_number']); ?></p>
        </div>
        <div class="space-x-2">
            <button onclick="printReceipt()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-receipt mr-2"></i>Print Receipt
            </button>
            <a href="pos.php?action=receipt&id=<?php echo $sale_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Receipt
            </a>
        </div>
    </div>
</div>

<!-- Thermal Receipt Template (Hidden) -->
<div id="thermal-receipt" class="thermal-receipt hidden">
    <div class="thermal-content">
        <div class="thermal-header">
            <div class="thermal-company-name"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></div>
            <div class="thermal-company-subtitle"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Solar Power Installation Services')); ?></div>
            <div class="thermal-company-tagline"><?php echo htmlspecialchars(getSystemSetting('company_tagline', 'Your Trusted Partner in Solar Solutions')); ?></div>
            <div class="thermal-company-tin"><?php echo htmlspecialchars(getSystemSetting('company_tin', 'NON VAT Reg TIN: 247-334-690-00001')); ?></div>
            <div class="thermal-company-contact">
                📧 <?php echo htmlspecialchars(getSystemSetting('company_email', 'info@4nsolar.com')); ?> | 
                📞 <?php echo htmlspecialchars(getSystemSetting('company_phone', '+63 906 386 1728')); ?>
            </div>
            <div class="thermal-company-address">📍 <?php echo htmlspecialchars(getSystemSetting('company_address', 'Zambonga City, Philippines')); ?></div>
            <div class="thermal-separator">═══════════════════════════════════</div>
            <div class="thermal-receipt-number">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></div>
        </div>
        
        <div class="thermal-details">
            <div class="thermal-detail-row">
                <span class="thermal-label">Date:</span>
                <span class="thermal-value"><?php echo date('M j, Y g:i A', strtotime($sale['completed_at'])); ?></span>
            </div>
            <div class="thermal-detail-row">
                <span class="thermal-label">Cashier:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['cashier_name']); ?></span>
            </div>
            <?php if ($sale['customer_name']): ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Customer:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_name']); ?></span>
            </div>
            <?php if ($sale['customer_phone']): ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Phone:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_phone']); ?></span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Customer:</span>
                <span class="thermal-value">Walk-in</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="thermal-separator">─────────────────────────────────────</div>
        
        <div class="thermal-items">
            <?php foreach ($sale['items'] as $item): ?>
            <div class="thermal-item">
                <div class="thermal-item-name"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                <div class="thermal-item-details">
                    <span class="thermal-item-qty"><?php echo $item['quantity']; ?>x</span>
                    <span class="thermal-item-price"><?php echo formatCurrency($item['unit_price']); ?></span>
                    <?php if ($item['discount_percentage'] > 0): ?>
                    <span class="thermal-item-discount">-<?php echo $item['discount_percentage']; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="thermal-item-total"><?php echo formatCurrency($item['total_amount']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="thermal-separator">─────────────────────────────────────</div>
        
        <div class="thermal-totals">
            <div class="thermal-total-row">
                <span class="thermal-total-label">Subtotal:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['subtotal']); ?></span>
            </div>
            <?php if ($sale['total_discount'] > 0): ?>
            <div class="thermal-total-row thermal-discount">
                <span class="thermal-total-label">Discount:</span>
                <span class="thermal-total-value">-<?php echo formatCurrency($sale['total_discount']); ?></span>
            </div>
            <?php endif; ?>
            <div class="thermal-total-row thermal-grand-total">
                <span class="thermal-total-label">TOTAL:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['total_amount']); ?></span>
            </div>
            <div class="thermal-total-row">
                <span class="thermal-total-label">Paid (<?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>):</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['amount_paid']); ?></span>
            </div>
            <?php if ($sale['change_amount'] > 0): ?>
            <div class="thermal-total-row thermal-change">
                <span class="thermal-total-label">Change:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['change_amount']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="thermal-separator">═══════════════════════════════════</div>
        
        <div class="thermal-footer">
            <div class="thermal-thank-you">Thank you for your business!</div>
            <div class="thermal-warranty">For warranty and support, please keep this receipt.</div>
            <div class="thermal-signature">
                <div class="thermal-signature-line">_________________________</div>
                <div class="thermal-signature-label">Cashier Authorized Representative</div>
            </div>
        </div>
    </div>
</div>

<!-- Preview of Thermal Receipt -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Thermal Receipt Preview</h2>
    <div class="thermal-receipt-preview">
        <div class="thermal-content">
            <div class="thermal-header">
                <div class="thermal-company-name"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></div>
                <div class="thermal-company-subtitle"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Solar Power Installation Services')); ?></div>
                <div class="thermal-company-tagline"><?php echo htmlspecialchars(getSystemSetting('company_tagline', 'Your Trusted Partner in Solar Solutions')); ?></div>
                <div class="thermal-company-tin"><?php echo htmlspecialchars(getSystemSetting('company_tin', 'NON VAT Reg TIN: 247-334-690-00001')); ?></div>
                <div class="thermal-company-contact">
                    📧 <?php echo htmlspecialchars(getSystemSetting('company_email', 'info@4nsolar.com')); ?> | 
                    📞 <?php echo htmlspecialchars(getSystemSetting('company_phone', '+63 906 386 1728')); ?>
                </div>
                <div class="thermal-company-address">📍 <?php echo htmlspecialchars(getSystemSetting('company_address', 'Zambonga City, Philippines')); ?></div>
                <div class="thermal-separator">═══════════════════════════════════</div>
                <div class="thermal-receipt-number">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></div>
            </div>
            
            <div class="thermal-details">
                <div class="thermal-detail-row">
                    <span class="thermal-label">Date:</span>
                    <span class="thermal-value"><?php echo date('M j, Y g:i A', strtotime($sale['completed_at'])); ?></span>
                </div>
                <div class="thermal-detail-row">
                    <span class="thermal-label">Cashier:</span>
                    <span class="thermal-value"><?php echo htmlspecialchars($sale['cashier_name']); ?></span>
                </div>
                <?php if ($sale['customer_name']): ?>
                <div class="thermal-detail-row">
                    <span class="thermal-label">Customer:</span>
                    <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_name']); ?></span>
                </div>
                <?php if ($sale['customer_phone']): ?>
                <div class="thermal-detail-row">
                    <span class="thermal-label">Phone:</span>
                    <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_phone']); ?></span>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="thermal-detail-row">
                    <span class="thermal-label">Customer:</span>
                    <span class="thermal-value">Walk-in</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="thermal-separator">─────────────────────────────────────</div>
            
            <div class="thermal-items">
                <?php foreach ($sale['items'] as $item): ?>
                <div class="thermal-item">
                    <div class="thermal-item-name"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                    <div class="thermal-item-details">
                        <span class="thermal-item-qty"><?php echo $item['quantity']; ?>x</span>
                        <span class="thermal-item-price"><?php echo formatCurrency($item['unit_price']); ?></span>
                        <?php if ($item['discount_percentage'] > 0): ?>
                        <span class="thermal-item-discount">-<?php echo $item['discount_percentage']; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="thermal-item-total"><?php echo formatCurrency($item['total_amount']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="thermal-separator">─────────────────────────────────────</div>
            
            <div class="thermal-totals">
                <div class="thermal-total-row">
                    <span class="thermal-total-label">Subtotal:</span>
                    <span class="thermal-total-value"><?php echo formatCurrency($sale['subtotal']); ?></span>
                </div>
                <?php if ($sale['total_discount'] > 0): ?>
                <div class="thermal-total-row thermal-discount">
                    <span class="thermal-total-label">Discount:</span>
                    <span class="thermal-total-value">-<?php echo formatCurrency($sale['total_discount']); ?></span>
                </div>
                <?php endif; ?>
                <div class="thermal-total-row thermal-grand-total">
                    <span class="thermal-total-label">TOTAL:</span>
                    <span class="thermal-total-value"><?php echo formatCurrency($sale['total_amount']); ?></span>
                </div>
                <div class="thermal-total-row">
                    <span class="thermal-total-label">Paid (<?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>):</span>
                    <span class="thermal-total-value"><?php echo formatCurrency($sale['amount_paid']); ?></span>
                </div>
                <?php if ($sale['change_amount'] > 0): ?>
                <div class="thermal-total-row thermal-change">
                    <span class="thermal-total-label">Change:</span>
                    <span class="thermal-total-value"><?php echo formatCurrency($sale['change_amount']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="thermal-separator">═══════════════════════════════════</div>
            
            <div class="thermal-footer">
                <div class="thermal-thank-you">Thank you for your business!</div>
                <div class="thermal-warranty">For warranty and support, please keep this receipt.</div>
                <div class="thermal-signature">
                    <div class="thermal-signature-line">_________________________</div>
                    <div class="thermal-signature-label">Cashier Authorized Representative</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Receipt Styles for Regular Printer */
.thermal-receipt {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.3;
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    background: white;
    color: black;
    padding: 20px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.thermal-receipt-preview {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.3;
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    background: white;
    color: black;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.thermal-content {
    padding: 0;
}

.thermal-header {
    text-align: center;
    margin-bottom: 10px;
}

.thermal-company-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 4px;
}

.thermal-company-subtitle {
    font-size: 12px;
    margin-bottom: 3px;
}

.thermal-company-tagline {
    font-size: 10px;
    margin-bottom: 3px;
}

.thermal-company-tin {
    font-size: 9px;
    margin-bottom: 3px;
}

.thermal-company-contact {
    font-size: 9px;
    margin-bottom: 3px;
}

.thermal-company-address {
    font-size: 9px;
    margin-bottom: 8px;
}

.thermal-separator {
    text-align: center;
    font-size: 12px;
    margin: 8px 0;
}

.thermal-receipt-number {
    font-weight: bold;
    font-size: 14px;
    margin-top: 8px;
}

.thermal-details {
    margin-bottom: 15px;
}

.thermal-detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 12px;
}

.thermal-label {
    font-weight: bold;
}

.thermal-value {
    text-align: right;
}

.thermal-items {
    margin-bottom: 15px;
}

.thermal-item {
    margin-bottom: 8px;
    font-size: 12px;
}

.thermal-item-name {
    font-weight: bold;
    margin-bottom: 3px;
    word-wrap: break-word;
}

.thermal-item-details {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 3px;
}

.thermal-item-qty {
    font-weight: bold;
}

.thermal-item-price {
    text-align: center;
}

.thermal-item-discount {
    color: #666;
    font-style: italic;
}

.thermal-item-total {
    text-align: right;
    font-weight: bold;
    font-size: 13px;
}

.thermal-totals {
    margin-bottom: 15px;
}

.thermal-total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 12px;
}

.thermal-total-label {
    font-weight: bold;
}

.thermal-total-value {
    text-align: right;
}

.thermal-grand-total {
    font-size: 14px;
    font-weight: bold;
    border-top: 2px solid #000;
    padding-top: 5px;
    margin-top: 5px;
}

.thermal-discount {
    color: #666;
}

.thermal-change {
    color: #0066cc;
    font-weight: bold;
}

.thermal-footer {
    text-align: center;
    margin-top: 20px;
}

.thermal-thank-you {
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 13px;
}

.thermal-warranty {
    font-size: 10px;
    margin-bottom: 20px;
    color: #666;
}

.thermal-signature {
    margin-top: 25px;
}

.thermal-signature-line {
    margin-bottom: 8px;
    font-size: 12px;
}

.thermal-signature-label {
    font-size: 10px;
    color: #666;
}

/* Print styles for regular printer */
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
    
    /* Receipt print styles for regular printer */
    .thermal-receipt, .thermal-receipt * {
        visibility: visible;
    }
    
    .thermal-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 400px;
        margin: 0;
        padding: 20px;
        background: white;
        color: black;
        border: none;
        box-shadow: none;
    }
    
    @page {
        margin: 0.5in;
        size: A4;
    }
    
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<script>
function printReceipt() {
    // Hide all other content
    document.querySelectorAll('body > *').forEach(element => {
        if (!element.classList.contains('thermal-receipt')) {
            element.style.display = 'none';
        }
    });
    
    // Show thermal receipt
    const thermalReceipt = document.getElementById('thermal-receipt');
    thermalReceipt.classList.remove('hidden');
    thermalReceipt.style.display = 'block';
    thermalReceipt.style.position = 'absolute';
    thermalReceipt.style.left = '0';
    thermalReceipt.style.top = '0';
    thermalReceipt.style.zIndex = '9999';
    
    // Print the thermal receipt
    window.print();
    
    // Restore original display after printing
    setTimeout(() => {
        document.querySelectorAll('body > *').forEach(element => {
            element.style.display = '';
        });
        thermalReceipt.classList.add('hidden');
        thermalReceipt.style.display = '';
        thermalReceipt.style.position = '';
        thermalReceipt.style.left = '';
        thermalReceipt.style.top = '';
        thermalReceipt.style.zIndex = '';
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>
