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
        <link href="assets/fontawesome/all.min.css" rel="stylesheet">
        <style>
            .sidebar {
                background-color: #1e40af;
                min-height: 100vh;
                transition: all 0.3s ease-in-out;
                overflow-x: hidden;
            }
            .sidebar.collapsed {
                width: 60px !important;
                min-width: 60px !important;
            }
            .sidebar .nav-link {
                color: #e2e8f0;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                margin-bottom: 0.25rem;
                transition: all 0.3s ease;
                white-space: nowrap;
                overflow: hidden;
                position: relative;
            }
            .sidebar.collapsed .nav-link {
                padding: 0.75rem 0.5rem;
                text-align: center;
                justify-content: center;
            }
            .sidebar.collapsed .nav-link span {
                opacity: 0;
                width: 0;
                max-width: 0;
                transition: all 0.3s ease;
                overflow: hidden;
                display: none;
            }
            .sidebar.collapsed .nav-link:hover span {
                opacity: 0;
                width: 0;
                max-width: 0;
                display: none;
            }
            .sidebar.collapsed .nav-link:active span {
                opacity: 0;
                width: 0;
                max-width: 0;
                display: none;
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
            .main-content {
                transition: all 0.3s ease-in-out;
            }
            .main-content.expanded {
                margin-left: 0;
            }
            .sidebar-toggle {
                background: none;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 0.25rem;
                transition: background-color 0.3s ease;
            }
            .sidebar-toggle:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }
            .sidebar .nav-link i {
                min-width: 20px;
                text-align: center;
            }
            
            /* Mobile responsiveness */
            @media (max-width: 768px) {
                .sidebar {
                    position: fixed;
                    top: 0;
                    left: -100%;
                    z-index: 1000;
                    transition: left 0.3s ease-in-out;
                }
                .sidebar.show {
                    left: 0;
                }
                .main-content {
                    margin-left: 0 !important;
                }
                .sidebar.collapsed {
                    left: -100%;
                }
                .sidebar-backdrop {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 999;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease-in-out;
                }
                .sidebar-backdrop.show {
                    opacity: 1;
                    visibility: visible;
                }
            }
        </style>
    <?php else: ?>
        <!-- Tailwind CSS (Local) -->
        <link href="assets/css/output.css" rel="stylesheet">
        <link href="assets/fontawesome/all.min.css" rel="stylesheet">
        <style>
            /* Additional styles for Tailwind sidebar */
            #sidebar.collapsed .nav-link span {
                opacity: 0 !important;
                width: 0 !important;
                max-width: 0 !important;
                overflow: hidden !important;
                transition: all 0.3s ease !important;
                display: none !important;
            }
            #sidebar.collapsed .nav-link:hover span {
                opacity: 0 !important;
                width: 0 !important;
                max-width: 0 !important;
                display: none !important;
            }
            #sidebar.collapsed .nav-link:active span {
                opacity: 0 !important;
                width: 0 !important;
                max-width: 0 !important;
                display: none !important;
            }
            #sidebar.collapsed .nav-link {
                justify-content: center !important;
            }
            #sidebar.collapsed .nav-link:hover {
                background-color: #3b82f6 !important;
                color: white !important;
            }
        </style>
    <?php endif; ?>
