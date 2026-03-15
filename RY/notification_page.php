<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// count unread
$sqlBadge = "SELECT COUNT(*) as unread_count FROM user_notification 
            WHERE user_ID = $current_user_id 
            AND is_read = 0
            ";

$resultBadge = $conn->query($sqlBadge);
$rowBadge = $resultBadge->fetch_assoc();
$unread_count = $rowBadge['unread_count'];

//avatar
$sql = "SELECT * FROM user_acc WHERE user_id = $current_user_id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql = "SELECT 
            n.not_id,
            n.title,
            n.message,
            n.date,
            un.is_read
        FROM user_notification un
        JOIN notification n ON un.not_ID = n.not_id
        WHERE un.user_ID = $current_user_id
        ";

if ($filter == 'unread') {
    $sql .= " AND un.is_read = 0";
}

$sql .= " ORDER BY n.not_id DESC"; 

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <style>

        * { box-sizing: border-box; }

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            --border-radius: 12px;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        a { text-decoration: none; }

        .container { 
            display: flex;
            min-height: 100vh;
            position: relative; 
            overflow-x: hidden; 
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--eco-green) 0%, #1b5e20 100%);
            height: 100vh;
            position: fixed;
            left: 0; 
            top: 0;
            display: flex; 
            flex-direction: column;
            overflow-y: auto; 
            z-index: 1000;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .sidebar-header { 
            padding: 25px 20px; 
            text-align: center; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
            margin-bottom: 20px; 
        }

        .sidebar-logo { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 12px; color: white; 
            font-size: 22px; 
            font-weight: 700; 
        }

        .logo-icon { 
            width: 36px; 
            height: 36px; 
            background: white; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: var(--eco-green); 
            font-size: 20px; 
            flex-shrink: 0; 
        }

        .sidebar-nav { 
            padding: 0 20px; 
            flex-grow: 1; 
        }

        .nav-section { 
            margin-bottom: 25px;
        }

        .nav-title { 
            color: rgba(255, 255, 255, 0.7); 
            font-size: 12px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 12px; 
            padding-left: 15px; 
            white-space: nowrap; 
        }
        
        .nav-btn {
            width: 100%; padding: 14px 20px;
            background: transparent; color: rgba(255, 255, 255, 0.85);
            margin-bottom: 8px; border-radius: 10px;
            display: flex; align-items: center; gap: 15px;
            font-size: 15px; font-weight: 500; 
            white-space: nowrap; overflow: hidden;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .nav-btn.active { 
            background: rgba(255, 255, 255, 0.15); 
            color: white; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
        }

        .nav-btn .icon { 
            width: 24px; 
            font-size: 18px; 
            text-align: center; 
            flex-shrink: 0; 
        }

        .nav-btn:active {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        @media (hover: hover) {
            .nav-btn:hover {
                transform: translateX(10px);
                background: rgba(255, 255, 255, 0.2);
                color: white;
            }
        }

        .overlay {
            display: none; 
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); 
            z-index: 999; 
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active { 
            display: block; 
            opacity: 1; 
        }

        .main { 
            margin-left: 300px;
            padding: 30px;
            width: calc(100% - 300px);
            transition: all 0.3s ease;
        }
        
        .top-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            gap: 15px;
            flex-wrap: nowrap;
        }
        
        .page-title-group { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            min-width: 0; 
        }

        .page-title h2 { 
            font-size: 28px; 
            color: var(--text-dark); 
            margin: 0; 
            white-space: nowrap; 
        }

        .page-title p { 
            color: var(--text-light); 
            margin: 5px 0 0 0; 
            font-size: 14px; 
            white-space: nowrap; 
        }

        hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--light-green), transparent);
            margin: 20px 0 30px 0;
        }

        .filter-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            background: white; 
            padding: 15px 20px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow); 
        }

        .toggle-group { 
            display: flex; 
            gap: 10px; 
        }

        .filter-btn { 
            padding: 8px 24px; 
            border-radius: 20px; 
            font-weight: 600; 
            font-size: 14px; 
            text-align: center; 
            transition: all 0.2s;
        }

        .filter-btn.active { 
            background-color: var(--eco-green); 
            color: white; 
            border: 2px solid var(--eco-green); 
        }

        .filter-btn.inactive { 
            background-color: transparent; 
            color: var(--text-light); 
            border: 2px solid #e0e0e0; 
        }
        
        .mark-read-btn { 
            font-size: 14px; 
            color: var(--light-green); 
            cursor: pointer; 
            font-weight: 600; 
            background: none; 
            border: none; 
        }

        .mark-read-btn:hover { 
            text-decoration: underline; 
            color: var(--eco-green);
        }

        .notification-list { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
        }
        
        .notif-card {
            background-color: white; 
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex; 
            align-items: center; 
            gap: 20px;
            transition: transform 0.2s ease; 
            position: relative;
            border-left: 4px solid transparent;
        }

        .notif-card:hover { 
            transform: translateX(5px); 
        }
        
        .notif-card.unread { 
            background-color: #f1f8e9; 
            border-left: 4px solid var(--light-green); 
        }
        
        .notif-content { 
            flex-grow: 1; 
        }

        .notif-title { 
            font-size: 16px; 
            font-weight: 700; 
            color: var(--text-dark); 
            margin-bottom: 5px; 
        }

        .notif-desc { 
            font-size: 14px; 
            color: var(--text-light); 
            line-height: 1.4; 
        }

        .notif-time { 
            font-size: 12px; 
            color: #999; 
            margin-left: 10px; 
            white-space: nowrap; 
        }

        .unread-dot { 
            width: 10px; 
            height: 10px; 
            background-color: #ff5252; 
            border-radius: 50%; 
            position: absolute; 
            top: 20px; 
            right: 20px; 
            display: none; 
        }

        .notif-card.unread .unread-dot { 
            display: block; 
        }

        .mobile-menu-btn { 
            display: none; 
            background: none; 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: var(--text-dark); 
            padding: 0;
            margin-right: 5px;
        }

        @media (max-width: 900px) {
            .sidebar { width: 80px; }
            .sidebar-header .sidebar-logo span, .nav-title, .nav-btn span:not(.icon) { display: none; }
            
            .nav-btn { justify-content: center; padding: 15px; }
            .nav-btn .icon { margin-right: 0; }
            
            .main { margin-left: 80px; width: calc(100% - 80px); }
        }

        @media (max-width: 600px) {
            .main { margin-left: 0; width: 100%; padding: 15px; }
            
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }

            .sidebar-header .sidebar-logo span, .nav-title, .nav-btn span:not(.icon) { display: block; }
            .nav-btn { justify-content: flex-start; padding: 14px 20px; }
            .nav-btn .icon { margin-right: 0; }

            .mobile-menu-btn { display: block; }
            
            .top-bar { flex-wrap: nowrap; gap: 10px; }
            .page-title h2 { font-size: 20px; }
            .page-title p { display: none; }

            .top-right { gap: 8px; }
            .icon-btn, .avatar-btn { width: 36px; height: 36px; font-size: 14px !important; }
            .icon-btn span[style*="font-size:20px"] { font-size: 16px !important; } 
            .avatar-btn div { font-size: 16px !important; }
            
            .badge { width: 16px; height: 16px; font-size: 10px; top: -2px; right: -2px; }

            .filter-bar { flex-direction: column; gap: 15px; align-items: stretch; }
            .toggle-group { justify-content: center; width: 100%; }
            .filter-btn { flex: 1; }
            .mark-read-btn { text-align: center; padding: 10px; border: 1px dashed var(--light-green); border-radius: 8px; }
            
            .notif-card { flex-direction: column; align-items: flex-start; gap: 10px; }
            .notif-time { margin-left: 0; margin-top: 5px; display: block; }
            .unread-dot { top: 15px; right: 15px; }
        }
        
        @media (min-width: 601px) { .mobile-menu-btn { display: none; } }
    </style>
