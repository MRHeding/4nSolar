<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - 4NSOLAR ELECTRICZ</title>
    <link href="assets/css/output.css" rel="stylesheet">
    <link href="assets/fontawesome/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-solar-blue to-blue-800 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8">
            <div class="text-center mb-8">
                <img src="images/logo.png" alt="4NSOLAR ELECTRICZ Logo" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-3xl font-bold text-solar-blue">4NSOLAR ELECTRICZ</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Inventory Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter your username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter your password">
                </div>

                <button type="submit" 
                        class="w-full bg-solar-blue text-white py-3 px-4 rounded-lg hover:bg-blue-800 transition duration-200 font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                <p class="mt-2 text-xs">© 2025 4NSOLAR ELECTRICZ. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
