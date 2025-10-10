<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// Redirect to dashboard if user is already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

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
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></title>
    <link href="assets/css/racing-sans-one.css" rel="stylesheet">
    <link href="assets/css/output.css" rel="stylesheet">
    <link href="assets/fontawesome/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1e3a8a;
            background-image: url('images/login-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .login-overlay {
            background: linear-gradient(135deg, rgba(177, 193, 238, 0.85), rgba(59, 130, 246, 0.7));
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative">
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 login-overlay"></div>
    <div class="max-w-md w-full mx-4 relative z-10">
        <div class="bg-white bg-opacity-90 backdrop-blur-sm rounded-lg shadow-2xl p-8 border border-blue-200">
            <div class="text-center mb-8">
                <img src="<?php echo htmlspecialchars(getSystemSetting('logo_url', 'images/logo.png')); ?>" alt="<?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?> Logo" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-3xl font-bold" style="color: #0000ff; font-family: 'Racing Sans One', cursive;"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></h1>
                <p class="mt-2 font-medium" style="color: #0000ff;"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Business Management System')); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium mb-2" style="color: #0000ff;">
                        <i class="fas fa-user mr-2" style="color: #0000ff;"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent bg-white bg-opacity-90"
                           placeholder="Enter your username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-2" style="color: #0000ff;">
                        <i class="fas fa-lock mr-2" style="color: #0000ff;"></i>Password
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent bg-white bg-opacity-90"
                           placeholder="Enter your password">
                </div>

                <button type="submit" 
                        class="w-full text-white py-3 px-4 rounded-lg transition duration-200 font-medium"
                        style="background-color: #0000ff;"
                        onmouseover="this.style.backgroundColor='#000080'"
                        onmouseout="this.style.backgroundColor='#0000ff'">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <div class="mt-8 text-center text-sm" style="color: #0000ff;">
                <p class="mt-2 text-xs font-medium">© 2025 <?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
