<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $ride_id = $_GET['id']; 
} else {
    $ride_id = 1; 
}

$sql = "SELECT * FROM carpool_list 
        JOIN user_acc ON carpool_list.driver_id = user_acc.user_id 
        JOIN driver ON user_acc.user_id = driver.user_id 
        WHERE carpool_list.cl_id = '$ride_id'";

$result = mysqli_query($con, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Ride not found.");
}

$driver_id = $data['driver_id']; 
$stats_sql = "SELECT 
                COUNT(*) as total_trips, 
                (SELECT AVG(rating) FROM feedback 
                 JOIN carpool_list ON feedback.cl_id = carpool_list.cl_id 
                 WHERE carpool_list.driver_id = '$driver_id') as avg_rating
              FROM carpool_list 
              WHERE driver_id = '$driver_id'";
              
$stats_result = mysqli_query($con, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$rating = $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : "5.0";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rides Details</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; } /* 修复：重置所有元素的margin和padding */

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
            --border-radius: 16px;
        }

        body {
            margin: 0; /* 修复：确保body没有margin */
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            min-height: 100vh; /* 修复：确保body至少占满视口高度 */
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
            gap: 12px; 
            color: white; 
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
            width: 100%; 
            padding: 14px 20px;
            background: transparent; 
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 8px; 
            border-radius: 10px;
            display: flex; 
            align-items: center; 
            gap: 15px;
            font-size: 15px; 
            font-weight: 500; 
            white-space: nowrap; 
            overflow: hidden;
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

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .overlay {
            display: none; 
            position: fixed;
            top: 0; 
            left: 0;
            width: 100%; 
            height: 100%;
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
            margin-left: 280px; 
            padding: 30px; 
            width: calc(100% - 280px); 
            transition: all 0.3s ease;
            min-height: 100vh; /* 修复：确保main填满剩余空间 */
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
        
        .content-grid {
            display: flex; 
            gap: 30px; 
            align-items: flex-start;
        }
        
        .main-info { 
            flex: 3; 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
            width: 100%; 
        }

        .action-sidebar { 
            flex: 1; 
            position: sticky; 
            top: 20px; 
            min-width: 280px; 
        }

        .detail-card {
            background: white; 
            border-radius: var(--border-radius); 
            padding: 25px; 
            box-shadow: var(--shadow);
            position: relative;
        }

        .section-title { 
            font-size: 18px; 
            font-weight: 700; 
            color: var(--text-dark); 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .journey-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 20px; 
            margin-bottom: 25px; 
        }

        .info-box label { 
            font-size: 12px; 
            text-transform: uppercase; 
            color: #888; 
            letter-spacing: 0.5px; 
            display: block;
            margin-bottom: 5px; 
        }

        .info-box span { 
            font-size: 16px; 
            font-weight: 600; 
            color: var(--text-dark); 
            word-break: break-word;
        }

        .driver-wrapper { 
            display: flex; 
            gap: 20px; 
            align-items: center; 
        }

        .driver-img { 
            width: 100px;
            height: 100px;
            border-radius: 50%; 
            background-color: #eee;
            background-size: cover; 
            background-position: center;
            border: 3px solid #f1f8e9; 
            flex-shrink: 0; 
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .driver-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .driver-bio { flex: 1; }

        .driver-bio h3 { 
            margin: 0 0 5px 0; 
            font-size: 20px; 
        }
        
        .rating-badge { 
            background: #fff3e0; 
            color: #f57c00; 
            padding: 4px 8px; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: 600; 
            display: inline-block; 
            margin-bottom: 8px; 
        }

        .car-badge { 
            width: 40%;
            background: #f1f8e9; 
            padding: 15px; 
            border-radius: 12px; 
            text-align: center; 
            min-width: 140px; 
            font-size: 18px;
        }

        .car-badge strong { 
            display: block; 
            color: var(--text-dark); 
            margin-bottom: 4px; 
        }

        .car-badge span { 
            font-size: 13px; 
            color: #666; 
        }

        .join-card { 
            text-align: center; 
            background: white; 
            border-top: 5px solid var(--light-green); 
        }

        .join-card h3 { 
            margin-top: 0; 
        }

        .btn-join,.btn-cancel { 
            width: 100%; 
            padding: 14px; 
            background: var(--eco-green); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.2s; 
            box-shadow: 0 4px 10px rgba(46,125,50,0.3);
            margin: 15px 0;
        }

        .btn-cancel{
            background-color: #f1f3f4;
            color: var(--text-dark);
            border: 1px solid #dadce0;
        }

        .btn-cancel:hover{
            background-color: #e8eaed;
            transform: translateY(-2px);
        }

        .btn-join:hover { 
            background: var(--light-green); 
            transform: translateY(-2px); 
        }

        .bonus-tag { 
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            color: #f57c00; 
            font-weight: 600; 
            font-size: 14px; 
            background: #fff3e0; 
            padding: 5px 10px; 
            border-radius: 20px; 
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
            
            .content-grid { flex-direction: column; } 
            .action-sidebar { width: 100%; position: static; margin-top: 20px; }
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

            .content-grid { flex-direction: column; gap: 20px; } 
            .action-sidebar { width: 100%; position: static; order: -1; }
            
            .journey-grid { grid-template-columns: 1fr; }
            
            .driver-wrapper { flex-direction: column; text-align: center; }
            .btn-group { justify-content: center; }
            .car-badge { width: 100%; }
        }
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
                    <a href="JourneyPlanner.php" class="nav-btn"><span class="icon">🗺️</span><span>Journey Planner</span></a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Community</div>
                    <a href="CarpoolList.php" class="nav-btn active"><span class="icon">👥</span><span>Carpool List</span></a>
                    <a href="../RY/ranking_page.php" class="nav-btn"><span class="icon">🏆</span><span>Ranking</span></a>
                    <a href="../RY/news_page.php" class="nav-btn"><span class="icon">📰</span><span>News</span></a> 
                </div>

                <div class="nav-section">
                    <div class="nav-title">Tools</div>
                    <a href="SmartParking.php" class="nav-btn"><span class="icon">🅿️</span><span>Smart Parking</span></a>
                    <a href="Ahm.php" class="nav-btn"><span class="icon">🎯</span><span>Achievement</span></a>
                    <a href="../RY/statistics.php" class="nav-btn"><span class="icon">🎁</span><span>Statistic/Dashboard</span></a> 
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
                        <h2>Ride Details</h2>
                        <p>View journey information and driver profile</p>
                    </div>
                </div>
            </div>

            <hr>

            <div class="content-grid">
                <div class="main-info">
                    <div class="detail-card">
                        <div class="section-title">📍 Journey Information</div>
                        <div class="journey-grid">
                            <div class="info-box">
                                <label>From</label>
                                <span><?php echo $data['from_place']; ?></span>
                            </div>
                            <div class="info-box">
                                <label>To</label>
                                <span><?php echo $data['to_place']; ?></span>
                            </div>
                            <div class="info-box">
                                <label>Date & Time</label>
                                <span><?php echo $data['date']; ?> at <?php echo $data['time']; ?></span>
                            </div>
                            <div class="info-box">
                                <label>Boarding Point</label>
                                <span><?php echo $data['bording_point']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="section-title">🚘 Driver Profile</div>
                        <div class="driver-wrapper">
                            <div class="driver-img">
                                <img src="/ASS-WDD/<?php echo htmlspecialchars($data['photo_url']) ?>" 
                                     alt="driver photo">
                            </div>

                            <div class="driver-bio">
                                <h3><?php echo $data['name']; ?></h3>
                                <div class="rating-badge">★ <?php echo $rating; ?> Rating</div>
                                <div style="font-size:13px; color:#666; margin-bottom:5px;">
                                    Completed <strong><?php echo $stats['total_trips']; ?></strong> trips
                                </div>
                            </div>

                            <div class="car-badge">
                                <strong><?php echo $data['Plate_Number']; ?></strong>
                                <span><?php echo $data['Car_Color']; ?></span><br>
                                <span style="font-size:12px; color:#999;"><?php echo $data['Car_Model']; ?></span>
                            </div>
                        </div>
                    </div>

                <div class="action-sidebar">
                    <div class="detail-card join-card">
                        <h3>Ready to go?</h3>
                        <p style="color:#666; font-size:14px; margin-bottom: 20px;">
                            Join this carpool to save CO2 and earn points!
                        </p>
                        <button class="btn-cancel" onclick="window.location.href='CarpoolList.php'">Back</button>              
                        <a class="btn-join" style="display:block;text-align:center;"
                        href="../Aston/navigation.php?cl_id=<?php echo (int)$data['cl_id']; ?>">
                        Confirm & Join Ride
                        </a>
                        <div class="bonus-tag">
                            <span>🎁 +20 Bonus Points</span>
                        </div>
                    </div>
                </div>
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
    </script>
</body>
</html>