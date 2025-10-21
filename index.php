<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4N Solar System - Under Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .development-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 90%;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .title {
            color: #333;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .features {
            text-align: left;
            margin: 2rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }
        
        .feature-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .feature-text {
            color: #555;
            font-weight: 500;
        }
        
        .progress-section {
            margin: 2rem 0;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
            background-color: rgba(102, 126, 234, 0.2);
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 2s ease;
        }
        
        .contact-info {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .contact-info h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .contact-item i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="development-container">
        <div class="logo">
            <i class="fas fa-solar-panel"></i>
        </div>
        
        <h1 class="title">4N Solar System</h1>
        <p class="subtitle">Solar Energy Management & Inventory System</p>
        
        <div class="status-badge">
            <i class="fas fa-tools me-2"></i>Under Development
        </div>
        
        <div class="features">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="feature-text">Advanced Inventory Management</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-solar-panel"></i>
                </div>
                <div class="feature-text">Solar Project Tracking</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="feature-text">Revenue Analysis & Reports</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="feature-text">Customer Management</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="feature-text">Quotation & POS System</div>
            </div>
        </div>
        
        <div class="progress-section">
            <h6 class="text-center mb-3">Development Progress</h6>
            <div class="progress">
                <div class="progress-bar" style="width: 75%"></div>
            </div>
            <small class="text-muted">75% Complete</small>
        </div>
        
        <div class="contact-info">
            <h6><i class="fas fa-info-circle me-2"></i>System Information</h6>
            <div class="contact-item">
                <i class="fas fa-calendar"></i>
                <span>Expected Launch: Q2 2024</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-code-branch"></i>
                <span>Version: 2.0 Beta</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-server"></i>
                <span>Status: Development Mode</span>
            </div>
        </div>
        
        <a href="login.php" class="btn-custom">
            <i class="fas fa-sign-in-alt me-2"></i>Access Development Version
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar on load
            setTimeout(() => {
                const progressBar = document.querySelector('.progress-bar');
                progressBar.style.width = '75%';
            }, 1000);
            
            // Add hover effects to feature items
            const featureItems = document.querySelectorAll('.feature-item');
            featureItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(10px)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>