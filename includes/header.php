<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>4NSOLAR ELECTRICZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'solar-blue': '#1E40AF',
                        'solar-yellow': '#FCD34D',
                        'solar-green': '#059669',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="bg-solar-blue shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="images/logo.png" alt="4NSOLAR ELECTRICZ Logo" class="h-8 w-auto">
                    <div>
                        <h1 class="text-white text-xl font-bold">4NSOLAR ELECTRICZ</h1>
                        <p class="text-blue-200 text-sm">Inventory Management System</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="text-white">
                        <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="ml-2 px-2 py-1 bg-blue-600 rounded text-xs uppercase"><?php echo $_SESSION['role']; ?></span>
                    </div>
                    <a href="logout.php" class="text-white hover:text-solar-yellow">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="inventory.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-boxes"></i>
                            <span>Inventory</span>
                        </a>
                    </li>
                    <li>
                        <a href="projects.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-project-diagram"></i>
                            <span>Solar Projects</span>
                        </a>
                    </li>
                    <li>
                        <a href="pos.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-cash-register"></i>
                            <span>Point of Sale</span>
                        </a>
                    </li>
                    <li>
                        <a href="suppliers.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-truck"></i>
                            <span>Suppliers</span>
                        </a>
                    </li>
                    <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-solar-blue text-white' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <?php endif; ?>
            
            <!-- Page Content Goes Here -->
            <?php if (isset($content_start) && $content_start): ?>
            <div class="max-w-7xl mx-auto">
            <?php endif; ?>
