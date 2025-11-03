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
function calculateSolarSystemRequirements($appliances, $system_voltage = 48) {
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
    $system_requirements = calculateSystemComponents($energy_analysis, $system_voltage);
    
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
        $required_fields = ['name', 'quantity', 'wattage', 'hours'];
        foreach ($required_fields as $field) {
            if (!isset($appliance[$field]) || empty($appliance[$field])) {
                return ['valid' => false, 'error' => "Missing {$field} for appliance at index {$index}"];
            }
        }
        
        // Validate numeric values
        if (!is_numeric($appliance['quantity']) || $appliance['quantity'] <= 0) {
            return ['valid' => false, 'error' => "Invalid quantity for appliance '{$appliance['name']}'"];
        }
        
        if (!is_numeric($appliance['wattage']) || $appliance['wattage'] <= 0) {
            return ['valid' => false, 'error' => "Invalid wattage for appliance '{$appliance['name']}'"];
        }
        
        if (!is_numeric($appliance['hours']) || $appliance['hours'] < 0 || $appliance['hours'] > 24) {
            return ['valid' => false, 'error' => "Invalid hours (0-24) for appliance '{$appliance['name']}'"];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Calculate total energy consumption and analysis
 */
function calculateEnergyConsumption($appliances) {
    $total_wattage = 0;
    $total_daily_w = 0;
    $appliance_summary = [];
    
    foreach ($appliances as $appliance) {
        $total_appliance_wattage = $appliance['quantity'] * $appliance['wattage'];
        $daily_w = $total_appliance_wattage * $appliance['hours'];
        $total_wattage += $total_appliance_wattage;
        $total_daily_w += $daily_w;
        
        $appliance_summary[] = [
            'name' => $appliance['name'],
            'quantity' => $appliance['quantity'],
            'wattage' => $appliance['wattage'],
            'total_wattage' => $total_appliance_wattage,
            'hours' => $appliance['hours'],
            'daily_w' => round($daily_w, 0),
            'monthly_w' => round($daily_w * 30, 0)
        ];
    }
    
    return [
        'appliance_summary' => $appliance_summary,
        'total_wattage' => $total_wattage,
        'total_daily_w' => round($total_daily_w, 0),
        'total_monthly_w' => round($total_daily_w * 30, 0),
        'total_yearly_w' => round($total_daily_w * 365, 0),
        'peak_load_kw' => round($total_wattage / 1000, 2)
    ];
}

/**
 * Calculate system components with efficiency factors and safety margins
 */
function calculateSystemComponents($energy_analysis, $system_voltage = 48) {
    // System efficiency factors
    $inverter_efficiency = 0.90; // 90% inverter efficiency
    $battery_efficiency = 0.85; // 85% battery round-trip efficiency
    $system_losses = 0.15; // 15% system losses (wiring, dust, temperature)
    $safety_margin = 1.25; // 25% safety margin
    
    // Days of autonomy for battery backup
    $autonomy_days = 2; // 2 days backup
    
    // Calculate adjusted energy requirements
    // Convert daily watts to kWh for system calculations (divide by 1000)
    $daily_kwh = $energy_analysis['total_daily_w'] / 1000;
    $daily_kwh_adjusted = $daily_kwh / ($inverter_efficiency * (1 - $system_losses));
    $daily_kwh_with_safety = $daily_kwh_adjusted * $safety_margin;
    
    // Solar panel requirements (assuming 5 peak sun hours average)
    $peak_sun_hours = 5;
    $required_solar_kw = $daily_kwh_with_safety / $peak_sun_hours;
    
    // Battery requirements
    $battery_kwh_needed = ($daily_kwh * $autonomy_days) / $battery_efficiency;
    $battery_kwh_with_safety = $battery_kwh_needed * $safety_margin;
    
    // Apply voltage-specific battery capacity factor
    $battery_capacity_factor = 1.0; // Default factor
    if (in_array($system_voltage, [51.2, 25.6, 12.8])) {
        $battery_capacity_factor = 0.67; // 33% reduction (factor less 33%)
    }
    
    // Apply the capacity factor to battery calculations
    $battery_kwh_with_safety = $battery_kwh_with_safety * $battery_capacity_factor;
    
    // Inverter requirements (peak load + safety margin)
    $inverter_kw_needed = $energy_analysis['peak_load_kw'] * $safety_margin;
    
    return [
        'daily_energy_need' => round($daily_kwh, 2),
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
        'battery_capacity_factor' => $battery_capacity_factor,
        'efficiency_factors' => [
            'inverter_efficiency' => $inverter_efficiency * 100,
            'battery_efficiency' => $battery_efficiency * 100,
            'system_losses' => $system_losses * 100,
            'safety_margin' => ($safety_margin - 1) * 100
        ]
    ];
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
    $required_watts = $requirements['inverter_watts'];
    $system_voltage = $requirements['system_voltage'];
    
    foreach ($inverters as $inverter) {
        // Extract wattage and voltage information from size specification
        $inverter_info = extractInverterInfo($inverter);
        if (!$inverter_info['watts']) continue;
        
        $inverter_watts = $inverter_info['watts'];
        $inverter_voltage = $inverter_info['voltage'];
        
        // Check if inverter meets minimum wattage requirement
        if ($inverter_watts < $required_watts) continue;
        
        // Check voltage compatibility
        $voltage_compatible = true;
        if ($inverter_voltage) {
            // For LiFePO4 systems, prefer inverters that support the specific voltage
            if (in_array($system_voltage, [51.2, 25.6, 12.8])) {
                // Check if inverter supports the LiFePO4 voltage range
                $voltage_compatible = (
                    ($system_voltage == 51.2 && ($inverter_voltage == 48 || $inverter_voltage == 51.2)) ||
                    ($system_voltage == 25.6 && ($inverter_voltage == 24 || $inverter_voltage == 25.6)) ||
                    ($system_voltage == 12.8 && ($inverter_voltage == 12 || $inverter_voltage == 12.8))
                );
            } else {
                // For standard voltages, exact match or compatible range
                $voltage_compatible = (
                    $inverter_voltage == $system_voltage ||
                    ($system_voltage == 48 && in_array($inverter_voltage, [12, 24, 48])) ||
                    ($system_voltage == 24 && in_array($inverter_voltage, [12, 24])) ||
                    ($system_voltage == 12 && $inverter_voltage == 12)
                );
            }
        }
        
        if (!$voltage_compatible) continue;
        
        // Calculate overhead percentage
        $overhead_percentage = round((($inverter_watts - $required_watts) / $required_watts) * 100, 1);
        
        // Calculate efficiency score (lower is better)
        $efficiency_score = 0;
        
        // Wattage efficiency (prefer closer to requirement, but not under)
        if ($overhead_percentage <= 20) {
            $efficiency_score += $overhead_percentage * 0.5; // Prefer minimal overhead
        } else {
            $efficiency_score += 10 + ($overhead_percentage - 20) * 1.5; // Penalize excessive overhead
        }
        
        // Voltage compatibility bonus
        if ($inverter_voltage && $inverter_voltage == $system_voltage) {
            $efficiency_score -= 5; // Bonus for exact voltage match
        } else if ($inverter_voltage) {
            $efficiency_score += 2; // Small penalty for voltage conversion
        }
        
        // Cost factor (normalize by wattage)
        $cost_per_watt = $inverter['selling_price'] / $inverter_watts;
        $efficiency_score += $cost_per_watt * 100; // Weight cost in efficiency
        
        $recommendations[] = [
            'product' => $inverter,
            'inverter_kw' => round($inverter_watts / 1000, 1),
            'inverter_watts' => $inverter_watts,
            'inverter_voltage' => $inverter_voltage,
            'meets_requirement' => true,
            'overhead_percentage' => $overhead_percentage,
            'cost' => $inverter['selling_price'],
            'cost_per_watt' => round($cost_per_watt, 3),
            'voltage_compatible' => $voltage_compatible,
            'efficiency_score' => $efficiency_score
        ];
    }
    
    // Sort by efficiency score (lower is better)
    usort($recommendations, function($a, $b) {
        return $a['efficiency_score'] <=> $b['efficiency_score'];
    });
    
    return array_slice($recommendations, 0, 3); // Return top 3 recommendations
}

/**
 * Calculate battery recommendations
 */
function calculateBatteryRecommendations($batteries, $requirements) {
    $recommendations = [];
    $system_voltage = $requirements['system_voltage'];
    
    foreach ($batteries as $battery) {
        // Extract both capacity and voltage information
        $battery_info = extractBatteryInfo($battery);
        if (!$battery_info['capacity_ah']) continue;
        
        $capacity_ah = $battery_info['capacity_ah'];
        $battery_voltage = $battery_info['voltage'];
        
        // Filter batteries by nominal voltage compatibility
        // For LiFePO4 voltages (51.2V, 25.6V, 12.8V), prefer matching nominal voltages
        $voltage_compatible = false;
        
        if (in_array($system_voltage, [51.2, 25.6, 12.8])) {
            // For LiFePO4 system voltages, prefer batteries with matching nominal voltages
            if ($battery_voltage && abs($battery_voltage - $system_voltage) < 1.0) {
                $voltage_compatible = true;
            } else if (!$battery_voltage) {
                // If voltage not specified, assume compatible but with lower priority
                $voltage_compatible = true;
            }
        } else {
            // For standard voltages (48V, 24V, 12V), use traditional matching
            if ($battery_voltage) {
                $voltage_compatible = ($battery_voltage == $system_voltage || 
                                    ($system_voltage == 48 && in_array($battery_voltage, [12, 24, 48])) ||
                                    ($system_voltage == 24 && in_array($battery_voltage, [12, 24])));
            } else {
                $voltage_compatible = true; // Assume compatible if voltage not specified
            }
        }
        
        if (!$voltage_compatible) continue;
        
        $batteries_needed = ceil($requirements['battery_ah'] / $capacity_ah);
        $total_capacity_ah = $batteries_needed * $capacity_ah;
        $total_cost = $batteries_needed * $battery['selling_price'];
        
        // Calculate priority score (lower is better)
        $priority_score = 0;
        
        // Voltage matching priority
        if ($battery_voltage && abs($battery_voltage - $system_voltage) < 0.1) {
            $priority_score += 0; // Perfect voltage match
        } else if ($battery_voltage) {
            $priority_score += 10; // Close voltage match
        } else {
            $priority_score += 20; // Unknown voltage
        }
        
        // Cost effectiveness
        $cost_per_ah = round($battery['selling_price'] / $capacity_ah, 2);
        $priority_score += $cost_per_ah * 0.1; // Weight cost in priority
        
        $recommendations[] = [
            'product' => $battery,
            'capacity_ah' => $capacity_ah,
            'battery_voltage' => $battery_voltage,
            'quantity_needed' => $batteries_needed,
            'total_capacity_ah' => $total_capacity_ah,
            'total_cost' => $total_cost,
            'cost_per_ah' => $cost_per_ah,
            'meets_requirement' => $total_capacity_ah >= $requirements['battery_ah'],
            'voltage_compatible' => $voltage_compatible,
            'priority_score' => $priority_score
        ];
    }
    
    // Sort by priority score (voltage compatibility + cost effectiveness)
    usort($recommendations, function($a, $b) {
        return $a['priority_score'] <=> $b['priority_score'];
    });
    
    return array_slice($recommendations, 0, 5); // Return top 5 recommendations
}

/**
 * Extract inverter wattage and voltage from product information
 */
function extractInverterInfo($inverter) {
    $text = ($inverter['brand'] . ' ' . $inverter['model'] . ' ' . $inverter['size_specification']);
    $watts = null;
    $voltage = null;
    
    // Look for wattage patterns
    // Try kW first (more common in specifications)
    if (preg_match('/(\d+(?:\.\d+)?)\s*kw/i', $text, $matches)) {
        $watts = floatval($matches[1]) * 1000;
    } else if (preg_match('/(\d+)\s*w/i', $text, $matches)) {
        $watts = intval($matches[1]);
    }
    
    // Look for voltage patterns
    // LiFePO4 nominal voltages: 51.2V, 25.6V, 12.8V
    if (preg_match('/51\.?2\s*v/i', $text)) {
        $voltage = 51.2;
    } else if (preg_match('/25\.?6\s*v/i', $text)) {
        $voltage = 25.6;
    } else if (preg_match('/12\.?8\s*v/i', $text)) {
        $voltage = 12.8;
    } else if (preg_match('/(\d+(?:\.\d+)?)\s*v/i', $text, $matches)) {
        $voltage = floatval($matches[1]);
    }
    
    return [
        'watts' => $watts,
        'voltage' => $voltage
    ];
}

/**
 * Extract battery capacity and voltage from product information
 */
function extractBatteryInfo($battery) {
    $text = ($battery['brand'] . ' ' . $battery['model'] . ' ' . $battery['size_specification']);
    $capacity_ah = null;
    $voltage = null;
    
    // Look for voltage patterns first
    // LiFePO4 nominal voltages: 51.2V, 25.6V, 12.8V
    if (preg_match('/51\.?2\s*v/i', $text)) {
        $voltage = 51.2;
    } else if (preg_match('/25\.?6\s*v/i', $text)) {
        $voltage = 25.6;
    } else if (preg_match('/12\.?8\s*v/i', $text)) {
        $voltage = 12.8;
    } else if (preg_match('/(\d+(?:\.\d+)?)\s*v/i', $text, $matches)) {
        $voltage = floatval($matches[1]);
    }
    
    // Look for capacity patterns
    // Look for patterns like "100ah", "200Ah", "100 ah"
    if (preg_match('/(\d+)\s*ah/i', $text, $matches)) {
        $capacity_ah = intval($matches[1]);
    }
    
    // Look for patterns like "12v 100ah" or "51.2v 100ah"
    if (preg_match('/\d+(?:\.\d+)?v\s*(\d+)ah/i', $text, $matches)) {
        $capacity_ah = intval($matches[1]);
    }
    
    return [
        'capacity_ah' => $capacity_ah,
        'voltage' => $voltage
    ];
}

/**
 * Extract battery capacity from product information (legacy function for backward compatibility)
 */
function extractBatteryCapacity($battery) {
    $battery_info = extractBatteryInfo($battery);
    return $battery_info['capacity_ah'];
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
        ['name' => 'Refrigerator', 'quantity' => 1, 'wattage' => 100, 'hours' => 24],
        ['name' => 'LED Light Bulb', 'quantity' => 2, 'wattage' => 10, 'hours' => 5],
        ['name' => 'Electric Fan', 'quantity' => 1, 'wattage' => 75, 'hours' => 8],
        ['name' => 'WiFi Router', 'quantity' => 1, 'wattage' => 10, 'hours' => 24],
        ['name' => 'Laptop', 'quantity' => 1, 'wattage' => 65, 'hours' => 3]
    ];
}
?>