<?php
require_once 'includes/config.php';
require_once 'includes/suppliers.php';

echo "Current categories in the system:\n";
echo "================================\n";

$categories = getCategories();
foreach ($categories as $category) {
    echo "ID: {$category['id']} | Name: {$category['name']}\n";
    echo "Description: {$category['description']}\n";
    echo "Created: {$category['created_at']}\n";
    echo "Active: " . ($category['is_active'] ? 'Yes' : 'No') . "\n";
    echo "--------------------------------\n";
}

echo "\nTotal categories: " . count($categories) . "\n";
?>
