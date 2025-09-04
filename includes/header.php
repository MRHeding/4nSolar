<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>4NSOLAR ELECTRICZ</title>
    <?php 
    // Determine if we're in the payroll section
    $payroll_pages = ['payroll.php', 'employees.php', 'employee_attendance.php', 'payroll_detail.php', 'payroll_slip.php'];
    $current_page = basename($_SERVER['PHP_SELF']);
    $use_bootstrap = in_array($current_page, $payroll_pages);
    ?>
    
    <?php if ($use_bootstrap): ?>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .sidebar {
                background-color: #1e40af;
                min-height: 100vh;
            }
            .sidebar .nav-link {
                color: #e2e8f0;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                margin-bottom: 0.25rem;
            }
            .sidebar .nav-link:hover,
            .sidebar .nav-link.active {
                background-color: #3b82f6;
                color: white;
            }
            .navbar-brand {
                color: white !important;
                font-weight: bold;
            }
        </style>
    <?php else: ?>
        <!-- Tailwind CSS -->
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
    <?php endif; ?>
</head>
<body class="<?php echo $use_bootstrap ? 'bg-light' : 'bg-gray-50'; ?>">
    <?php if (isLoggedIn()): ?>
    
    <?php if ($use_bootstrap): ?>
        <!-- Bootstrap Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1e40af;">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                    <img src="images/logo.png" alt="4NSOLAR ELECTRICZ Logo" height="40" class="me-3">
                    <div>
                        <div class="fw-bold">4NSOLAR ELECTRICZ</div>
                        <small class="text-light">Management System</small>
                    </div>
                </a>
                
                <div class="d-flex align-items-center text-white">
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="badge bg-primary me-3"><?php echo strtoupper($_SESSION['role']); ?></span>
                    <a href="logout.php" class="text-white text-decoration-none">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Bootstrap Sidebar -->
                <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                                    <i class="fas fa-boxes me-2"></i> Inventory
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                                    <i class="fas fa-project-diagram me-2"></i> Solar Projects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                                    <i class="fas fa-cash-register me-2"></i> Point of Sale
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>" href="suppliers.php">
                                    <i class="fas fa-truck me-2"></i> Suppliers
                                </a>
                            </li>
                            <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payroll.php', 'employees.php', 'employee_attendance.php', 'payroll_detail.php']) ? 'active' : ''; ?>" href="payroll.php">
                                    <i class="fas fa-money-check-alt me-2"></i> Payroll
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                                    <i class="fas fa-users me-2"></i> Users
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                    <i class="fas fa-chart-bar me-2"></i> Reports
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <?php else: ?>
        <!-- Tailwind Navigation -->
        <nav class="bg-solar-blue shadow-lg">
            <div class="px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-3">
                        <img src="images/logo.png" alt="4NSOLAR ELECTRICZ Logo" class="h-12 w-auto">
                        <div class="flex flex-col justify-start">
                            <h1 class="text-white text-2xl font-bold leading-tight text-left">4NSOLAR ELECTRICZ</h1>
                            <p class="text-blue-200 text-sm font-medium text-left">Inventory Management System</p>
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
                            <a href="payroll.php" class="flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payroll.php', 'employees.php', 'employee_attendance.php', 'payroll_detail.php']) ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-money-check-alt"></i>
                                <span>Payroll</span>
                            </a>
                        </li>
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
            <?php endif; ?>
            
            <!-- Page Content Goes Here -->
            <?php if (isset($content_start) && $content_start): ?>
            <div class="max-w-7xl mx-auto">
            <?php endif; ?>
