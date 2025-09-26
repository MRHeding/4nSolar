<?php
// Installment Management Functions for 4nSolar System

/**
 * Create a new installment plan for a quotation
 */
function createInstallmentPlan($quotation_id, $plan_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Validate quotation exists and is accepted
        $stmt = $pdo->prepare("SELECT id, total_amount, status FROM quotations WHERE id = ?");
        $stmt->execute([$quotation_id]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quotation) {
            throw new Exception("Quotation not found");
        }
        
        if ($quotation['status'] !== 'accepted') {
            throw new Exception("Can only create installment plans for accepted quotations");
        }
        
        // Validate plan data
        $total_amount = floatval($plan_data['total_amount']);
        $down_payment = floatval($plan_data['down_payment'] ?? 0);
        $number_of_installments = intval($plan_data['number_of_installments']);
        $interest_rate = floatval($plan_data['interest_rate'] ?? 0);
        
        // Debug: Log the calculation inputs
        error_log("Installment calculation inputs: total_amount=$total_amount, down_payment=$down_payment, number_of_installments=$number_of_installments, interest_rate=$interest_rate");
        
        if ($total_amount <= 0 || $number_of_installments <= 0) {
            throw new Exception("Invalid plan parameters");
        }
        
        // Calculate installment amount with interest
        $remaining_amount = $total_amount - $down_payment;
        $monthly_interest_rate = $interest_rate / 100;
        
        if ($interest_rate > 0) {
            // Calculate with compound interest
            $installment_amount = $remaining_amount * 
                ($monthly_interest_rate * pow(1 + $monthly_interest_rate, $number_of_installments)) /
                (pow(1 + $monthly_interest_rate, $number_of_installments) - 1);
        } else {
            // Simple division without interest
            $installment_amount = $remaining_amount / $number_of_installments;
        }
        
        // Round installment amount down to nearest 0.50 (e.g., 13440.37 becomes 13440.50)
        $installment_amount = floor($installment_amount * 2) / 2;
        
        // Debug: Log the calculated installment amount
        error_log("Calculated installment amount: $installment_amount");
        
        // Insert installment plan
        $sql = "INSERT INTO installment_plans 
                (quotation_id, plan_name, total_amount, down_payment, installment_amount, 
                 number_of_installments, payment_frequency, interest_rate, late_fee_amount, 
                 late_fee_type, start_date, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $quotation_id,
            $plan_data['plan_name'] ?? 'Payment Plan',
            $total_amount,
            $down_payment,
            round($installment_amount, 2),
            $number_of_installments,
            $plan_data['payment_frequency'] ?? 'monthly',
            $interest_rate,
            floatval($plan_data['late_fee_amount'] ?? 500),
            $plan_data['late_fee_type'] ?? 'fixed',
            $plan_data['start_date'],
            $plan_data['notes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ]);
        
        $plan_id = $pdo->lastInsertId();
        
        // Generate individual installment payments
        generateInstallmentPayments($plan_id, $plan_data);
        
        // Update quotation to mark it has installment plan
        $stmt = $pdo->prepare("UPDATE quotations SET has_installment_plan = 1, installment_status = 'active' WHERE id = ?");
        $stmt->execute([$quotation_id]);
        
        $pdo->commit();
        return ['success' => true, 'plan_id' => $plan_id, 'message' => 'Installment plan created successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate individual installment payment schedule
 */
function generateInstallmentPayments($plan_id, $plan_data) {
    global $pdo;
    
    $start_date = new DateTime($plan_data['start_date']);
    $frequency = $plan_data['payment_frequency'] ?? 'monthly';
    $number_of_installments = intval($plan_data['number_of_installments']);
    
    // Get plan details
    $stmt = $pdo->prepare("SELECT * FROM installment_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $installment_amount = $plan['installment_amount'];
    
    for ($i = 1; $i <= $number_of_installments; $i++) {
        // Calculate due date based on frequency
        $due_date = clone $start_date;
        
        switch ($frequency) {
            case 'weekly':
                $due_date->add(new DateInterval("P" . (($i - 1) * 7) . "D"));
                break;
            case 'monthly':
                $due_date->add(new DateInterval("P" . ($i - 1) . "M"));
                break;
            case 'quarterly':
                $due_date->add(new DateInterval("P" . (($i - 1) * 3) . "M"));
                break;
            case 'yearly':
                $due_date->add(new DateInterval("P" . ($i - 1) . "Y"));
                break;
        }
        
        // Insert installment payment
        $sql = "INSERT INTO installment_payments 
                (plan_id, installment_number, due_date, due_amount, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plan_id, $i, $due_date->format('Y-m-d'), $installment_amount]);
    }
}

/**
 * Record a payment for an installment
 */
function recordInstallmentPayment($payment_id, $payment_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get payment details
        $stmt = $pdo->prepare("SELECT ip.*, pl.quotation_id 
                              FROM installment_payments ip 
                              JOIN installment_plans pl ON ip.plan_id = pl.id 
                              WHERE ip.id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception("Payment record not found");
        }
        
        $paid_amount = floatval($payment_data['paid_amount']);
        $payment_method = $payment_data['payment_method'] ?? 'cash';
        $payment_date = $payment_data['payment_date'] ?? date('Y-m-d');
        
        // Generate automatic reference number if not provided
        $reference_number = $payment_data['reference_number'] ?? null;
        if (empty($reference_number)) {
            $reference_number = generatePaymentReference($payment['quotation_id'], $payment['installment_number']);
        }
        
        // Check for late fees
        $late_fee = 0;
        $due_date = new DateTime($payment['due_date']);
        $pay_date = new DateTime($payment_date);
        $grace_period = intval(getInstallmentSetting('grace_period_days', 5));
        
        // Clone due_date for comparison to avoid modifying original
        $due_date_with_grace = clone $due_date;
        $due_date_with_grace->add(new DateInterval("P{$grace_period}D"));
        
        if ($pay_date > $due_date_with_grace) {
            // Apply late fee
            $late_fee_amount = floatval(getInstallmentSetting('default_late_fee', 500));
            $late_fee_type = getInstallmentSetting('late_fee_type', 'fixed');
            
            if ($late_fee_type === 'percentage') {
                $late_fee = ($payment['due_amount'] * $late_fee_amount) / 100;
            } else {
                $late_fee = $late_fee_amount;
            }
        }
        
        // Calculate current paid amount (add to existing)
        $current_paid = floatval($payment['paid_amount']);
        $new_total_paid = $current_paid + $paid_amount;
        
        // Determine payment status
        $total_due = floatval($payment['due_amount']) + $late_fee;
        $status = 'pending';
        
        if ($new_total_paid >= $total_due) {
            $status = 'paid';
        } elseif ($new_total_paid > 0) {
            $status = 'partial';
        }
        
        // Generate receipt number if auto-generation is enabled
        $receipt_number = null;
        if (getInstallmentSetting('auto_generate_receipts', 1)) {
            $receipt_number = generateReceiptNumber();
        }
        
        // Update payment record
        $sql = "UPDATE installment_payments SET 
                paid_amount = ?, payment_date = ?, late_fee_applied = ?, 
                payment_method = ?, reference_number = ?, receipt_number = ?, 
                status = ?, notes = ?, paid_by = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $new_total_paid,
            $payment_date,
            $late_fee,
            $payment_method,
            $reference_number,
            $receipt_number,
            $status,
            $payment_data['notes'] ?? null,
            $_SESSION['user_id'] ?? 1,
            $payment_id
        ]);
        
        if (!$result) {
            throw new Exception("Failed to update payment record");
        }
        
        // Record payment transaction
        $sql = "INSERT INTO installment_transactions 
                (payment_id, transaction_type, amount, description, processed_by, reference_number) 
                VALUES (?, 'payment', ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $payment_id,
            $paid_amount,
            "Payment for installment #{$payment['installment_number']} - " . ucfirst($payment_method),
            $_SESSION['user_id'] ?? 1,
            $reference_number
        ]);
        
        if (!$result) {
            throw new Exception("Failed to record payment transaction");
        }
        
        // Record late fee transaction if applicable
        if ($late_fee > 0) {
            $sql = "INSERT INTO installment_transactions 
                    (payment_id, transaction_type, amount, description, processed_by, reference_number) 
                    VALUES (?, 'late_fee', ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $payment_id,
                $late_fee,
                "Late fee for installment #{$payment['installment_number']}",
                $_SESSION['user_id'] ?? 1,
                $reference_number
            ]);
        }
        
        // Handle overpayment - auto-adjust future installments
        if ($new_total_paid > $total_due) {
            $overpayment = $new_total_paid - $total_due;
            adjustFutureInstallments($payment['plan_id'], $overpayment, $payment_id);
        }
        
        // Check if plan is completed
        checkPlanCompletion($payment['plan_id']);
        
        $pdo->commit();
        
        $message = 'Payment of ' . formatCurrency($paid_amount) . ' recorded successfully';
        if ($late_fee > 0) {
            $message .= ' (Late fee: ' . formatCurrency($late_fee) . ' applied)';
        }
        if ($new_total_paid > $total_due) {
            $overpayment = $new_total_paid - $total_due;
            $message .= ' (Overpayment of ' . formatCurrency($overpayment) . ' automatically applied to future installments)';
        }
        
        return [
            'success' => true, 
            'message' => $message,
            'receipt_number' => $receipt_number,
            'reference_number' => $reference_number,
            'late_fee' => $late_fee,
            'total_paid' => $new_total_paid,
            'status' => $status
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Payment recording failed: ' . $e->getMessage()];
    }
}

