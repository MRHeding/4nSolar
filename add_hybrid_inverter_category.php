<?php
require_once 'includes/config.php';
require_once 'includes/suppliers.php';

// Add Hybrid Inverter category
$name = 'Hybrid Inverter';
$description = 'Hybrid inverters that can work with both solar panels and batteries, providing grid-tie and backup functionality';

// Check if category already exists
$categories = getCategories(false); // Get all categories including inactive ones
$exists = false;
foreach ($categories as $category) {
    if (strtolower($category['name']) === strtolower($name)) {
        $exists = true;
        echo "Category '$name' already exists with ID: " . $category['id'] . "\n";
        if (!$category['is_active']) {
            echo "Category is currently inactive.\n";
        }
        break;
    }
}

if (!$exists) {
    if (addCategory($name, $description)) {
        echo "Successfully added category: $name\n";
        echo "Description: $description\n";
        
        // Get the new category ID
        $categories = getCategories();
        foreach ($categories as $category) {
            if ($category['name'] === $name) {
                echo "New category ID: " . $category['id'] . "\n";
                break;
            }
        }
    } else {
        echo "Failed to add category: $name\n";
    }
} else {
    echo "Category already exists, no action needed.\n";
}

echo "\nCurrent categories:\n";
$all_categories = getCategories();
foreach ($all_categories as $category) {
    echo "- ID: {$category['id']}, Name: {$category['name']}\n";
}
?>
