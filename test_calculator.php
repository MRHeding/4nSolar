<?php
// Test script for the solar calculator
require_once 'includes/config.php';  // Include database connection
require_once 'includes/solar_calculator.php';

echo "<h1>Solar Calculator Test</h1>\n";

// Test with sample appliances
$appliances = [
    ['name' => 'LED Light', 'voltage' => 12, 'wattage' => 10, 'hours' => 8],
    ['name' => 'Refrigerator', 'voltage' => 220, 'wattage' => 150, 'hours' => 24],
    ['name' => 'TV', 'voltage' => 220, 'wattage' => 100, 'hours' => 6],
    ['name' => 'Laptop', 'voltage' => 220, 'wattage' => 65, 'hours' => 8],
    ['name' => 'Fan', 'voltage' => 220, 'wattage' => 75, 'hours' => 12]
];

echo "<h2>Test Appliances:</h2>\n";
echo "<ul>\n";
foreach ($appliances as $appliance) {
    echo "<li>{$appliance['name']}: {$appliance['wattage']}W at {$appliance['voltage']}V for {$appliance['hours']} hours/day</li>\n";
}
echo "</ul>\n";

// Calculate recommendations
$result = calculateSolarSystemRequirements($appliances);

if ($result['success']) {
    echo "<h2>Calculation Results:</h2>\n";
    echo "<pre>" . print_r($result, true) . "</pre>\n";
    
    // Test individual functions
    echo "<h2>Individual Function Tests:</h2>\n";
    
    // Test energy consumption calculation
    $energy = calculateEnergyConsumption($appliances);
    echo "<p><strong>Total Daily Energy:</strong> {$energy['total_daily_kwh']} kWh</p>\n";
    echo "<p><strong>Total Wattage:</strong> {$energy['total_wattage']} W</p>\n";
    
    // Test system voltage determination
    $voltage = determineSystemVoltage($energy['total_wattage']);
    echo "<p><strong>Recommended System Voltage:</strong> {$voltage}V</p>\n";
    
    // Test validation
    $validAppliances = [
        ['name' => 'Test Light', 'voltage' => 12, 'wattage' => 20, 'hours' => 5]
    ];
    $validation = validateApplianceInput($validAppliances);
    echo "<p><strong>Validation Test:</strong> " . ($validation['valid'] ? 'PASSED' : 'FAILED') . "</p>\n";
    
    // Test invalid input
    $invalidAppliances = [
        ['name' => '', 'voltage' => 12, 'wattage' => -10, 'hours' => 25]
    ];
    $invalidValidation = validateApplianceInput($invalidAppliances);
    echo "<p><strong>Invalid Input Test:</strong> " . (!$invalidValidation['valid'] ? 'PASSED' : 'FAILED') . "</p>\n";
    if (!$invalidValidation['valid']) {
        echo "<p><strong>Validation Errors:</strong> " . $invalidValidation['error'] . "</p>\n";
    }
    
} else {
    echo "<h2>Error in Calculation:</h2>\n";
    if (isset($result['error'])) {
        echo "<p>" . $result['error'] . "</p>\n";
    } else {
        echo "<p>Unknown error occurred</p>\n";
    }
}

echo "<h2>Test Complete</h2>\n";
?>