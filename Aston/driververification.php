<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../WS/Login.php');
    exit();
}

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../WS/homepage.php');
    exit();
}

$host = '127.0.0.1';
$dbname = 'wdd';        
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     
    $sql = "SELECT
            d.Driver_ID,
            d.License,
            d.Car_Model,
            d.Car_Color,
            d.Plate_Number,
            d.Seat_Available,
            d.IC_Photo_URL,
            d.License_Photo_URL,
            d.Status,
            u.user_id,
            u.apu_id,
            u.name,
            u.username,
            u.email,
            u.phone_number,
            u.gender,
            u.photo_url
        FROM driver d
        JOIN user_acc u ON u.user_id = d.user_id
        WHERE d.Status = 'pending'
        ORDER BY d.Driver_ID DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Database error in admin_driver_verification: " . $e->getMessage());
    $db_error = true;
    $pending = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Verification | GOBy Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --warning: #f59e0b;
            --success: #10b981;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #f0f9ff 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Header Styles */
        .header {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
            border-left: 6px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary-light), transparent 70%);
            border-radius: 0 0 0 100%;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 16px;
            color: var(--gray-600);
            margin-bottom: 20px;
        }

        .stats-card {
            display: flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(135deg, var(--primary-light), white);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid rgba(16, 185, 129, 0.2);
            max-width: 400px;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: var(--shadow);
        }

        .stats-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 4px;
        }

        .stats-content p {
            color: var(--gray-600);
            font-size: 14px;
        }

        /* Main Content */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
        }

        /* Application Cards */
        .applications-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .pending-count {
            background: var(--warning);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* No Applications */
        .no-applications {
            text-align: center;
            padding: 60px 20px;
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 2px dashed var(--gray-200);
        }

        .no-applications i {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 20px;
        }

        .no-applications h3 {
            font-size: 20px;
            color: var(--gray-700);
            margin-bottom: 12px;
        }

        .no-applications p {
            color: var(--gray-500);
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Applications Grid */
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        /* Application Card */
        .application-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            position: relative;
        }

        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .card-header {
            background: linear-gradient(135deg, var(--gray-50), var(--primary-light));
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: var(--shadow);
            background: white;
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .user-info .username {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 6px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--warning);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-content {
            padding: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            background: var(--gray-50);
            padding: 16px;
            border-radius: var(--radius-sm);
            border-left: 3px solid var(--primary);
        }

        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--gray-500);
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            word-break: break-word;
        }

        .documents-section {
            margin-bottom: 24px;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .document-item {
            height: 140px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            position: relative;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
        }

        .document-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .document-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            font-size: 12px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .btn {
            padding: 14px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, var(--primary-dark), #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        /* Message Box */
        .message-box {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1000;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .message {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .message.success {
            border-left-color: var(--success);
            background: linear-gradient(135deg, #f0fdf4, white);
        }

        .message.error {
            border-left-color: var(--danger);
            background: linear-gradient(135deg, #fef2f2, white);
        }

        .message i {
            font-size: 20px;
        }

        .message.success i {
            color: var(--success);
        }

        .message.error i {
            color: var(--danger);
        }

        .message-content {
            flex: 1;
        }

        .message-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .message-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 4px;
            transition: var(--transition);
        }

        .message-close:hover {
            color: var(--gray-600);
        }

        /* Footer Actions */
        .footer-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid var(--gray-200);
        }

        .btn-outline {
            padding: 14px 32px;
            border-radius: var(--radius);
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-solid {
            padding: 14px 32px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
        }

        .btn-solid:hover {
            background: linear-gradient(135deg, var(--primary-dark), #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .applications-grid {
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .container {
                padding: 16px;
            }
            
            .applications-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 24px;
            }
            
            .page-title {
                font-size: 28px;
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .documents-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .footer-actions {
                flex-direction: column;
            }
            
            .btn-outline, .btn-solid {
                width: 100%;
                text-align: center;
            }
            
            .card-header {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .user-avatar {
                width: 80px;
                height: 80px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stats-card {
                flex-direction: column;
                text-align: center;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }

        /* Animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .application-card {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .application-card:nth-child(1) { animation-delay: 0.1s; }
        .application-card:nth-child(2) { animation-delay: 0.2s; }
        .application-card:nth-child(3) { animation-delay: 0.3s; }
        .application-card:nth-child(4) { animation-delay: 0.4s; }
        .application-card:nth-child(5) { animation-delay: 0.5s; }
        .application-card:nth-child(6) { animation-delay: 0.6s; }
        .application-card:nth-child(7) { animation-delay: 0.7s; }
        .application-card:nth-child(8) { animation-delay: 0.8s; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="page-title">Driver Verification Portal</h1>
                <p class="page-subtitle">Review and manage pending driver applications with ease</p>
                
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="pendingCount"><?php echo count($pending); ?></h3>
                        <p>Pending Applications</p>
                    </div>
                </div>
            </div>
        </header>

        <div id="messageBox" class="message-box"></div>

        <!-- Main Content -->
        <main class="content-grid">
            <section class="applications-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-file-alt"></i>
                        Applications to Review
                        <?php if (count($pending) > 0): ?>
                        <span class="pending-count"><?php echo count($pending); ?> pending</span>
                        <?php endif; ?>
                    </h2>
                </div>

                <?php if (isset($db_error) && $db_error): ?>
                <div class="no-applications">
                    <i class="fas fa-database"></i>
                    <h3>Database Connection Error</h3>
                    <p>Unable to load driver verification data. Please try again later or contact support.</p>
                </div>
                <?php elseif (count($pending) === 0): ?>
                <div class="no-applications">
                    <i class="fas fa-check-circle"></i>
                    <h3>All Clear! 🎉</h3>
                    <p>No pending driver applications to review at this time. All applications have been processed.</p>
                    <a href="../WS/homepage.php" class="btn-solid">Go to Homepage</a>
                </div>
                <?php else: ?>
                <div class="applications-grid" id="applicationsGrid">
                    <?php foreach ($pending as $driver): 
                        $driverId = (int)$driver['Driver_ID'];
                        $photo = $driver['photo_url'] ?: 'max.jpg';
                        $icPhoto = $driver['IC_Photo_URL'] ?: '';
                        $licPhoto = $driver['License_Photo_URL'] ?: '';
                    ?>
                    <div class="application-card" id="driverCard<?php echo $driverId; ?>">
                        <div class="card-header">
                            <div class="user-avatar">
                                <img src="/ASS-WDD/<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($driver['name']); ?>">
                            </div>
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($driver['name']); ?></h4>
                                <div class="username">@<?php echo htmlspecialchars($driver['username']); ?> • <?php echo htmlspecialchars($driver['apu_id']); ?></div>
                                <span class="status-badge">
                                    <i class="fas fa-clock"></i> Pending Review
                                </span>
                            </div>
                        </div>

                        <div class="card-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Phone</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['phone_number']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Car Model</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['Car_Model']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Car Color</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['Car_Color']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">License Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['License']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Seats Available</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['Seat_Available']); ?></div>
                                </div>
                                <div class="info-item" style="grid-column: span 2;">
                                    <div class="info-label">Plate Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($driver['Plate_Number']); ?></div>
                                </div>
                            </div>

                            <div class="documents-section">
                                <h3 style="font-size: 14px; color: var(--gray-600); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-file-image"></i> Supporting Documents
                                </h3>
                                <div class="documents-grid">
                                    <div class="document-item">
                                        <?php if ($icPhoto): ?>
                                            <img src="/ASS-WDD/<?php echo htmlspecialchars($icPhoto); ?>" alt="IC Photo">
                                        <?php else: ?>
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--gray-400);">
                                                <i class="fas fa-id-card" style="font-size: 32px; margin-bottom: 8px;"></i>
                                                <span>No IC Photo</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="document-overlay">IC Document</div>
                                    </div>
                                    
                                    <div class="document-item">
                                        <?php if ($licPhoto): ?>
                                            <img src="/ASS-WDD/<?php echo htmlspecialchars($licPhoto); ?>" alt="License Photo">
                                        <?php else: ?>
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--gray-400);">
                                                <i class="fas fa-id-badge" style="font-size: 32px; margin-bottom: 8px;"></i>
                                                <span>No License Photo</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="document-overlay">Driving License</div>
                                    </div>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button class="btn btn-approve" onclick="handleAction(<?php echo $driverId; ?>, 'approve')" id="approveBtn<?php echo $driverId; ?>">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button class="btn btn-reject" onclick="handleAction(<?php echo $driverId; ?>, 'reject')" id="rejectBtn<?php echo $driverId; ?>">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- Footer Actions -->
        <div class="footer-actions">
            <a href="../LK/AdminPanel.php" class="btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Admin Panel
            </a>
            <a href="../WS/homepage.php" class="btn-solid">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
        </div>
    </div>

    <script>
        function showMessage(type, title, text) {
            const messageBox = document.getElementById('messageBox');
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <div class="message-content">
                    <div class="message-title">${title}</div>
                    <div class="message-text">${text}</div>
                </div>
                <button class="message-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            messageBox.appendChild(message);
            setTimeout(() => {
                if (message.parentElement) {
                    message.style.opacity = '0';
                    message.style.transform = 'translateX(100%)';
                    setTimeout(() => message.remove(), 300);
                }
            }, 5000);
        }
        async function handleAction(driverId, action) {
            const approveBtn = document.getElementById('approveBtn' + driverId);
            const rejectBtn = document.getElementById('rejectBtn' + driverId);
            const card = document.getElementById('driverCard' + driverId);

            approveBtn.disabled = true;
            rejectBtn.disabled = true;

            if (action === 'approve') {
                approveBtn.innerHTML = '<div class="loading"></div>';
            } else {
                rejectBtn.innerHTML = '<div class="loading"></div>';
            }
            
            try {
                const response = await fetch("verify_driver.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `driver_id=${encodeURIComponent(driverId)}&action=${encodeURIComponent(action)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('success', 'Action Completed', data.message || 'The application has been processed successfully.');

                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.remove();

                        const countEl = document.getElementById('pendingCount');
                        let current = parseInt(countEl.textContent);
                        countEl.textContent = Math.max(0, current - 1);

                        const pendingBadge = document.querySelector('.pending-count');
                        if (pendingBadge) {
                            let badgeCount = parseInt(pendingBadge.textContent.split(' ')[0]);
                            badgeCount--;
                            if (badgeCount <= 0) {
                                pendingBadge.remove();
                                document.querySelector('.applications-grid').innerHTML = `
                                    <div class="no-applications" style="grid-column: 1/-1;">
                                        <i class="fas fa-check-circle"></i>
                                        <h3>All Clear! 🎉</h3>
                                        <p>No pending driver applications to review at this time. All applications have been processed.</p>
                                        <a href="homepage.php" class="btn-solid">Go to Homepage</a>
                                    </div>
                                `;
                            } else {
                                pendingBadge.textContent = badgeCount + ' pending';
                            }
                        }
                    }, 500);
                } else {
                    showMessage('error', 'Action Failed', data.message || 'Failed to process the application. Please try again.');

                    approveBtn.disabled = false;
                    rejectBtn.disabled = false;

                    approveBtn.innerHTML = '<i class="fas fa-check-circle"></i> Approve';
                    rejectBtn.innerHTML = '<i class="fas fa-times-circle"></i> Reject';
                }
            } catch (error) {
                showMessage('error', 'Network Error', 'Unable to connect to the server. Please check your connection and try again.');

                approveBtn.disabled = false;
                rejectBtn.disabled = false;

                approveBtn.innerHTML = '<i class="fas fa-check-circle"></i> Approve';
                rejectBtn.innerHTML = '<i class="fas fa-times-circle"></i> Reject';
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl + A to approve first pending application
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                const firstApproveBtn = document.querySelector('.btn-approve:not([disabled])');
                if (firstApproveBtn) {
                    firstApproveBtn.click();
                }
            }
            
            // Ctrl + R to reject first pending application
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                const firstRejectBtn = document.querySelector('.btn-reject:not([disabled])');
                if (firstRejectBtn) {
                    firstRejectBtn.click();
                }
            }
            
            // F5 to refresh
            if (e.key === 'F5') {
                e.preventDefault();
                location.reload();
            }
        });

        // Add animation to page load
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>