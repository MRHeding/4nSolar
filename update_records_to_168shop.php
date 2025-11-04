<?php
/**
 * Update POS Sales Records to 168shop
 * This script updates sales records from October 15, 2025 to November 3, 2025
 * to set shop_type to '168shop'
 */

require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Records to 168shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
        }
        h1 {
            color: #1e40af;
        }
        .info {
            padding: 15px;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
        .success {
            padding: 15px;
            background: #c8e6c9;
            border-left: 4px solid #4caf50;
            margin: 20px 0;
        }
        .error {
            padding: 15px;
            background: #ffcdd2;
            border-left: 4px solid #f44336;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1e40af;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <h1>Update POS Sales Records to 168shop</h1>
    <div class="info">
        <strong>What this does:</strong> Updates sales records from October 15, 2025 to November 3, 2025 to set shop_type to '168shop'.
    </div>
    
    <?php
    if (isset($_POST['update'])) {
        try {
            // Get count before update
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pos_sales WHERE DATE(created_at) BETWEEN '2025-10-15' AND '2025-11-03'");
            $stmt->execute();
            $before_count = $stmt->fetch()['count'];
            
            // Update records
            $stmt = $pdo->prepare("UPDATE pos_sales SET shop_type = '168shop' WHERE DATE(created_at) BETWEEN '2025-10-15' AND '2025-11-03'");
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
            echo "<div class='success'>";
            echo "<h3>✅ Update Successful!</h3>";
            echo "<p><strong>Records updated:</strong> $affected_rows</p>";
            echo "</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
    // Show current distribution
    try {
        echo "<h2>Current Distribution (October 15 - November 3, 2025)</h2>";
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                shop_type,
                COUNT(*) as record_count,
                SUM(total_amount) as total_revenue
            FROM pos_sales
            WHERE DATE(created_at) BETWEEN '2025-10-15' AND '2025-11-03'
            GROUP BY DATE(created_at), shop_type
            ORDER BY sale_date DESC, shop_type
        ");
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        if (count($records) > 0) {
            echo "<table>";
            echo "<tr><th>Date</th><th>Shop Type</th><th>Records</th><th>Total Revenue</th></tr>";
            $total_4nsolar = 0;
            $total_168shop = 0;
            $count_4nsolar = 0;
            $count_168shop = 0;
            
            foreach ($records as $record) {
                $shop_color = $record['shop_type'] === '4nsolar' ? '#3b82f6' : '#f97316';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['sale_date']) . "</td>";
                echo "<td><strong style='color: $shop_color;'>" . htmlspecialchars(strtoupper($record['shop_type'])) . "</strong></td>";
                echo "<td>" . $record['record_count'] . "</td>";
                echo "<td>₱" . number_format($record['total_revenue'], 2) . "</td>";
                echo "</tr>";
                
                if ($record['shop_type'] === '4nsolar') {
                    $total_4nsolar += $record['total_revenue'];
                    $count_4nsolar += $record['record_count'];
                } else {
                    $total_168shop += $record['total_revenue'];
                    $count_168shop += $record['record_count'];
                }
            }
            
            echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
            echo "<td colspan='2'><strong>Total</strong></td>";
            echo "<td><strong>" . ($count_4nsolar + $count_168shop) . "</strong></td>";
            echo "<td><strong>₱" . number_format($total_4nsolar + $total_168shop, 2) . "</strong></td>";
            echo "</tr>";
            echo "</table>";
            
            echo "<div class='info'>";
            echo "<h3>Summary:</h3>";
            echo "<ul>";
            echo "<li><strong>4NSOLAR:</strong> $count_4nsolar records - ₱" . number_format($total_4nsolar, 2) . "</li>";
            echo "<li><strong>168SHOP:</strong> $count_168shop records - ₱" . number_format($total_168shop, 2) . "</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p>No records found in this date range.</p>";
        }
        
        // Show records that need to be updated (currently 4nsolar)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM pos_sales 
            WHERE DATE(created_at) BETWEEN '2025-10-15' AND '2025-11-03' 
            AND shop_type = '4nsolar'
        ");
        $stmt->execute();
        $need_update = $stmt->fetch()['count'];
        
        if ($need_update > 0) {
            echo "<div class='info'>";
            echo "<h3>Records Needing Update:</h3>";
            echo "<p>There are <strong>$need_update</strong> record(s) in this date range currently set to '4nsolar' that should be '168shop'.</p>";
            if (!isset($_POST['update'])) {
                echo "<form method='POST'>";
                echo "<button type='submit' name='update' style='background: #f97316; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Update All to 168shop</button>";
                echo "</form>";
            }
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<p>✅ All records in this date range are already set to '168shop'.</p>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
        <p><a href="pos.php?action=history">← Back to Sales History</a></p>
        <p><small>You can safely delete this file after updating the records.</small></p>
    </div>
</body>
</html>
