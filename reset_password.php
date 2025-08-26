<?php
require_once 'includes/config.php';

// This script resets the admin password to 'admin123'
// Run this file in your browser if you can't login

$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "<h2>Password Reset Successful!</h2>";
    echo "<p>Admin password has been reset to: <strong>admin123</strong></p>";
    echo "<p>You can now <a href='login.php'>login here</a></p>";
    echo "<p><strong>Important:</strong> Delete this file after use for security!</p>";
    
} catch(PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>Could not reset password: " . $e->getMessage() . "</p>";
    echo "<p>Make sure your database is set up correctly.</p>";
}
?>
