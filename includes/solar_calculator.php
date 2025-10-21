<?php
/**
 * Solar System Calculator
 * Comprehensive function to calculate solar system requirements based on appliance loads
 */

/**
 * Calculate solar system requirements based on appliance data
 * 
 * @param array $appliances Array of appliances with name, voltage, wattage, and hours
 * @return array Comprehensive recommendations including panels, inverter, and battery
 */
function calculateSolarSystemRequirements($appliances) {
    // Input validation
    $validation = validateApplianceInput($appliances);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'error' => $validation['error'],
            'recommendations' => null
        ];
    }
    
    // Calculate total energy consumption
    $energy_analysis = calculateEnergyConsumption($appliances);
    
    // Calculate system requirements with safety margins
    $system_requirements = calculateSystemComponents($energy_analysis);
    
    // Get product recommendations from inventory
    $product_recommendations = getProductRecommendations($system_requirements);
    
    return [
        'success' => true,
        'error' => null,
        'appliance_summary' => $energy_analysis['appliance_summary'],
        'energy_analysis' => $energy_analysis,
        'system_requirements' => $system_requirements,
        'product_recommendations' => $product_recommendations,
        'recommendations' => formatRecommendations($system_requirements, $product_recommendations)
    ];
}

/**
 * Validate appliance input data
 */