/**
 * Get installment plan for a quotation
 */
function getInstallmentPlan($quotation_id) {
    global $pdo;
    
    $sql = "SELECT ip.*, q.quote_number, q.customer_name, q.total_amount as quote_total,
            u.full_name as created_by_name
            FROM installment_plans ip
            LEFT JOIN quotations q ON ip.quotation_id = q.id
            LEFT JOIN users u ON ip.created_by = u.id
            WHERE ip.quotation_id = ? AND ip.status = 'active'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quotation_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        // Get payments
        $sql = "SELECT * FROM installment_payments WHERE plan_id = ? ORDER BY installment_number";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plan['id']]);
        $plan['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary
        $plan['summary'] = calculatePlanSummary($plan['id']);
    }
    
    return $plan;
}

/**
 * Calculate plan summary (paid, pending, overdue amounts)
 */
function calculatePlanSummary($plan_id) {
    global $pdo;
    
    $sql = "SELECT 
            COUNT(*) as total_installments,
            SUM(due_amount) as total_due,
            SUM(paid_amount) as total_paid,
            SUM(late_fee_applied) as total_late_fees,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'pending' AND due_date < CURDATE() THEN due_amount ELSE 0 END) as overdue_amount,
            SUM(CASE WHEN status = 'pending' AND due_date >= CURDATE() THEN due_amount ELSE 0 END) as pending_amount
            FROM installment_payments 
            WHERE plan_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$plan_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all installment plans with summary
 */
