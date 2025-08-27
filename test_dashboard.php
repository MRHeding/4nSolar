<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4nSolar System Test Dashboard</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            color: #7f8c8d;
            margin: 10px 0 0 0;
            font-size: 1.1em;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .test-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            text-align: center;
        }
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .test-card h3 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 1.3em;
        }
        .test-card p {
            color: #7f8c8d;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }
        .test-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .test-btn.secondary {
            background: #6c757d;
        }
        .test-btn.success {
            background: #28a745;
        }
        .test-btn.warning {
            background: #ffc107;
            color: #212529;
        }
        .test-btn.danger {
            background: #dc3545;
        }
        .status-section {
            background: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .status-section h2 {
            margin: 0 0 15px 0;
            color: #2c3e50;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .status-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .status-item.success { border-left-color: #28a745; }
        .status-item.warning { border-left-color: #ffc107; }
        .status-item.danger { border-left-color: #dc3545; }
        .status-item h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        .status-item p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        .quick-actions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .quick-actions h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 4nSolar Test Dashboard</h1>
            <p>Comprehensive testing and diagnostics for your solar inventory management system</p>
            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="status-section">
            <h2>📊 Quick System Status</h2>
            <div class="status-grid">
                <?php
                // Quick status checks
                $status_checks = [
                    'Database' => [
                        'status' => 'checking',
                        'message' => 'Checking connection...'
                    ],
                    'PHP Extensions' => [
                        'status' => 'checking', 
                        'message' => 'Checking requirements...'
                    ],
                    'File System' => [
                        'status' => 'checking',
                        'message' => 'Checking permissions...'
                    ],
                    'Core Files' => [
                        'status' => 'checking',
                        'message' => 'Checking integrity...'
                    ]
                ];

                // Database check
                try {
                    $pdo = new PDO("mysql:host=localhost;dbname=4nsolar_inventory", "root", "");
                    $status_checks['Database'] = ['status' => 'success', 'message' => 'Connected'];
                } catch (PDOException $e) {
                    $status_checks['Database'] = ['status' => 'danger', 'message' => 'Connection failed'];
                }

                // PHP Extensions check
                $critical_extensions = ['pdo', 'pdo_mysql'];
                $optional_extensions = ['gd', 'curl', 'mbstring'];
                $missing_critical = 0;
                $missing_optional = 0;

                foreach ($critical_extensions as $ext) {
                    if (!extension_loaded($ext)) $missing_critical++;
                }
                foreach ($optional_extensions as $ext) {
                    if (!extension_loaded($ext)) $missing_optional++;
                }

                if ($missing_critical > 0) {
                    $status_checks['PHP Extensions'] = ['status' => 'danger', 'message' => "$missing_critical critical missing"];
                } elseif ($missing_optional > 0) {
                    $status_checks['PHP Extensions'] = ['status' => 'warning', 'message' => "$missing_optional optional missing"];
                } else {
                    $status_checks['PHP Extensions'] = ['status' => 'success', 'message' => 'All extensions loaded'];
                }

                // File system check
                $required_files = ['dashboard.php', 'inventory.php', 'includes/config.php'];
                $missing_files = 0;
                foreach ($required_files as $file) {
                    if (!file_exists($file)) $missing_files++;
                }

                if ($missing_files > 0) {
                    $status_checks['Core Files'] = ['status' => 'danger', 'message' => "$missing_files files missing"];
                } else {
                    $status_checks['Core Files'] = ['status' => 'success', 'message' => 'All files present'];
                }

                // Directory permissions
                $writable_dirs = ['images/products/', 'assets/'];
                $non_writable = 0;
                foreach ($writable_dirs as $dir) {
                    if (is_dir($dir) && !is_writable($dir)) $non_writable++;
                }

                if ($non_writable > 0) {
                    $status_checks['File System'] = ['status' => 'warning', 'message' => "$non_writable dirs not writable"];
                } else {
                    $status_checks['File System'] = ['status' => 'success', 'message' => 'Permissions OK'];
                }

                // Display status items
                foreach ($status_checks as $name => $check) {
                    echo "<div class='status-item {$check['status']}'>";
                    echo "<h4>$name</h4>";
                    echo "<p>{$check['message']}</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <div class="test-grid">
            <div class="test-card">
                <h3>🗄️ Database Setup</h3>
                <p>Check and create missing database tables, fix schema issues, and verify data integrity.</p>
                <a href="setup_database.php" class="test-btn">Setup Database</a>
            </div>

            <div class="test-card">
                <h3>⚙️ PHP Extensions</h3>
                <p>Check for required PHP extensions like GD, PDO, and others. Get fix instructions.</p>
                <a href="check_extensions.php" class="test-btn warning">Check Extensions</a>
            </div>

            <div class="test-card">
                <h3>🧪 Comprehensive Test</h3>
                <p>Run a complete system test covering database, files, security, and functionality.</p>
                <a href="web_test.php" class="test-btn success">Run Full Test</a>
            </div>

            <div class="test-card">
                <h3>📊 System Dashboard</h3>
                <p>Access the main application dashboard to test the user interface and features.</p>
                <a href="dashboard.php" class="test-btn secondary">Open Dashboard</a>
            </div>

            <div class="test-card">
                <h3>🔐 Login System</h3>
                <p>Test the authentication system and user login functionality.</p>
                <a href="login.php" class="test-btn">Test Login</a>
            </div>

            <div class="test-card">
                <h3>📦 Inventory Test</h3>
                <p>Test inventory management features including add, edit, and stock tracking.</p>
                <a href="inventory.php" class="test-btn">Test Inventory</a>
            </div>

            <div class="test-card">
                <h3>🏗️ Projects Test</h3>
                <p>Test solar project management, quotes, and project tracking features.</p>
                <a href="projects.php" class="test-btn">Test Projects</a>
            </div>

            <div class="test-card">
                <h3>💰 POS System</h3>
                <p>Test the point of sale system for handling customer transactions.</p>
                <a href="pos.php" class="test-btn">Test POS</a>
            </div>
        </div>

        <div class="quick-actions">
            <h3>🚀 Quick Actions</h3>
            <div class="action-grid">
                <a href="?action=clear_cache" class="test-btn secondary">Clear Cache</a>
                <a href="?action=reset_session" class="test-btn secondary">Reset Session</a>
                <a href="?action=check_permissions" class="test-btn warning">Check Permissions</a>
                <a href="?action=backup_db" class="test-btn">Backup Database</a>
            </div>
        </div>

        <?php
        // Handle quick actions
        if (isset($_GET['action'])) {
            echo "<div class='status-section'>";
            echo "<h3>Action Result:</h3>";
            
            switch ($_GET['action']) {
                case 'clear_cache':
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        echo "<p class='text-success'>✓ OPCache cleared successfully</p>";
                    } else {
                        echo "<p class='text-info'>ℹ️ OPCache not available</p>";
                    }
                    break;
                    
                case 'reset_session':
                    session_start();
                    session_destroy();
                    echo "<p class='text-success'>✓ Session reset successfully</p>";
                    break;
                    
                case 'check_permissions':
                    $dirs_to_check = ['images/', 'images/products/', 'assets/'];
                    echo "<ul>";
                    foreach ($dirs_to_check as $dir) {
                        if (is_dir($dir)) {
                            $writable = is_writable($dir);
                            $status = $writable ? '✓' : '❌';
                            $class = $writable ? 'text-success' : 'text-danger';
                            echo "<li class='$class'>$status $dir - " . ($writable ? 'Writable' : 'Not writable') . "</li>";
                        } else {
                            echo "<li class='text-warning'>⚠️ $dir - Directory does not exist</li>";
                        }
                    }
                    echo "</ul>";
                    break;
                    
                case 'backup_db':
                    echo "<p class='text-info'>ℹ️ Database backup feature would be implemented here</p>";
                    echo "<p>For manual backup, use: <code>mysqldump -u root 4nsolar_inventory > backup.sql</code></p>";
                    break;
            }
            echo "</div>";
        }
        ?>

        <div class="status-section">
            <h2>📋 System Information</h2>
            <div class="status-grid">
                <div class="status-item">
                    <h4>PHP Version</h4>
                    <p><?php echo phpversion(); ?></p>
                </div>
                <div class="status-item">
                    <h4>Server</h4>
                    <p><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                </div>
                <div class="status-item">
                    <h4>Memory Limit</h4>
                    <p><?php echo ini_get('memory_limit'); ?></p>
                </div>
                <div class="status-item">
                    <h4>Upload Limit</h4>
                    <p><?php echo ini_get('upload_max_filesize'); ?></p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>4nSolar Inventory Management System © 2025</p>
            <p>For support and documentation, contact your system administrator.</p>
        </div>
    </div>
</body>
</html>
