<?php
echo "<h1>4N Solar System - Data Verification & Cleanup</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .step{border:2px solid #007cba;margin:20px 0;padding:20px;border-radius:5px;} .step h2{color:#007cba;margin-top:0;}</style>\n";

echo "<div class='step'>\n";
echo "<h2>Step 1: Data Cleanup</h2>\n";
echo "<p>Running data consistency fixes...</p>\n";
echo "<iframe src='fix_data_consistency.php' width='100%' height='400' style='border:1px solid #ccc;'></iframe>\n";
echo "</div>\n";

echo "<div class='step'>\n";
echo "<h2>Step 2: Data Verification</h2>\n";
echo "<p>Verifying reports accuracy...</p>\n";
echo "<iframe src='verify_reports_accuracy.php' width='100%' height='600' style='border:1px solid #ccc;'></iframe>\n";
echo "</div>\n";

echo "<div class='step'>\n";
echo "<h2>Step 3: Test Reports Page</h2>\n";
echo "<p>You can now test the reports page at: <a href='reports.php' target='_blank'>reports.php</a></p>\n";
echo "<p>All data consistency issues should be resolved and the reports should display accurate information.</p>\n";
echo "</div>\n";
?>
