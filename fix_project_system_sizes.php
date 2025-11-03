<?php
/**
 * One-time utility script to fix system_size_kw in existing projects
 * by retrieving the value from quote_solar_details table
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/projects.php';
require_once 'includes/inventory.php';

// Only allow logged-in admin users to run this script
if (!isLoggedIn()) {
    die("Access denied. Please login first.");
}

echo "<h2>Fixing System Sizes for Existing Projects</h2>";
echo "<p>Retrieving system sizes from quote_solar_details table...</p>";
echo "<hr>";

try {
    // Get all projects that have a quote_id and system_size_kw = 0
    $stmt = $pdo->prepare("SELECT id, project_name, quote_id, system_size_kw 
                          FROM solar_projects 
                          WHERE quote_id IS NOT NULL 
                          AND (system_size_kw = 0 OR system_size_kw IS NULL)");
    $stmt->execute();
    $projects = $stmt->fetchAll();
    
    if (empty($projects)) {
        echo "<p><strong>No projects found with missing system sizes.</strong></p>";
    } else {
        echo "<p>Found " . count($projects) . " projects with missing system sizes.</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Project ID</th><th>Project Name</th><th>Quote ID</th><th>Old Size</th><th>New Size</th><th>Status</th>";
        echo "</tr>";
        
        $updated = 0;
        $failed = 0;
        
        foreach ($projects as $project) {
            $project_id = $project['id'];
            $quote_id = $project['quote_id'];
            $old_size = $project['system_size_kw'];
            
            // Get solar details for this quote
            $solar_details = getSolarProjectDetails($quote_id);
            
            if ($solar_details && isset($solar_details['system_size_kw']) && $solar_details['system_size_kw'] > 0) {
                $new_size = $solar_details['system_size_kw'];
                
                // Update the project
                $update_stmt = $pdo->prepare("UPDATE solar_projects SET system_size_kw = ? WHERE id = ?");
                $result = $update_stmt->execute([$new_size, $project_id]);
                
                if ($result) {
                    echo "<tr style='background-color: #e8f5e9;'>";
                    echo "<td>{$project_id}</td>";
                    echo "<td>" . htmlspecialchars($project['project_name']) . "</td>";
                    echo "<td>{$quote_id}</td>";
                    echo "<td>{$old_size} kW</td>";
                    echo "<td><strong>{$new_size} kW</strong></td>";
                    echo "<td style='color: green;'>✓ Updated</td>";
                    echo "</tr>";
                    $updated++;
                } else {
                    echo "<tr style='background-color: #ffebee;'>";
                    echo "<td>{$project_id}</td>";
                    echo "<td>" . htmlspecialchars($project['project_name']) . "</td>";
                    echo "<td>{$quote_id}</td>";
                    echo "<td>{$old_size} kW</td>";
                    echo "<td>-</td>";
                    echo "<td style='color: red;'>✗ Update failed</td>";
                    echo "</tr>";
                    $failed++;
                }
            } else {
                echo "<tr style='background-color: #fff3e0;'>";
                echo "<td>{$project_id}</td>";
                echo "<td>" . htmlspecialchars($project['project_name']) . "</td>";
                echo "<td>{$quote_id}</td>";
                echo "<td>{$old_size} kW</td>";
                echo "<td>-</td>";
                echo "<td style='color: orange;'>⚠ No solar details found in quote</td>";
                echo "</tr>";
                $failed++;
            }
        }
        
        echo "</table>";
        echo "<hr>";
        echo "<h3>Summary:</h3>";
        echo "<p><strong>Total projects processed:</strong> " . count($projects) . "</p>";
        echo "<p><strong>Successfully updated:</strong> <span style='color: green;'>{$updated}</span></p>";
        echo "<p><strong>Failed or skipped:</strong> <span style='color: red;'>{$failed}</span></p>";
    }
    
    echo "<hr>";
    echo "<p><a href='projects.php'>← Go to Projects</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

