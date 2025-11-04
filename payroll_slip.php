<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/payroll.php';
require_once 'includes/settings.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    header('Location: dashboard.php');
    exit();
}

// Get payroll ID
$payroll_id = $_GET['id'] ?? null;

if (!$payroll_id) {
    header('Location: payroll.php');
    exit();
}

// Get payroll details
$payroll = getPayrollById($pdo, $payroll_id);

if (!$payroll) {
    header('Location: payroll.php');
    exit();
}

// Get company settings
$company_title = getCompanyTitle();
$company_subtitle = getCompanySubtitle();
$logo_url = getLogoUrl();

// Check if print mode
$print_mode = isset($_GET['print']) && $_GET['print'] == '1';
$pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';

// If PDF mode, we'll need a PDF library (for now, just redirect to print mode)
if ($pdf_mode) {
    // TODO: Implement PDF generation if needed
    // For now, just show print version
    $print_mode = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Slip - <?php echo htmlspecialchars($payroll['employee_name']); ?> - <?php echo htmlspecialchars($company_title); ?></title>
    <link href="assets/fontawesome/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .pay-slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .company-info {
            flex: 1;
        }
        .company-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .company-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .company-subtitle {
            font-size: 14px;
            color: #666;
        }
        .pay-slip-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .pay-slip-number {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .earnings-section, .deductions-section {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #333;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #f9fafb;
            font-weight: bold;
        }
        .net-pay-section {
            background: #2563eb;
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin-top: 30px;
        }
        .net-pay-label {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .net-pay-amount {
            font-size: 36px;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin: 50px 0 10px 0;
            padding-top: 5px;
        }
        .signature-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .signature-role {
            font-size: 12px;
            color: #666;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }
        .no-print button, .no-print a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .no-print button:hover, .no-print a:hover {
            background: #1e40af;
        }
        @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
                font-size: 11px;
                width: 100%;
            }
            .pay-slip-container {
                box-shadow: none;
                padding: 10mm;
                max-width: 100%;
                margin: 0;
                width: 100%;
                page-break-inside: avoid;
            }
            .header {
                padding-bottom: 8px;
                margin-bottom: 10px;
                border-bottom-width: 2px;
            }
            .company-logo {
                max-width: 100px;
                max-height: 50px;
                margin-bottom: 5px;
            }
            .company-title {
                font-size: 16px;
                margin-bottom: 2px;
            }
            .company-subtitle {
                font-size: 10px;
            }
            .pay-slip-title {
                font-size: 20px;
                margin-bottom: 5px;
            }
            .pay-slip-number {
                font-size: 10px;
                margin-bottom: 12px;
            }
            .info-section {
                margin-bottom: 12px;
                page-break-inside: avoid;
            }
            .info-row {
                gap: 10px;
                margin-bottom: 8px;
            }
            .info-label {
                font-size: 9px;
                margin-bottom: 2px;
            }
            .info-value {
                font-size: 11px;
            }
            .section-title {
                font-size: 13px;
                margin-bottom: 8px;
                padding-bottom: 5px;
                border-bottom-width: 1px;
            }
            .earnings-section, .deductions-section {
                margin-bottom: 12px;
                page-break-inside: avoid;
            }
            table {
                margin-bottom: 10px;
                font-size: 10px;
                page-break-inside: avoid;
            }
            table th {
                padding: 6px 8px;
                font-size: 10px;
            }
            table td {
                padding: 6px 8px;
                font-size: 10px;
            }
            table tr {
                page-break-inside: avoid;
            }
            .net-pay-section {
                padding: 12px;
                margin-top: 12px;
                border-radius: 4px;
                page-break-inside: avoid;
            }
            .net-pay-label {
                font-size: 13px;
                margin-bottom: 5px;
            }
            .net-pay-amount {
                font-size: 24px;
            }
            .signature-section {
                margin-top: 15px;
                padding-top: 10px;
                border-top-width: 1px;
                page-break-inside: avoid;
            }
            .signature-grid {
                gap: 10px;
                margin-top: 10px;
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
            }
            .signature-line {
                margin: 25px 0 5px 0;
                padding-top: 3px;
            }
            .signature-name {
                font-size: 10px;
                margin-bottom: 3px;
            }
            .signature-role {
                font-size: 9px;
            }
            .footer {
                margin-top: 10px;
                padding-top: 8px;
                font-size: 9px;
            }
            @page {
                margin: 8mm;
                size: A4;
            }
        }
    </style>
    <?php if ($print_mode): ?>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Print Pay Slip
        </button>
        <a href="payroll_details.php?id=<?php echo $payroll_id; ?>">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
        <a href="payroll.php">
            <i class="fas fa-list"></i> Back to Payroll
        </a>
    </div>

    <div class="pay-slip-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="company-info">
                    <?php if ($logo_url && file_exists($logo_url)): ?>
                    <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Company Logo" class="company-logo">
                    <?php endif; ?>
                    <div class="company-title"><?php echo htmlspecialchars($company_title); ?></div>
                    <?php if ($company_subtitle): ?>
                    <div class="company-subtitle"><?php echo htmlspecialchars($company_subtitle); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pay Slip Title -->
        <div class="pay-slip-title">PAY SLIP</div>
        <div class="pay-slip-number">Pay Slip #<?php echo str_pad($payroll_id, 6, '0', STR_PAD_LEFT); ?></div>

        <!-- Employee Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Employee Code</div>
                    <div class="info-value"><?php echo htmlspecialchars($payroll['employee_code']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Employee Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($payroll['employee_name']); ?></div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Position</div>
                    <div class="info-value"><?php echo htmlspecialchars($payroll['position']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pay Period</div>
                    <div class="info-value">
                        <?php echo date('M d, Y', strtotime($payroll['pay_period_start'])); ?> - 
                        <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?>
                    </div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Working Days</div>
                    <div class="info-value"><?php echo $payroll['total_working_days']; ?> days</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Days Present</div>
                    <div class="info-value"><?php echo $payroll['working_days_present']; ?> days</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <?php
                        $status_text = ucfirst($payroll['status']);
                        echo $status_text;
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Generated Date</div>
                    <div class="info-value"><?php echo date('M d, Y g:i A', strtotime($payroll['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Earnings Section -->
        <div class="earnings-section">
            <div class="section-title">EARNINGS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                    </tr>
                    <?php if (!empty($payroll['packages'])): ?>
                        <?php foreach ($payroll['packages'] as $package): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                            <td style="text-align: right;">₱<?php echo number_format($package['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($payroll['allowances'] > 0): ?>
                    <tr>
                        <td>Allowances</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['overtime_pay'] > 0): ?>
                    <tr>
                        <td>Overtime Pay</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['bonus_pay'] > 0): ?>
                    <tr>
                        <td>Bonus Pay</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['bonus_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td><strong>Total Earnings</strong></td>
                        <td style="text-align: right;"><strong>₱<?php echo number_format($payroll['gross_salary'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deductions Section -->
        <div class="deductions-section">
            <div class="section-title">DEDUCTIONS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payroll['custom_deductions'])): ?>
                        <?php foreach ($payroll['custom_deductions'] as $deduction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($deduction['description']); ?></td>
                            <td style="text-align: right;">₱<?php echo number_format($deduction['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($payroll['cash_advance'] > 0): ?>
                    <tr>
                        <td>Cash Advance</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['cash_advance'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['uniforms'] > 0): ?>
                    <tr>
                        <td>Uniforms</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['uniforms'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['tools'] > 0): ?>
                    <tr>
                        <td>Tools</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['tools'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['lates'] > 0): ?>
                    <tr>
                        <td>Late Penalties</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['lates'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['miscellaneous'] > 0): ?>
                    <tr>
                        <td>Miscellaneous</td>
                        <td style="text-align: right;">₱<?php echo number_format($payroll['miscellaneous'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['total_deductions'] == 0): ?>
                    <tr>
                        <td colspan="2" style="text-align: center; color: #999; font-style: italic;">No deductions</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td><strong>Total Deductions</strong></td>
                        <td style="text-align: right;"><strong>₱<?php echo number_format($payroll['total_deductions'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Net Pay Section -->
        <div class="net-pay-section">
            <div class="net-pay-label">NET PAY</div>
            <div class="net-pay-amount">₱<?php echo number_format($payroll['net_salary'], 2); ?></div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-grid">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">Liddy Lou Orsuga</div>
                    <div class="signature-role">HR</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">Novie G. Mohadsa</div>
                    <div class="signature-role">Operation Manager</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name"><?php echo htmlspecialchars($payroll['employee_name']); ?></div>
                    <div class="signature-role"><?php echo htmlspecialchars($payroll['position']); ?></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <?php if ($payroll['notes']): ?>
            <p style="margin-top: 10px; font-style: italic;">Note: <?php echo htmlspecialchars($payroll['notes']); ?></p>
            <?php endif; ?>
            <p style="margin-top: 10px;">Generated on <?php echo date('F d, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html>

