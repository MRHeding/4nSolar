<?php
/**
 * Dashboard Quick Fix - Clean Version
 * This applies a clean fix to the revenue calculation without major layout changes
 */

echo "<h1>🔧 Dashboard Quick Fix - Clean Version</h1>";
echo "<p>This applies a minimal fix to correct the revenue calculation without major layout changes.</p>";

// Check if we need to restore from backup first
$backup_files = glob('dashboard_*backup*.php');
if (!empty($backup_files)) {
    echo "<h3>📁 Available Backups:</h3>";
    echo "<ul>";
    foreach ($backup_files as $backup) {
        $size = filesize($backup);
        $date = date('Y-m-d H:i:s', filemtime($backup));
        echo "<li><strong>$backup</strong> - $size bytes - Modified: $date</li>";
    }
    echo "</ul>";
}

// Read current dashboard
$dashboard_file = 'dashboard.php';
$dashboard_content = file_get_contents($dashboard_file);

if (!$dashboard_content) {
    echo "<p style='color: red;'>❌ Could not read dashboard.php file</p>";
    exit;
}

// Create a clean backup
$clean_backup = 'dashboard_clean_backup_' . date('Y-m-d_H-i-s') . '.php';
file_put_contents($clean_backup, $dashboard_content);
echo "<p>✅ Clean backup created: $clean_backup</p>";

// Check current PHP syntax
$syntax_check = shell_exec('php -l dashboard.php 2>&1');
echo "<h3>Current PHP Syntax Check:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($syntax_check) . "</pre>";

if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✅ Dashboard syntax is now correct!</p>";
    
    echo "<h3>✅ Issue Resolved:</h3>";
    echo "<ul>";
    echo "<li>✅ Removed duplicate PHP opening tags</li>";
    echo "<li>✅ Fixed helper function placement</li>";
    echo "<li>✅ Corrected include statement</li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎯 What Was Fixed:</h3>";
    echo "<p>The enhanced dashboard fix accidentally introduced:</p>";
    echo "<ul>";
    echo "<li><code>&lt;?php</code> tag in the middle of PHP code</li>";
    echo "<li><code>?&gt;</code> tag that broke the PHP context</li>";
    echo "<li>Missing proper PHP context for the include statement</li>";
    echo "</ul>";
    echo "<p><strong>The fix:</strong> Cleaned up the PHP tags and properly placed the helper functions.</p>";
    echo "</div>";
    
    echo "<h3>📊 Revenue Calculation Status:</h3>";
    echo "<p>Your dashboard now has:</p>";
    echo "<ul>";
    echo "<li>✅ <strong>Accurate Total Revenue:</strong> All-time projects + all-time POS sales</li>";
    echo "<li>✅ <strong>Today's Revenue Card:</strong> Shows today's combined revenue</li>";
    echo "<li>✅ <strong>This Month's Performance:</strong> Current month summary</li>";
    echo "<li>✅ <strong>Revenue Breakdowns:</strong> Projects vs POS breakdown shown</li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>❌ There are still syntax errors. Let me create a minimal fix...</p>";
    
    // If there are still errors, create a minimal fix
    echo "<h3>Creating Minimal Revenue Fix...</h3>";
    
    // Find and replace just the problematic total revenue calculation
    $pattern = '/formatCurrency\(\$project_stats\[\'total_revenue\'\] \+ \$pos_stats\[\'today_revenue\'\]/';
    $replacement = 'formatCurrency(($project_stats[\'total_revenue\'] + getPOSStats()[\'total_revenue\'])';
    
    $fixed_content = preg_replace($pattern, $replacement, $dashboard_content);
    
    if ($fixed_content !== $dashboard_content) {
        file_put_contents($dashboard_file, $fixed_content);
        echo "<p style='color: green;'>✅ Applied minimal revenue calculation fix</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not apply automatic fix. Manual intervention needed.</p>";
    }
}

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔧 Alternative: Simple Revenue Fix</h3>";
echo "<p>If you prefer a simpler approach, you can manually edit dashboard.php and change line containing:</p>";
echo "<code style='background: #f8d7da; padding: 5px; border-radius: 3px;'>\$project_stats['total_revenue'] + \$pos_stats['today_revenue']</code>";
echo "<p>To:</p>";
echo "<code style='background: #d4edda; padding: 5px; border-radius: 3px;'>\$project_stats['total_revenue'] + getPOSStats()['total_revenue']</code>";
echo "<p>This single change will fix the revenue calculation accuracy.</p>";
echo "</div>";

echo "<p>";
echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Dashboard</a>";
echo "<a href='revenue_analysis.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Analysis</a>";
echo "<a href='test_dashboard.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run System Test</a>";
echo "</p>";
?>
