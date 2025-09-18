<?php
/**
 * Payroll System Database Migration Script
 * Run this script once to create the payroll system tables
 * 
 * Usage: Navigate to http://localhost/4nsolarSystem/migrate_payroll.php
 */

require_once 'includes/config.php';

// Set execution time limit for large migrations
set_time_limit(300);

// Display results
$results = [];
$errors = [];

try {
    // Read the SQL migration file
    $sql_file = 'database/payroll_system.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Migration file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    if ($sql_content === false) {
        throw new Exception("Could not read migration file: $sql_file");
    }
    
    // Remove comments and clean up SQL
    $lines = explode("\n", $sql_content);
    $clean_sql = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and comment lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $clean_sql .= $line . "\n";
    }
    
    // Split SQL statements by semicolon and newline
    $statements = preg_split('/;\s*\n/', $clean_sql);
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }
        
        // Add semicolon back if missing
        if (!preg_match('/;\s*$/', $statement)) {
            $statement .= ';';
        }
        
        try {
            $result = $pdo->exec($statement);
            
            // Get a preview of the statement for logging
            $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 100);
            if (strlen($statement) > 100) {
                $preview .= '...';
            }
            
            $results[] = [
                'index' => $index + 1,
                'preview' => $preview,
                'affected_rows' => $result,
                'status' => 'success'
            ];
            
        } catch (PDOException $e) {
            // Some errors are expected (like table already exists)
            $error_msg = $e->getMessage();
            $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 100);
            if (strlen($statement) > 100) {
                $preview .= '...';
            }
            
            $errors[] = [
                'index' => $index + 1,
                'preview' => $preview,
                'error' => $error_msg,
                'status' => 'error'
            ];
        }
    }
    
    $migration_completed = true;
    
} catch (Exception $e) {
    $migration_completed = false;
    $fatal_error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll System Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .container { max-width: 900px; }
        .result-item { font-family: monospace; font-size: 0.9em; }
        .migration-header { background: linear-gradient(135deg, #1e40af, #3b82f6); }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header migration-header text-white">
                <div class="d-flex align-items-center">
                    <img src="images/logo.png" alt="4NSOLAR ELECTRICZ" style="height: 40px; margin-right: 15px;">
                    <div>
                        <h3 class="mb-0"><i class="fas fa-database me-2"></i>Payroll System Migration</h3>
                        <small>4NSOLAR ELECTRICZ Management System</small>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (isset($fatal_error)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Fatal Error</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($fatal_error); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Migration Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h4><?php echo count($results); ?></h4>
                                    <small>Successful Operations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <h4><?php echo count($errors); ?></h4>
                                    <small>Warnings/Errors</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h4><?php echo date('H:i:s'); ?></h4>
                                    <small>Completed At</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($migration_completed && count($results) > 0): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Migration Completed Successfully!</h5>
                            <p class="mb-0">The payroll system database has been set up. You can now access the payroll features.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Successful Operations -->
                    <?php if (!empty($results)): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-check me-2"></i>Successful Operations (<?php echo count($results); ?>)</h6>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($results as $result): ?>
                                    <div class="result-item p-2 mb-1 bg-light rounded border-start border-success border-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <small class="text-muted">#<?php echo $result['index']; ?></small>
                                                <div><?php echo htmlspecialchars($result['preview']); ?></div>
                                            </div>
                                            <span class="badge bg-success ms-2">
                                                <i class="fas fa-check me-1"></i>Success
                                            </span>
                                        </div>
                                        <?php if ($result['affected_rows'] !== false && $result['affected_rows'] > 0): ?>
                                            <small class="text-muted">Affected rows: <?php echo $result['affected_rows']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Errors/Warnings -->
                    <?php if (!empty($errors)): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Warnings/Errors (<?php echo count($errors); ?>)</h6>
                                <small>Note: Some errors like "table already exists" are normal and can be ignored.</small>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($errors as $error): ?>
                                    <div class="result-item p-2 mb-1 bg-light rounded border-start border-warning border-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <small class="text-muted">#<?php echo $error['index']; ?></small>
                                                <div><?php echo htmlspecialchars($error['preview']); ?></div>
                                                <small class="text-danger"><?php echo htmlspecialchars($error['error']); ?></small>
                                            </div>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Warning
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tables Created -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-table me-2"></i>Payroll System Tables</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fas fa-users text-primary me-2"></i>employees</li>
                                        <li class="list-group-item"><i class="fas fa-calendar-check text-success me-2"></i>employee_attendance</li>
                                        <li class="list-group-item"><i class="fas fa-money-check-alt text-info me-2"></i>payroll</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fas fa-minus-circle text-warning me-2"></i>payroll_deductions</li>
                                        <li class="list-group-item"><i class="fas fa-calendar-times text-secondary me-2"></i>employee_leaves</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="payroll.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>Go to Payroll System
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            
            <div class="card-footer text-muted text-center">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Migration completed on <?php echo date('F j, Y \a\t g:i A'); ?>
                    | Database: <?php echo DB_NAME; ?>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