function getAllInstallmentPlans($status = null) {
    global $pdo;
    
    $sql = "SELECT ip.*, q.quote_number, q.customer_name, q.customer_phone,
            u.full_name as created_by_name
            FROM installment_plans ip
            LEFT JOIN quotations q ON ip.quotation_id = q.id
            LEFT JOIN users u ON ip.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    if ($status) {
        $sql .= " AND ip.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY ip.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add summary for each plan
    foreach ($plans as &$plan) {
        $plan['summary'] = calculatePlanSummary($plan['id']);
    }
    
    return $plans;
}

/**
 * Get overdue payments
 */
function getOverduePayments() {
    global $pdo;
    
    $grace_period = getInstallmentSetting('grace_period_days', 5);
    $cutoff_date = date('Y-m-d', strtotime("-{$grace_period} days"));
    
    $sql = "SELECT ip.*, q.quote_number, q.customer_name, q.customer_phone,
            pl.plan_name
            FROM installment_payments ip
            LEFT JOIN installment_plans pl ON ip.plan_id = pl.id
            LEFT JOIN quotations q ON pl.quotation_id = q.id
            WHERE ip.status IN ('pending', 'partial') 
            AND ip.due_date <= ?
            ORDER BY ip.due_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cutoff_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get upcoming payments (due in next X days)
 */
function getUpcomingPayments($days = 7) {
    global $pdo;
    
    $end_date = date('Y-m-d', strtotime("+{$days} days"));
    
    $sql = "SELECT ip.*, q.quote_number, q.customer_name, q.customer_phone,
            pl.plan_name
            FROM installment_payments ip
            LEFT JOIN installment_plans pl ON ip.plan_id = pl.id
            LEFT JOIN quotations q ON pl.quotation_id = q.id
            WHERE ip.status = 'pending' 
            AND ip.due_date BETWEEN CURDATE() AND ?
            ORDER BY ip.due_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if installment plan is completed
 */
function checkPlanCompletion($plan_id) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
            FROM installment_payments WHERE plan_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$plan_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0 && $result['paid'] == $result['total']) {
        // All payments completed, update plan status
        $stmt = $pdo->prepare("UPDATE installment_plans SET status = 'completed' WHERE id = ?");
        $stmt->execute([$plan_id]);
        
        // Update quotation installment status
        $stmt = $pdo->prepare("UPDATE quotations q 
                              JOIN installment_plans ip ON q.id = ip.quotation_id 
                              SET q.installment_status = 'completed' 
                              WHERE ip.id = ?");
        $stmt->execute([$plan_id]);
        
        return true;
    }
    
    return false;
}

/**
 * Generate payment reference number
 */
function generatePaymentReference($quotation_id, $installment_number) {
    global $pdo;
    
    // Get quote number for reference
    $stmt = $pdo->prepare("SELECT quote_number FROM quotations WHERE id = ?");
    $stmt->execute([$quotation_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $quote_number = $quote ? $quote['quote_number'] : 'QTE' . $quotation_id;
    
    // Format: PAY-QUOTENUM-INST-YYYYMMDD-XXXX
    $date_part = date('Ymd');
    $random_part = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    return "PAY-{$quote_number}-I{$installment_number}-{$date_part}-{$random_part}";
}

/**
 * Generate receipt number
 */
function generateReceiptNumber() {
    global $pdo;
    
    // Get today's count to make sequential receipt numbers
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM installment_payments 
                          WHERE receipt_number IS NOT NULL AND DATE(updated_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $daily_count = ($result['count'] ?? 0) + 1;
    
    // Format: RCP-YYYYMMDD-XXXX
    return 'RCP-' . date('Ymd') . '-' . str_pad($daily_count, 4, '0', STR_PAD_LEFT);
}

/**
 * Get installment setting value
 */
function getInstallmentSetting($key, $default = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_value FROM installment_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update installment setting
 */
function updateInstallmentSetting($key, $value, $description = null) {
    global $pdo;
    
    $sql = "INSERT INTO installment_settings (setting_key, setting_value, description, updated_by) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            description = COALESCE(VALUES(description), description),
            updated_by = VALUES(updated_by)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$key, $value, $description, $_SESSION['user_id'] ?? 1]);
}

/**
 * Get payment history for a plan
 */
function getPaymentHistory($plan_id) {
    global $pdo;
    
    $sql = "SELECT it.*, ip.installment_number, u.full_name as processed_by_name
            FROM installment_transactions it
            LEFT JOIN installment_payments ip ON it.payment_id = ip.id
            LEFT JOIN users u ON it.processed_by = u.id
            WHERE ip.plan_id = ?
            ORDER BY it.transaction_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$plan_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calculate installment with different interest options
 */
function calculateInstallmentOptions($total_amount, $down_payment = 0, $months_options = [6, 12, 18, 24, 36]) {
    $remaining_amount = $total_amount - $down_payment;
    $options = [];
    
    $default_interest = getInstallmentSetting('default_interest_rate', 2.5);
    
    foreach ($months_options as $months) {
        $monthly_rate = $default_interest / 100;
        
        if ($default_interest > 0) {
            $installment = $remaining_amount * 
                ($monthly_rate * pow(1 + $monthly_rate, $months)) /
                (pow(1 + $monthly_rate, $months) - 1);
        } else {
            $installment = $remaining_amount / $months;
        }
        
        $total_to_pay = ($installment * $months) + $down_payment;
        $total_interest = $total_to_pay - $total_amount;
        
        // Round all calculations to 2 decimal places
        // Round installment amount down to nearest 0.50
        $installment = floor($installment * 2) / 2;
        $total_to_pay = round($total_to_pay, 2);
        $total_interest = round($total_interest, 2);
        
        $options[] = [
            'months' => $months,
            'monthly_payment' => $installment,
            'total_to_pay' => $total_to_pay,
            'total_interest' => $total_interest,
            'interest_rate' => $default_interest
        ];
    }
    
    return $options;
}

/**
 * Automatically adjust future installments when there's an overpayment
 */
function adjustFutureInstallments($plan_id, $overpayment_amount, $current_payment_id) {
    global $pdo;
    
    try {
        // Get all future pending installments for this plan
        $stmt = $pdo->prepare("SELECT * FROM installment_payments 
                              WHERE plan_id = ? AND status IN ('pending', 'partial') 
                              AND id != ? 
                              ORDER BY installment_number ASC");
        $stmt->execute([$plan_id, $current_payment_id]);
        $future_installments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($future_installments)) {
            return; // No future installments to adjust
        }
        
        $remaining_overpayment = $overpayment_amount;
        
        foreach ($future_installments as $installment) {
            if ($remaining_overpayment <= 0) {
                break; // No more overpayment to distribute
            }
            
            $current_due = floatval($installment['due_amount']);
            $current_paid = floatval($installment['paid_amount']);
            $remaining_due = $current_due - $current_paid;
            
            if ($remaining_due <= 0) {
                continue; // This installment is already fully paid
            }
            
            // Calculate how much of the overpayment to apply to this installment
            $amount_to_apply = min($remaining_overpayment, $remaining_due);
            
            // Update the installment with the overpayment
            $new_paid_amount = $current_paid + $amount_to_apply;
            $new_status = ($new_paid_amount >= $current_due) ? 'paid' : 'partial';
            
            $stmt = $pdo->prepare("UPDATE installment_payments 
                                  SET paid_amount = ?, status = ?, updated_at = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$new_paid_amount, $new_status, $installment['id']]);
            
            // Record the overpayment transaction
            $stmt = $pdo->prepare("INSERT INTO installment_transactions 
                                  (payment_id, transaction_type, amount, description, processed_by, reference_number) 
                                  VALUES (?, 'overpayment_credit', ?, ?, ?, ?)");
            $stmt->execute([
                $installment['id'],
                $amount_to_apply,
                "Overpayment credit applied to installment #{$installment['installment_number']}",
                $_SESSION['user_id'] ?? 1,
                'AUTO-' . date('YmdHis')
            ]);
            
            $remaining_overpayment -= $amount_to_apply;
        }
        
        // If there's still remaining overpayment, create a credit record
        if ($remaining_overpayment > 0) {
            $stmt = $pdo->prepare("INSERT INTO installment_transactions 
                                  (payment_id, transaction_type, amount, description, processed_by, reference_number) 
                                  VALUES (?, 'overpayment_credit', ?, ?, ?, ?)");
            $stmt->execute([
                $current_payment_id,
                $remaining_overpayment,
                "Overpayment credit - will be applied to future installments",
                $_SESSION['user_id'] ?? 1,
                'AUTO-' . date('YmdHis')
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error adjusting future installments: " . $e->getMessage());
        // Don't throw exception to avoid breaking the main payment process
    }
}

/**
 * Get installment plan with adjusted amounts due to overpayments
 */
function getInstallmentPlanWithAdjustments($quotation_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM installment_plans WHERE quotation_id = ?");
    $stmt->execute([$quotation_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        return null;
    }
    
    // Get all payments for this plan
    $stmt = $pdo->prepare("SELECT * FROM installment_payments 
                          WHERE plan_id = ? 
                          ORDER BY installment_number ASC");
    $stmt->execute([$plan['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $plan['payments'] = $payments;
    
    // Calculate total paid and remaining balance
    $total_paid = 0;
    $total_remaining = 0;
    
    foreach ($payments as $payment) {
        $total_paid += floatval($payment['paid_amount']);
        $remaining = floatval($payment['due_amount']) - floatval($payment['paid_amount']);
        if ($remaining > 0) {
            $total_remaining += $remaining;
        }
    }
    
    $plan['total_paid'] = $total_paid;
    $plan['total_remaining'] = $total_remaining;
    $plan['completion_percentage'] = $plan['total_amount'] > 0 ? 
        round(($total_paid / $plan['total_amount']) * 100, 2) : 0;
    
    // Calculate summary statistics
    $pending_amount = 0;
    $overdue_amount = 0;
    $today = new DateTime();
    
    foreach ($payments as $payment) {
        $remaining = floatval($payment['due_amount']) - floatval($payment['paid_amount']);
        if ($remaining > 0) {
            $due_date = new DateTime($payment['due_date']);
            if ($due_date < $today) {
                $overdue_amount += $remaining;
            } else {
                $pending_amount += $remaining;
            }
        }
    }
    
    $plan['summary'] = [
        'total_paid' => $total_paid,
        'pending_amount' => $pending_amount,
        'overdue_amount' => $overdue_amount,
        'total_remaining' => $total_remaining,
        'completion_percentage' => $plan['completion_percentage']
    ];
    
    return $plan;
}

?>