</head>
<body class="<?php echo $use_bootstrap ? 'bg-light' : 'bg-gray-50'; ?>">
    <?php if (isLoggedIn()): ?>
    
    <?php if ($use_bootstrap): ?>
        <!-- Bootstrap Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1e40af;">
            <div class="container-fluid">
                <button class="sidebar-toggle me-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
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
                <nav class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span class="ms-2">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                                    <i class="fas fa-boxes"></i>
                                    <span class="ms-2">Inventory</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'quotations.php' ? 'active' : ''; ?>" href="quotations.php">
                                    <i class="fas fa-file-invoice"></i>
                                    <span class="ms-2">Quotations</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                                    <i class="fas fa-project-diagram"></i>
                                    <span class="ms-2">Projects</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                                    <i class="fas fa-cash-register"></i>
                                    <span class="ms-2">POS</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>" href="suppliers.php">
                                    <i class="fas fa-truck"></i>
                                    <span class="ms-2">Suppliers</span>
                                </a>
                            </li>
                            <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payroll.php', 'employees.php', 'employee_attendance.php', 'payroll_detail.php']) ? 'active' : ''; ?>" href="payroll.php">
                                    <i class="fas fa-money-check-alt"></i>
                                    <span class="ms-2">Payroll</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                                    <i class="fas fa-users"></i>
                                    <span class="ms-2">Users</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                    <i class="fas fa-chart-bar"></i>
                                    <span class="ms-2">Reports</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content" id="main-content">
    <?php else: ?>
        <!-- Tailwind Navigation -->
        <nav class="bg-solar-blue shadow-lg">
            <div class="px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-3">
                        <button class="sidebar-toggle text-white hover:bg-blue-600 p-2 rounded-lg transition-colors" onclick="toggleSidebar()">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
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
            <!-- Mobile Backdrop -->
            <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()"></div>
            
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-lg min-h-screen transition-all duration-300 ease-in-out" id="sidebar">
                <div class="p-4">
                    <ul class="space-y-2">
                        <li>
                            <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-tachometer-alt w-5 text-center"></i>
                                <span class="transition-all duration-300">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-boxes w-5 text-center"></i>
                                <span class="transition-all duration-300">Inventory</span>
                            </a>
                        </li>
                        <li>
                            <a href="quotations.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'quotations.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-file-invoice w-5 text-center"></i>
                                <span class="transition-all duration-300">Quotations</span>
                            </a>
                        </li>
                        <li>
                            <a href="projects.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-project-diagram w-5 text-center"></i>
                                <span class="transition-all duration-300">Projects</span>
                            </a>
                        </li>
                        <li>
                            <a href="pos.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-cash-register w-5 text-center"></i>
                                <span class="transition-all duration-300">POS</span>
                            </a>
                        </li>
                        <li>
                            <a href="suppliers.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-truck w-5 text-center"></i>
                                <span class="transition-all duration-300">Suppliers</span>
                            </a>
                        </li>
                        <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                        <li>
                            <a href="payroll.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payroll.php', 'employees.php', 'employee_attendance.php', 'payroll_detail.php']) ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-money-check-alt w-5 text-center"></i>
                                <span class="transition-all duration-300">Payroll</span>
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-users w-5 text-center"></i>
                                <span class="transition-all duration-300">Users</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="reports.php" class="sidebar-link flex items-center space-x-3 text-gray-700 p-3 rounded-lg hover:bg-solar-blue hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-solar-blue text-white' : ''; ?>">
                                <i class="fas fa-chart-bar w-5 text-center"></i>
                                <span class="transition-all duration-300">Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-6 transition-all duration-300 ease-in-out" id="main-content">
    <?php endif; ?>
            <?php endif; ?>
            
            <!-- Page Content Goes Here -->
            <?php if (isset($content_start) && $content_start): ?>
            <div class="max-w-7xl mx-auto">
            <?php endif; ?>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar && mainContent) {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    // Mobile behavior - toggle show/hide
                    sidebar.classList.toggle('show');
                    if (backdrop) {
                        backdrop.classList.toggle('show');
                    }
                } else {
                    // Desktop behavior - toggle collapsed/expanded
                    sidebar.classList.toggle('collapsed');
                    
                    // For Bootstrap version
                    if (sidebar.classList.contains('collapsed')) {
                        sidebar.style.width = '60px';
                        sidebar.style.minWidth = '60px';
                        mainContent.style.marginLeft = '0';
                    } else {
                        sidebar.style.width = '';
                        sidebar.style.minWidth = '';
                        mainContent.style.marginLeft = '';
                    }
                    
                    // For Tailwind version
                    if (sidebar.classList.contains('collapsed')) {
                        sidebar.classList.remove('w-64');
                        sidebar.classList.add('w-16');
                        
                        // Hide text spans
                        const spans = sidebar.querySelectorAll('span');
                        spans.forEach(span => {
                            span.style.opacity = '0';
                            span.style.width = '0';
                            span.style.maxWidth = '0';
                            span.style.overflow = 'hidden';
                            span.style.display = 'none';
                        });
                    } else {
                        sidebar.classList.remove('w-16');
                        sidebar.classList.add('w-64');
                        
                        // Show text spans
                        const spans = sidebar.querySelectorAll('span');
                        spans.forEach(span => {
                            span.style.opacity = '1';
                            span.style.width = 'auto';
                            span.style.maxWidth = 'none';
                            span.style.overflow = 'visible';
                            span.style.display = 'inline';
                        });
                    }
                    
                    // Save state to localStorage (desktop only)
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            }
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar) {
                sidebar.classList.remove('show');
                if (backdrop) {
                    backdrop.classList.remove('show');
                }
            }
        }
        
        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (sidebar && mainContent) {
                const isMobile = window.innerWidth <= 768;
                
                if (!isMobile) {
                    // Only restore state on desktop
                    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                        
                        // For Bootstrap version
                        sidebar.style.width = '60px';
                        sidebar.style.minWidth = '60px';
                        mainContent.style.marginLeft = '0';
                        
                        // For Tailwind version
                        sidebar.classList.remove('w-64');
                        sidebar.classList.add('w-16');
                        
                        // Hide text spans
                        const spans = sidebar.querySelectorAll('span');
                        spans.forEach(span => {
                            span.style.opacity = '0';
                            span.style.width = '0';
                            span.style.maxWidth = '0';
                            span.style.overflow = 'hidden';
                            span.style.display = 'none';
                        });
                    }
                }
                
                // Prevent sidebar toggle when clicking on menu items when collapsed
                const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        if (sidebar.classList.contains('collapsed') && !isMobile) {
                            e.preventDefault();
                            // Navigate to the link after a short delay to allow for visual feedback
                            setTimeout(() => {
                                window.location.href = this.href;
                            }, 100);
                        }
                    });
                });
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar && mainContent) {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    // On mobile, remove collapsed state and show class
                    sidebar.classList.remove('collapsed', 'show');
                    sidebar.style.width = '';
                    sidebar.style.minWidth = '';
                    mainContent.style.marginLeft = '';
                    
                    // Hide backdrop
                    if (backdrop) {
                        backdrop.classList.remove('show');
                    }
                    
                    // Reset Tailwind classes
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    
                    // Show text spans
                    const spans = sidebar.querySelectorAll('span');
                    spans.forEach(span => {
                        span.style.opacity = '1';
                        span.style.width = 'auto';
                        span.style.maxWidth = 'none';
                        span.style.overflow = 'visible';
                        span.style.display = 'inline';
                    });
                }
            }
        });
    </script>