</head>

<body>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="container">
        <nav class="sidebar" id="mySidebar">
            <div class="sidebar-header">
                <a href="../WS/homepage.php" class="sidebar-logo">
                    <div class="logo-icon">🌱</div>
                    <span>GOBy</span>
                </a>
            </div>

            <div class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Navigation</div>
                    <a href="../WS/homepage.php" class="nav-btn"><span class="icon">🏠</span><span>Homepage</span></a>
                    <a href="../WS/event.php" class="nav-btn"><span class="icon">📅</span><span>Event</span></a>
                    <a href="../Aston/Chlng.php" class="nav-btn"><span class="icon">🏆</span><span>Challenge</span></a>
                    <a href="../WS/reward_page.php" class="nav-btn"><span class="icon">📊</span><span>Reward</span></a>
                    <a href="../LK/JourneyPlanner.php" class="nav-btn"><span class="icon">🗺️</span><span>Journey Planner</span></a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Community</div>
                    <a href="../LK/CarpoolList.php" class="nav-btn"><span class="icon">👥</span><span>Carpool List</span></a>
                    <a href="ranking_page.php" class="nav-btn"><span class="icon">🏆</span><span>Ranking</span></a>
                    <a href="news_page.php" class="nav-btn "><span class="icon">📰</span><span>News</span></a> 
                </div>

                <div class="nav-section">
                    <div class="nav-title">Tools</div>
                    <a href="../LK/SmartParking.php" class="nav-btn"><span class="icon">🅿️</span><span>Smart Parking</span></a>
                    <a href="../LK/Ahm.php" class="nav-btn"><span class="icon">🎯</span><span>Achievement</span></a>
                    <a href="statistics.php" class="nav-btn"><span class="icon">🎁</span><span>Statistic/Dashboard</span></a> 
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Registration</div>
                    <a href="../WS/register_event_organizer.php" class="nav-btn"><span class="icon">📝</span><span>Register Event Organizer</span></a>
                    <a href="../WS/register_driver.php" class="nav-btn"><span class="icon">🚗</span><span>Register Driver</span></a>
                </div>

                <div class="nav-section">
                    <div class="nav-title">Account</div>
                    <a href="../Aston/profile.php" class="nav-btn"><span class="icon">👤</span><span>Profile</span></a>
                    <a href="../WS/logout.php" class="nav-btn"><span class="icon">🚪</span><span>Logout</span></a>
                </div>
            </div>
        </nav>

        <main class="main">
            <div class="top-bar">
                <div class="page-title-group">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                    <div class="page-title">
                        <h2>Notifications</h2>
                        <p>Public Announcements & Updates</p>
                    </div>
                </div>
            </div>
            <hr>

            <div class="filter-bar">
                <div class="toggle-group">
                    <a href="?filter=all" class="filter-btn <?php echo ($filter == 'all') ? 'active' : 'inactive'; ?>">All</a>
                    <a href="?filter=unread" class="filter-btn <?php echo ($filter == 'unread') ? 'active' : 'inactive'; ?>">Unread</a>
                </div>
                
                <button onclick="markAllAsRead()" class="mark-read-btn">Mark all as read</button>
            </div>

            <div class="notification-list">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_array($result)) {
                        echo '<a href="mark_read.php?id='.$row['not_id'].'">';
                        
                        $card_class = ($row['is_read'] == 0) ? 'notif-card unread' : 'notif-card';
                        
                        echo '<div class="' . $card_class . '">';
                            echo '<div class="notif-content">';
                                echo '<div class="notif-title">' . $row['title'] . '</div>';
                                echo '<div class="notif-desc">' . $row['message'] . '</div>';
                                echo '<div class="notif-time">📅 ' . $row['date'] . '</div>';
                            echo '</div>';
                            echo '<div class="unread-dot"></div>';
                        echo '</div>'; 
                        
                        echo '</a>';
                    }
                } else {
                    echo "<p style='text-align:center; color:gray;'>No notifications found.</p>";
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function markAllAsRead() {
            if(confirm("Are you sure you want to mark ALL notifications as read?")) {
                window.location.href = "mark_all_read.php";
            }
        }
    </script>

</body>
</html>