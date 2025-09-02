<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/pos.php';

// This script tests if deleted inventory items are properly filtered out
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Deletion Fix - 4nSolar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'solar-blue': '#1e40af',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Inventory Deletion Fix Test</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Results</h2>
            
            <?php
            // Test 1: Get all inventory items (should only show active items)
            echo "<h3 class='text-lg font-medium mb-2'>Test 1: All Inventory Items (getInventoryItems)</h3>";
            $all_items = getInventoryItems();
            echo "<p class='mb-4'>Found " . count($all_items) . " active items</p>";
            
            // Test 2: Get POS inventory items (should only show active items with stock > 0)
            echo "<h3 class='text-lg font-medium mb-2'>Test 2: POS Available Items (getPOSInventoryItems)</h3>";
            $pos_items = getPOSInventoryItems();
            echo "<p class='mb-4'>Found " . count($pos_items) . " items available for POS</p>";
            
            // Test 3: Check database for inactive items
            echo "<h3 class='text-lg font-medium mb-2'>Test 3: Database Check</h3>";
            $stmt = $pdo->query("SELECT COUNT(*) as total_count FROM inventory_items");
            $total_count = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as active_count FROM inventory_items WHERE is_active = 1");
            $active_count = $stmt->fetchColumn();
            
            $inactive_count = $total_count - $active_count;
            
            echo "<div class='grid grid-cols-3 gap-4 mb-4'>";
            echo "<div class='bg-blue-50 p-3 rounded'>";
            echo "<div class='text-2xl font-bold text-blue-600'>$total_count</div>";
            echo "<div class='text-sm text-gray-600'>Total Items</div>";
            echo "</div>";
            echo "<div class='bg-green-50 p-3 rounded'>";
            echo "<div class='text-2xl font-bold text-green-600'>$active_count</div>";
            echo "<div class='text-sm text-gray-600'>Active Items</div>";
            echo "</div>";
            echo "<div class='bg-red-50 p-3 rounded'>";
            echo "<div class='text-2xl font-bold text-red-600'>$inactive_count</div>";
            echo "<div class='text-sm text-gray-600'>Deleted Items</div>";
            echo "</div>";
            echo "</div>";
            
            if ($inactive_count > 0) {
                echo "<div class='bg-yellow-50 border border-yellow-200 rounded p-4 mb-4'>";
                echo "<h4 class='font-medium text-yellow-800'>Deleted Items Found:</h4>";
                $stmt = $pdo->query("SELECT brand, model, created_at FROM inventory_items WHERE is_active = 0 ORDER BY updated_at DESC");
                $deleted_items = $stmt->fetchAll();
                echo "<ul class='mt-2 text-sm text-yellow-700'>";
                foreach ($deleted_items as $item) {
                    echo "<li>• {$item['brand']} {$item['model']} (added: " . date('M j, Y', strtotime($item['created_at'])) . ")</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            
            // Test 4: Check if deleted items appear in any active queries
            echo "<h3 class='text-lg font-medium mb-2'>Test 4: Verification</h3>";
            $issues = [];
            
            foreach ($all_items as $item) {
                if (!$item['is_active']) {
                    $issues[] = "Inactive item found in getInventoryItems(): {$item['brand']} {$item['model']}";
                }
            }
            
            foreach ($pos_items as $item) {
                if (!$item['is_active']) {
                    $issues[] = "Inactive item found in getPOSInventoryItems(): {$item['brand']} {$item['model']}";
                }
            }
            
            if (empty($issues)) {
                echo "<div class='bg-green-50 border border-green-200 rounded p-4'>";
                echo "<div class='flex items-center'>";
                echo "<svg class='w-5 h-5 text-green-500 mr-2' fill='currentColor' viewBox='0 0 20 20'>";
                echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'></path>";
                echo "</svg>";
                echo "<span class='font-medium text-green-800'>All tests passed! Deleted items are properly filtered out.</span>";
                echo "</div>";
                echo "</div>";
            } else {
                echo "<div class='bg-red-50 border border-red-200 rounded p-4'>";
                echo "<div class='flex items-center mb-2'>";
                echo "<svg class='w-5 h-5 text-red-500 mr-2' fill='currentColor' viewBox='0 0 20 20'>";
                echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'></path>";
                echo "</svg>";
                echo "<span class='font-medium text-red-800'>Issues found:</span>";
                echo "</div>";
                echo "<ul class='text-sm text-red-700'>";
                foreach ($issues as $issue) {
                    echo "<li>• $issue</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Next Steps</h2>
            <div class="space-y-2 text-gray-700">
                <p>1. If all tests pass, the deletion fix is working correctly</p>
                <p>2. Deleted items will no longer appear in POS or project item selection</p>
                <p>3. The items remain in the database for audit purposes but are marked as inactive</p>
                <p>4. You can safely delete this test file after confirming the fix works</p>
            </div>
            
            <div class="mt-6 space-x-4">
                <a href="inventory.php" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                    Go to Inventory
                </a>
                <a href="pos.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    Go to POS
                </a>
                <a href="projects.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                    Go to Projects
                </a>
            </div>
        </div>
    </div>
</body>
</html>
