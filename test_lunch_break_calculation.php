<?php
require_once 'includes/config.php';
require_once 'includes/payroll.php';

echo "<h2>Lunch Break Calculation Test</h2>";
echo "<p>Testing the corrected hour calculation with lunch break deduction:</p>";

// Test cases
$test_cases = [
    ['time_in' => '08:30:00', 'time_out' => '17:30:00', 'expected' => 8.00, 'status' => 'present'],
    ['time_in' => '08:00:00', 'time_out' => '17:00:00', 'expected' => 8.00, 'status' => 'present'],
    ['time_in' => '08:03:00', 'time_out' => '17:07:00', 'expected' => 8.00, 'status' => 'present'], // Should cap at 8.00
    ['time_in' => '08:30:00', 'time_out' => '12:30:00', 'expected' => 4.00, 'status' => 'half_day'], // Half day
    ['time_in' => '08:00:00', 'time_out' => '12:00:00', 'expected' => 4.00, 'status' => 'half_day'], // Half day
    ['time_in' => '08:30:00', 'time_out' => '19:30:00', 'expected' => 10.00, 'status' => 'overtime'], // Overtime (no cap)
];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Time In</th><th>Time Out</th><th>Raw Hours</th><th>Calculated Hours</th><th>Expected</th><th>Status</th></tr>";

foreach ($test_cases as $test) {
    $time_in = $test['time_in'];
    $time_out = $test['time_out'];
    $expected = $test['expected'];
    $status = $test['status'];
    
    // Calculate raw hours
    $time_in_obj = DateTime::createFromFormat('H:i:s', $time_in);
    $time_out_obj = DateTime::createFromFormat('H:i:s', $time_out);
    $diff = $time_out_obj->diff($time_in_obj);
    $raw_hours = $diff->h + ($diff->i / 60) + ($diff->s / 3600);
    
    // Calculate with lunch break and status
    $calculated_hours = calculateHoursWithLunchBreak($time_in, $time_out, $status);
    
    $test_status = abs($calculated_hours - $expected) < 0.01 ? '✅ PASS' : '❌ FAIL';
    
    echo "<tr>";
    echo "<td>$time_in</td>";
    echo "<td>$time_out</td>";
    echo "<td>" . number_format($raw_hours, 2) . "</td>";
    echo "<td>" . number_format($calculated_hours, 2) . "</td>";
    echo "<td>" . number_format($expected, 2) . "</td>";
    echo "<td>$status</td>";
    echo "<td>$test_status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Explanation:</h3>";
echo "<ul>";
echo "<li><strong>8:30 AM to 5:30 PM:</strong> Raw = 9 hours, Calculated = 8 hours (9 - 1 hour lunch break)</li>";
echo "<li><strong>8:00 AM to 5:00 PM:</strong> Raw = 9 hours, Calculated = 8 hours (9 - 1 hour lunch break)</li>";
echo "<li><strong>8:03 AM to 5:07 PM:</strong> Raw = 9.07 hours, Calculated = 8.00 hours (capped at 8 hours for regular work)</li>";
echo "<li><strong>Half day (4 hours or less):</strong> No lunch break deduction</li>";
echo "<li><strong>Overtime status:</strong> No 8-hour cap, allows overtime hours</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> The system now correctly accounts for the 1-hour lunch break (12 PM to 1 PM) for all shifts longer than 5 hours, and caps regular work at 8.00 hours.</p>";
?>