function validateApplianceInput($appliances) {
    if (empty($appliances) || !is_array($appliances)) {
        return ['valid' => false, 'error' => 'No appliances provided or invalid format'];
    }
    
    foreach ($appliances as $index => $appliance) {
        // Check required fields
        $required_fields = ['name', 'voltage', 'wattage', 'hours'];
        foreach ($required_fields as $field) {
            if (!isset($appliance[$field]) || empty($appliance[$field])) {
                return ['valid' => false, 'error' => "Missing {$field} for appliance at index {$index}"];
            }
        }
        
        // Validate numeric values
        if (!is_numeric($appliance['voltage']) || $appliance['voltage'] <= 0) {
            return ['valid' => false, 'error' => "Invalid voltage for appliance '{$appliance['name']}'"];
        }
        
        if (!is_numeric($appliance['wattage']) || $appliance['wattage'] <= 0) {
            return ['valid' => false, 'error' => "Invalid wattage for appliance '{$appliance['name']}'"];
        }
        
        if (!is_numeric($appliance['hours']) || $appliance['hours'] < 0 || $appliance['hours'] > 24) {
            return ['valid' => false, 'error' => "Invalid hours (0-24) for appliance '{$appliance['name']}'"];
        }
        
        // Validate voltage ranges (common household voltages)
        if ($appliance['voltage'] < 12 || $appliance['voltage'] > 480) {
            return ['valid' => false, 'error' => "Voltage out of range (12-480V) for appliance '{$appliance['name']}'"];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Calculate total energy consumption and analysis
 */
function calculateEnergyConsumption($appliances) {
    $total_wattage = 0;
    $total_daily_kwh = 0;
    $appliance_summary = [];
    $voltage_analysis = [];
    
    foreach ($appliances as $appliance) {
        $daily_kwh = ($appliance['wattage'] * $appliance['hours']) / 1000;
        $total_wattage += $appliance['wattage'];
        $total_daily_kwh += $daily_kwh;
        
        $appliance_summary[] = [
            'name' => $appliance['name'],
            'voltage' => $appliance['voltage'],
            'wattage' => $appliance['wattage'],
            'hours' => $appliance['hours'],
            'daily_kwh' => round($daily_kwh, 2),
            'monthly_kwh' => round($daily_kwh * 30, 2)
        ];
        
        // Track voltage requirements
        $voltage_key = $appliance['voltage'] . 'V';
        if (!isset($voltage_analysis[$voltage_key])) {
            $voltage_analysis[$voltage_key] = ['count' => 0, 'total_wattage' => 0];
        }
        $voltage_analysis[$voltage_key]['count']++;
        $voltage_analysis[$voltage_key]['total_wattage'] += $appliance['wattage'];
    }
    
    return [
        'appliance_summary' => $appliance_summary,
        'total_wattage' => $total_wattage,
        'total_daily_kwh' => round($total_daily_kwh, 2),
        'total_monthly_kwh' => round($total_daily_kwh * 30, 2),
        'total_yearly_kwh' => round($total_daily_kwh * 365, 2),
        'voltage_analysis' => $voltage_analysis,
        'peak_load_kw' => round($total_wattage / 1000, 2)
    ];
}

/**
 * Calculate system components with efficiency factors and safety margins
 */
function calculateSystemComponents($energy_analysis) {
    // System efficiency factors
    $inverter_efficiency = 0.90; // 90% inverter efficiency
    $battery_efficiency = 0.85; // 85% battery round-trip efficiency
    $system_losses = 0.15; // 15% system losses (wiring, dust, temperature)
    $safety_margin = 1.25; // 25% safety margin
    
    // Days of autonomy for battery backup
    $autonomy_days = 2; // 2 days backup
    
    // Calculate adjusted energy requirements
    // Fix: Apply system losses correctly - divide by (1 - losses) not multiply
    $daily_kwh_adjusted = $energy_analysis['total_daily_kwh'] / ($inverter_efficiency * (1 - $system_losses));
    $daily_kwh_with_safety = $daily_kwh_adjusted * $safety_margin;
    
    // Solar panel requirements (assuming 5 peak sun hours average)
    $peak_sun_hours = 5;
    $required_solar_kw = $daily_kwh_with_safety / $peak_sun_hours;
    
    // Battery requirements
    $battery_kwh_needed = ($energy_analysis['total_daily_kwh'] * $autonomy_days) / $battery_efficiency;
    $battery_kwh_with_safety = $battery_kwh_needed * $safety_margin;
    
    // Inverter requirements (peak load + safety margin)
    $inverter_kw_needed = $energy_analysis['peak_load_kw'] * $safety_margin;
    
    // Determine system voltage (based on most common appliance voltage)
    $system_voltage = determineSystemVoltage($energy_analysis['voltage_analysis']);
    
    return [
        'daily_energy_need' => round($energy_analysis['total_daily_kwh'], 2),
        'adjusted_daily_energy' => round($daily_kwh_adjusted, 2),
        'safety_adjusted_energy' => round($daily_kwh_with_safety, 2),
        'solar_panel_kw' => round($required_solar_kw, 2),
        'solar_panel_watts' => round($required_solar_kw * 1000),
        'battery_kwh' => round($battery_kwh_with_safety, 2),
        'battery_ah' => round(($battery_kwh_with_safety * 1000) / $system_voltage),
        'inverter_kw' => round($inverter_kw_needed, 2),
        'inverter_watts' => round($inverter_kw_needed * 1000),
        'system_voltage' => $system_voltage,
        'peak_load_kw' => $energy_analysis['peak_load_kw'],
        'autonomy_days' => $autonomy_days,
        'efficiency_factors' => [
            'inverter_efficiency' => $inverter_efficiency * 100,
            'battery_efficiency' => $battery_efficiency * 100,
            'system_losses' => $system_losses * 100,
            'safety_margin' => ($safety_margin - 1) * 100
        ]
    ];
}

/**
 * Determine optimal system voltage based on appliance analysis
 */
function determineSystemVoltage($voltage_analysis) {
    // Default to 48V for most residential systems
    $default_voltage = 48;
    
    // If most appliances are 12V or 24V, use that
    if (isset($voltage_analysis['12V']) && $voltage_analysis['12V']['total_wattage'] > 2000) {
        return 12;
    }
    if (isset($voltage_analysis['24V']) && $voltage_analysis['24V']['total_wattage'] > 3000) {
        return 24;
    }
    
    return $default_voltage;
}

/**
 * Get product recommendations from inventory
 */
function getProductRecommendations($requirements) {
    global $pdo;
    
    $recommendations = [
        'solar_panels' => [],
        'inverters' => [],
        'batteries' => [],
        'accessories' => []
    ];
    
    try {
        // Get solar panels
        $stmt = $pdo->prepare("
            SELECT * FROM inventory_items 
            WHERE category_id = 1 AND is_active = 1 
            ORDER BY CAST(REGEXP_SUBSTR(size_specification, '[0-9]+') AS UNSIGNED) DESC
        ");
        $stmt->execute();
        $panels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $recommendations['solar_panels'] = calculatePanelRecommendations($panels, $requirements);
        
        // Get hybrid inverters
        $stmt = $pdo->prepare("
            SELECT * FROM inventory_items 
            WHERE category_id = 9 AND is_active = 1 
            ORDER BY CAST(REGEXP_SUBSTR(size_specification, '[0-9]+') AS UNSIGNED) ASC
        ");
        $stmt->execute();
        $inverters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $recommendations['inverters'] = calculateInverterRecommendations($inverters, $requirements);
        
        // Get batteries
        $stmt = $pdo->prepare("
            SELECT * FROM inventory_items 
            WHERE category_id = 3 AND is_active = 1 
            ORDER BY selling_price ASC
        ");
        $stmt->execute();
        $batteries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $recommendations['batteries'] = calculateBatteryRecommendations($batteries, $requirements);
        
    } catch (Exception $e) {
        error_log("Error getting product recommendations: " . $e->getMessage());
    }
    
    return $recommendations;
}

/**
 * Calculate solar panel recommendations
 */
function calculatePanelRecommendations($panels, $requirements) {
    $recommendations = [];
    
    foreach ($panels as $panel) {
        // Extract wattage from size specification
        preg_match('/(\d+)w/i', $panel['size_specification'], $matches);
        if (!$matches) continue;
        
        $panel_watts = intval($matches[1]);
        $panels_needed = ceil($requirements['solar_panel_watts'] / $panel_watts);
        $total_system_watts = $panels_needed * $panel_watts;
        $total_cost = $panels_needed * $panel['selling_price'];
        
        $recommendations[] = [
            'product' => $panel,
            'panel_watts' => $panel_watts,
            'quantity_needed' => $panels_needed,
            'total_system_watts' => $total_system_watts,
            'total_cost' => $total_cost,
            'cost_per_watt' => round($panel['selling_price'] / $panel_watts, 2),
            'meets_requirement' => $total_system_watts >= $requirements['solar_panel_watts']
        ];
    }
    
    // Sort by cost effectiveness (cost per watt)
    usort($recommendations, function($a, $b) {
        return $a['cost_per_watt'] <=> $b['cost_per_watt'];
    });
    
    return array_slice($recommendations, 0, 5); // Return top 5 recommendations
}

/**
 * Calculate inverter recommendations
 */
function calculateInverterRecommendations($inverters, $requirements) {
    $recommendations = [];
    
    foreach ($inverters as $inverter) {
        // Extract wattage from size specification
        preg_match('/(\d+)kw/i', $inverter['size_specification'], $matches);
        if (!$matches) continue;
        
        $inverter_kw = intval($matches[1]);
        $inverter_watts = $inverter_kw * 1000;
        
        if ($inverter_watts >= $requirements['inverter_watts']) {
            $recommendations[] = [
                'product' => $inverter,
                'inverter_kw' => $inverter_kw,
                'inverter_watts' => $inverter_watts,
                'meets_requirement' => true,
                'overhead_percentage' => round((($inverter_watts - $requirements['inverter_watts']) / $requirements['inverter_watts']) * 100, 1),
                'cost' => $inverter['selling_price']
            ];
        }
    }
    
    // Sort by overhead percentage (closest to requirement first)
    usort($recommendations, function($a, $b) {
        return $a['overhead_percentage'] <=> $b['overhead_percentage'];
    });
    
    return array_slice($recommendations, 0, 3); // Return top 3 recommendations
}

/**
 * Calculate battery recommendations
 */
function calculateBatteryRecommendations($batteries, $requirements) {
    $recommendations = [];
    
    foreach ($batteries as $battery) {
        // Try to extract capacity information from size specification or name
        $capacity_ah = extractBatteryCapacity($battery);
        if (!$capacity_ah) continue;
        
        $batteries_needed = ceil($requirements['battery_ah'] / $capacity_ah);
        $total_capacity_ah = $batteries_needed * $capacity_ah;
        $total_cost = $batteries_needed * $battery['selling_price'];
        
        $recommendations[] = [
            'product' => $battery,
            'capacity_ah' => $capacity_ah,
            'quantity_needed' => $batteries_needed,
            'total_capacity_ah' => $total_capacity_ah,
            'total_cost' => $total_cost,
            'cost_per_ah' => round($battery['selling_price'] / $capacity_ah, 2),
            'meets_requirement' => $total_capacity_ah >= $requirements['battery_ah']
        ];
    }
    
    // Sort by cost effectiveness (cost per Ah)
    usort($recommendations, function($a, $b) {
        return $a['cost_per_ah'] <=> $b['cost_per_ah'];
    });
    
    return array_slice($recommendations, 0, 5); // Return top 5 recommendations
}

/**
 * Extract battery capacity from product information
 */
function extractBatteryCapacity($battery) {
    $text = ($battery['brand'] . ' ' . $battery['model'] . ' ' . $battery['size_specification']);
    
    // Look for patterns like "100ah", "200Ah", "100 ah"
    if (preg_match('/(\d+)\s*ah/i', $text, $matches)) {
        return intval($matches[1]);
    }
    
    // Look for patterns like "12v 100ah"
    if (preg_match('/\d+v\s*(\d+)ah/i', $text, $matches)) {
        return intval($matches[1]);
    }
    
    return null;
}

/**
 * Format final recommendations for display
 */
function formatRecommendations($requirements, $products) {
    $formatted = [
        'system_summary' => [
            'total_daily_energy' => $requirements['daily_energy_need'] . ' kWh',
            'recommended_solar_capacity' => $requirements['solar_panel_kw'] . ' kW (' . $requirements['solar_panel_watts'] . ' watts)',
            'recommended_inverter_capacity' => $requirements['inverter_kw'] . ' kW (' . $requirements['inverter_watts'] . ' watts)',
            'recommended_battery_capacity' => $requirements['battery_kwh'] . ' kWh (' . $requirements['battery_ah'] . ' Ah @ ' . $requirements['system_voltage'] . 'V)',
            'system_voltage' => $requirements['system_voltage'] . 'V',
            'backup_days' => $requirements['autonomy_days'] . ' days'
        ],
        'top_recommendations' => []
    ];
    
    // Add top product recommendations
    if (!empty($products['solar_panels'])) {
        $top_panel = $products['solar_panels'][0];
        $formatted['top_recommendations']['solar_panel'] = [
            'product_name' => $top_panel['product']['brand'] . ' ' . $top_panel['product']['model'] . ' ' . $top_panel['product']['size_specification'],
            'quantity' => $top_panel['quantity_needed'],
            'total_capacity' => $top_panel['total_system_watts'] . ' watts',
            'estimated_cost' => 'PHP ' . number_format($top_panel['total_cost'], 2)
        ];
    }
    
    if (!empty($products['inverters'])) {
        $top_inverter = $products['inverters'][0];
        $formatted['top_recommendations']['inverter'] = [
            'product_name' => $top_inverter['product']['brand'] . ' ' . $top_inverter['product']['model'] . ' ' . $top_inverter['product']['size_specification'],
            'capacity' => $top_inverter['inverter_kw'] . ' kW',
            'estimated_cost' => 'PHP ' . number_format($top_inverter['cost'], 2)
        ];
    }
    
    if (!empty($products['batteries'])) {
        $top_battery = $products['batteries'][0];
        $formatted['top_recommendations']['battery'] = [
            'product_name' => $top_battery['product']['brand'] . ' ' . $top_battery['product']['model'] . ' ' . $top_battery['product']['size_specification'],
            'quantity' => $top_battery['quantity_needed'],
            'total_capacity' => $top_battery['total_capacity_ah'] . ' Ah',
            'estimated_cost' => 'PHP ' . number_format($top_battery['total_cost'], 2)
        ];
    }
    
    return $formatted;
}

/**
 * Get sample appliance data for testing
 */
function getSampleAppliances() {
    return [
        ['name' => 'Refrigerator', 'voltage' => 220, 'wattage' => 100, 'hours' => 24],
        ['name' => 'LED Light Bulb (2 pcs)', 'voltage' => 220, 'wattage' => 20, 'hours' => 5],
        ['name' => 'Electric Fan', 'voltage' => 220, 'wattage' => 75, 'hours' => 8],
        ['name' => 'WiFi Router', 'voltage' => 220, 'wattage' => 10, 'hours' => 24],
        ['name' => 'Laptop', 'voltage' => 220, 'wattage' => 65, 'hours' => 3]
    ];
}
?>