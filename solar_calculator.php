<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/solar_calculator.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$page_title = 'Solar System Calculator';
$calculation_result = null;
$error_message = null;

// Handle form submission
if ($_POST && isset($_POST['calculate'])) {
    $appliances = [];
    
    // Process appliance data from form
    if (isset($_POST['appliances']) && is_array($_POST['appliances'])) {
        foreach ($_POST['appliances'] as $appliance_data) {
            if (!empty($appliance_data['name']) && !empty($appliance_data['wattage']) && !empty($appliance_data['hours'])) {
                $appliances[] = [
                    'name' => trim($appliance_data['name']),
                    'voltage' => floatval($appliance_data['voltage'] ?? 220),
                    'wattage' => floatval($appliance_data['wattage']),
                    'hours' => floatval($appliance_data['hours'])
                ];
            }
        }
    }
    
    if (!empty($appliances)) {
        $calculation_result = calculateSolarSystemRequirements($appliances);
        if (!$calculation_result['success']) {
            $error_message = $calculation_result['error'];
        }
    } else {
        $error_message = "Please add at least one appliance to calculate system requirements.";
    }
}

// Load sample data if requested
$sample_appliances = [];
if (isset($_GET['load_sample'])) {
    $sample_appliances = getSampleAppliances();
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Solar System Calculator</h1>
    <p class="text-gray-600 dark:text-gray-400">Calculate solar panel, inverter, and battery requirements based on your appliances</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-t-lg p-6">
        <div class="flex items-center">
            <i class="fas fa-solar-panel text-2xl mr-3"></i>
            <div>
                <h2 class="text-xl font-bold">Solar System Calculator</h2>
                <p class="text-blue-100 mt-1">Get accurate recommendations for your solar energy needs</p>
            </div>
        </div>
    </div>
    
    <div class="p-6">
        <?php if ($error_message): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="calculatorForm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                <div class="mb-4 lg:mb-0">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <i class="fas fa-plug mr-2 text-blue-600"></i>
                        Appliance Information
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Add all appliances you want to power with your solar system</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="?load_sample=1" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-download mr-2"></i>Load Sample Data
                    </a>
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors" onclick="addAppliance()">
                        <i class="fas fa-plus mr-2"></i>Add Appliance
                    </button>
                </div>
            </div>
                        
                        <div id="appliancesContainer">
                            <?php if (!empty($sample_appliances)): ?>
                                <?php foreach ($sample_appliances as $index => $appliance): ?>
                                    <div class="appliance-row mb-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Appliance Name</label>
                                                <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                       name="appliances[<?php echo $index; ?>][name]" 
                                                       value="<?php echo htmlspecialchars($appliance['name']); ?>" required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Voltage (V)</label>
                                                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                       name="appliances[<?php echo $index; ?>][voltage]" 
                                                       value="<?php echo $appliance['voltage']; ?>" min="12" max="480" required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Wattage (W)</label>
                                                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                       name="appliances[<?php echo $index; ?>][wattage]" 
                                                       value="<?php echo $appliance['wattage']; ?>" min="1" step="0.1" required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hours/Day</label>
                                                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                       name="appliances[<?php echo $index; ?>][hours]" 
                                                       value="<?php echo $appliance['hours']; ?>" min="0" max="24" step="0.1" required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daily kWh</label>
                                                <input type="text" class="daily-kwh w-full px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300" readonly>
                                            </div>
                                            <div class="flex justify-center">
                                                <button type="button" class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors" onclick="removeAppliance(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="appliance-row mb-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Appliance Name</label>
                                            <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                   name="appliances[0][name]" placeholder="e.g., LED Lights" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Voltage (V)</label>
                                            <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                   name="appliances[0][voltage]" value="220" min="12" max="480" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Wattage (W)</label>
                                            <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                   name="appliances[0][wattage]" placeholder="100" min="1" step="0.1" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hours/Day</label>
                                            <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                                   name="appliances[0][hours]" placeholder="6" min="0" max="24" step="0.1" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daily kWh</label>
                                            <input type="text" class="daily-kwh w-full px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300" readonly>
                                        </div>
                                        <div class="flex justify-center">
                                            <button type="button" class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors" onclick="removeAppliance(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Dynamic Calculate Button (appears after adding appliances) -->
                        <div id="dynamicCalculateButton" class="mt-6" style="display: none;">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6 flex items-center justify-center">
                                <button type="submit" name="calculate" class="w-full bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-300 flex items-center justify-center text-lg shadow-lg hover:shadow-xl transform hover:scale-105 hover:border-white/40">
                                    <i class="fas fa-calculator mr-3 text-xl"></i>
                                    <span class="font-bold">Calculate Solar System Requirements</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fas fa-bolt mr-2"></i>
                                    Total System Load
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold mb-1" id="totalWattage">0 W</div>
                                        <div class="text-blue-100 text-sm">Peak Load</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold mb-1" id="totalDailyKwh">0 kWh</div>
                                        <div class="text-blue-100 text-sm">Daily Energy</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($calculation_result && $calculation_result['success']): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mt-6">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-lg">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            Solar System Recommendations
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- System Summary -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
                                System Summary
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 text-center">
                                    <i class="fas fa-solar-panel text-3xl text-yellow-600 mb-3"></i>
                                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['solar_panel_kw']; ?> kW</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Solar Panels Required</div>
                                </div>
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 text-center">
                                    <i class="fas fa-microchip text-3xl text-blue-600 mb-3"></i>
                                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['inverter_kw']; ?> kW</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Inverter Capacity</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-700 rounded-lg p-4 text-center">
                                    <i class="fas fa-battery-full text-3xl text-green-600 mb-3"></i>
                                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['battery_kwh']; ?> kWh</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Battery Capacity</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border border-red-200 dark:border-red-700 rounded-lg p-4 text-center">
                                    <i class="fas fa-bolt text-3xl text-red-600 mb-3"></i>
                                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['system_voltage']; ?>V</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">System Voltage</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Recommendations -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <?php if (!empty($calculation_result['product_recommendations']['solar_panels'])): ?>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                        <i class="fas fa-solar-panel mr-2 text-yellow-600"></i>
                                        Recommended Solar Panels
                                    </h3>
                                    <div class="space-y-3">
                                        <?php foreach (array_slice($calculation_result['product_recommendations']['solar_panels'], 0, 3) as $panel): ?>
                                            <div class="bg-white dark:bg-gray-700 border <?php echo $panel['meets_requirement'] ? 'border-green-300 dark:border-green-600' : 'border-yellow-300 dark:border-yellow-600'; ?> rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2"><?php echo htmlspecialchars($panel['product']['brand'] . ' ' . $panel['product']['model']); ?></h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3"><?php echo htmlspecialchars($panel['product']['size_specification']); ?></p>
                                                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                                    <div class="text-gray-600 dark:text-gray-400">Quantity: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $panel['quantity_needed']; ?></span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Total: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $panel['total_system_watts']; ?>W</span></div>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <div class="text-lg font-bold text-green-600">PHP <?php echo number_format($panel['total_cost'], 2); ?></div>
                                                    <div class="text-sm text-gray-500">(PHP <?php echo $panel['cost_per_watt']; ?>/W)</div>
                                                </div>
                                            </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($calculation_result['product_recommendations']['inverters'])): ?>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                        <i class="fas fa-microchip mr-2 text-blue-600"></i>
                                        Recommended Inverters
                                    </h3>
                                    <div class="space-y-3">
                                        <?php foreach ($calculation_result['product_recommendations']['inverters'] as $inverter): ?>
                                            <div class="bg-white dark:bg-gray-700 border border-green-300 dark:border-green-600 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2"><?php echo htmlspecialchars($inverter['product']['brand'] . ' ' . $inverter['product']['model']); ?></h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3"><?php echo htmlspecialchars($inverter['product']['size_specification']); ?></p>
                                                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                                    <div class="text-gray-600 dark:text-gray-400">Capacity: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $inverter['inverter_kw']; ?>kW</span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Overhead: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $inverter['overhead_percentage']; ?>%</span></div>
                                                </div>
                                                <div class="text-lg font-bold text-green-600">PHP <?php echo number_format($inverter['cost'], 2); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($calculation_result['product_recommendations']['batteries'])): ?>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                        <i class="fas fa-battery-full mr-2 text-green-600"></i>
                                        Recommended Batteries
                                    </h3>
                                    <div class="space-y-3">
                                        <?php foreach (array_slice($calculation_result['product_recommendations']['batteries'], 0, 3) as $battery): ?>
                                            <div class="bg-white dark:bg-gray-700 border <?php echo $battery['meets_requirement'] ? 'border-green-300 dark:border-green-600' : 'border-yellow-300 dark:border-yellow-600'; ?> rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2"><?php echo htmlspecialchars($battery['product']['brand'] . ' ' . $battery['product']['model']); ?></h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3"><?php echo htmlspecialchars($battery['product']['size_specification']); ?></p>
                                                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                                    <div class="text-gray-600 dark:text-gray-400">Quantity: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $battery['quantity_needed']; ?></span></div>
                                                    <div class="text-gray-600 dark:text-gray-400">Total: <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo $battery['total_capacity_ah']; ?>Ah</span></div>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <div class="text-lg font-bold text-green-600">PHP <?php echo number_format($battery['total_cost'], 2); ?></div>
                                                    <div class="text-sm text-gray-500">(PHP <?php echo $battery['cost_per_ah']; ?>/Ah)</div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Detailed Analysis -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                    <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                                    Energy Analysis
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Daily Energy Need:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['energy_analysis']['total_daily_kwh']; ?> kWh</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Monthly Energy:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['energy_analysis']['total_monthly_kwh']; ?> kWh</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Peak Load:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['energy_analysis']['peak_load_kw']; ?> kW</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600 dark:text-gray-400">Backup Days:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['autonomy_days']; ?> days</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                    <i class="fas fa-cogs mr-2 text-green-600"></i>
                                    System Efficiency Factors
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Inverter Efficiency:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['efficiency_factors']['inverter_efficiency']; ?>%</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Battery Efficiency:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['efficiency_factors']['battery_efficiency']; ?>%</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">System Losses:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['efficiency_factors']['system_losses']; ?>%</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600 dark:text-gray-400">Safety Margin:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo $calculation_result['system_requirements']['efficiency_factors']['safety_margin']; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appliance Breakdown -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                                <i class="fas fa-list-ul mr-2 text-purple-600"></i>
                                Appliance Energy Breakdown
                            </h3>
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 dark:bg-gray-600">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Appliance</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Voltage</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Wattage</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hours/Day</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Daily kWh</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monthly kWh</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                                            <?php foreach ($calculation_result['appliance_summary'] as $appliance): ?>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($appliance['name']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300"><?php echo $appliance['voltage']; ?>V</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300"><?php echo $appliance['wattage']; ?>W</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300"><?php echo $appliance['hours']; ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300"><?php echo $appliance['daily_kwh']; ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300"><?php echo $appliance['monthly_kwh']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-blue-50 dark:bg-blue-900">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100">Total</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100">-</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo $calculation_result['energy_analysis']['total_wattage']; ?>W</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100">-</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo $calculation_result['energy_analysis']['total_daily_kwh']; ?></th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo $calculation_result['energy_analysis']['total_monthly_kwh']; ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let applianceCount = <?php echo !empty($sample_appliances) ? count($sample_appliances) : 1; ?>;

function addAppliance() {
    const container = document.getElementById('appliancesContainer');
    const newRow = document.createElement('div');
    newRow.className = 'appliance-row bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4 border border-gray-200 dark:border-gray-600';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Appliance Name</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" name="appliances[${applianceCount}][name]" placeholder="e.g., LED Lights" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Voltage (V)</label>
                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" name="appliances[${applianceCount}][voltage]" value="220" min="12" max="480" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Wattage (W)</label>
                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" name="appliances[${applianceCount}][wattage]" placeholder="100" min="1" step="0.1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hours/Day</label>
                <input type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" name="appliances[${applianceCount}][hours]" placeholder="6" min="0" max="24" step="0.1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daily kWh</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 daily-kwh" readonly>
            </div>
            <div>
                <button type="button" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center" onclick="removeAppliance(this)">
                    <i class="fas fa-trash mr-2"></i>
                    Remove
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    applianceCount++;
    updateTotals();
    
    // Add event listeners to the new row inputs for real-time updates
    const newInputs = newRow.querySelectorAll('input[name*="[name]"], input[name*="[wattage]"], input[name*="[hours]"]');
    newInputs.forEach(input => {
        input.addEventListener('input', updateTotals);
    });
}

function removeAppliance(button) {
    const container = document.getElementById('appliancesContainer');
    if (container.children.length > 1) {
        button.closest('.appliance-row').remove();
        updateTotals();
    }
}

function updateTotals() {
    let totalWattage = 0;
    let totalDailyKwh = 0;
    const applianceRows = document.querySelectorAll('.appliance-row');
    
    applianceRows.forEach(row => {
        const wattageInput = row.querySelector('input[name*="[wattage]"]');
        const hoursInput = row.querySelector('input[name*="[hours]"]');
        const dailyKwhInput = row.querySelector('.daily-kwh');
        
        const wattage = parseFloat(wattageInput.value) || 0;
        const hours = parseFloat(hoursInput.value) || 0;
        const dailyKwh = (wattage * hours) / 1000;
        
        dailyKwhInput.value = dailyKwh.toFixed(2);
        totalWattage += wattage;
        totalDailyKwh += dailyKwh;
    });
    
    document.getElementById('totalWattage').textContent = totalWattage.toFixed(0) + ' W';
    document.getElementById('totalDailyKwh').textContent = totalDailyKwh.toFixed(2) + ' kWh';
    
    // Show/hide dynamic calculate button based on appliance count and data
    const dynamicButton = document.getElementById('dynamicCalculateButton');
    const hasValidAppliances = Array.from(applianceRows).some(row => {
        const nameInput = row.querySelector('input[name*="[name]"]');
        const wattageInput = row.querySelector('input[name*="[wattage]"]');
        const hoursInput = row.querySelector('input[name*="[hours]"]');
        
        return nameInput.value.trim() !== '' && 
               parseFloat(wattageInput.value) > 0 && 
               parseFloat(hoursInput.value) > 0;
    });
    
    if (hasValidAppliances) {
        dynamicButton.style.display = 'block';
    } else {
        dynamicButton.style.display = 'none';
    }
}

// Add event listeners for real-time calculation
document.addEventListener('DOMContentLoaded', function() {
    updateTotals();
    
    // Add event listeners to existing appliance inputs
    document.querySelectorAll('#appliancesContainer input[name*="[name]"], #appliancesContainer input[name*="[wattage]"], #appliancesContainer input[name*="[hours]"]').forEach(input => {
        input.addEventListener('input', updateTotals);
    });
    
    // Also listen for changes on the container for dynamically added elements
    document.getElementById('appliancesContainer').addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[wattage]"], input[name*="[hours]"], input[name*="[name]"]')) {
            updateTotals();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>