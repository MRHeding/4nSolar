<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and is admin
requireLogin();
if (!hasRole(ROLE_ADMIN)) {
    die('Access denied. Admin privileges required.');
}

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Read and execute the SQL file
        $sql_content = file_get_contents('database/installment_system.sql');
        
        if ($sql_content === false) {
            throw new Exception('Could not read installment_system.sql file');
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            'strlen'
        );
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            // Skip comments and empty statements
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Check if error is due to table already existing
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate column') === false) {
                    throw $e;
                }
            }
        }
        
        $pdo->commit();
        $success = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}

$page_title = 'Installment System Setup';
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-credit-card mr-3 text-blue-600"></i>
                Installment System Setup
            </h1>
            
            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <div>
                        <h3 class="text-lg font-medium text-green-800">Installation Complete!</h3>
                        <p class="text-green-700 mt-1">
                            The installment system has been successfully installed and configured.
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-green-200">
                    <h4 class="font-medium text-green-800 mb-2">What's been added:</h4>
                    <ul class="list-disc list-inside text-green-700 space-y-1">
                        <li>Installment plans management</li>
                        <li>Payment tracking and scheduling</li>
                        <li>Transaction history logging</li>
                        <li>Automatic receipt generation</li>
                        <li>Late fee calculations</li>
                        <li>Payment reminders system</li>
                        <li>Integration with quotations</li>
                    </ul>
                </div>
                
                <div class="mt-4 pt-4 border-t border-green-200">
                    <h4 class="font-medium text-green-800 mb-2">Next Steps:</h4>
                    <div class="space-y-2">
                        <a href="quotations.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Go to Quotations
                        </a>
                        <p class="text-sm text-green-600 mt-2">
                            Navigate to any accepted quotation and click "Payment Plan" to start using the installment system.
                        </p>
                    </div>
                </div>
            </div>
            
            <?php elseif (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                    <h3 class="text-lg font-medium text-red-800">Installation Failed</h3>
                </div>
                <div class="text-red-700">
                    <p class="mb-2">The following errors occurred:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Installation Overview</h2>
                <p class="text-gray-600 mb-4">
                    This will install the complete installment/payment plan system for your 4nSolar quotations. 
                    The system includes:
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-medium text-blue-900 mb-2">
                            <i class="fas fa-database mr-2"></i>Database Changes
                        </h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• installment_plans table</li>
                            <li>• installment_payments table</li>
                            <li>• installment_transactions table</li>
                            <li>• installment_settings table</li>
                            <li>• Additional quotations columns</li>
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-medium text-green-900 mb-2">
                            <i class="fas fa-features mr-2"></i>Features
                        </h3>
                        <ul class="text-sm text-green-800 space-y-1">
                            <li>• Flexible payment schedules</li>
                            <li>• Interest calculations</li>
                            <li>• Late fee management</li>
                            <li>• Payment tracking</li>
                            <li>• Receipt generation</li>
                            <li>• Overdue notifications</li>
                        </ul>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                        <div>
                            <h3 class="font-medium text-yellow-800">Important Notes</h3>
                            <ul class="text-sm text-yellow-700 mt-2 space-y-1">
                                <li>• This will modify your database structure</li>
                                <li>• Make sure you have a backup of your database</li>
                                <li>• The installation is safe and won't affect existing data</li>
                                <li>• You need admin privileges to run this installation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="text-center">
                    <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition text-lg font-medium">
                        <i class="fas fa-download mr-2"></i>
                        Install Installment System
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 mb-3">How to Use After Installation</h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">1</span>
                        <p>Go to any <strong>accepted</strong> quotation in the quotations management page</p>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">2</span>
                        <p>Click the <strong>"Payment Plan"</strong> button to create an installment plan</p>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">3</span>
                        <p>Configure the payment schedule, interest rates, and terms</p>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-3 mt-0.5">4</span>
                        <p>Track payments, record transactions, and manage the payment schedule</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
