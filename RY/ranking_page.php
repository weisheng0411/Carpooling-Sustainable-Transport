<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$sql = "SELECT * FROM user_acc ORDER BY summary_point DESC";
$result = mysqli_query($conn, $sql);

$rank1 = null;
$rank2 = null;
$rank3 = null;
$others = []; 

$counter = 1;
while ($row = mysqli_fetch_assoc($result)) {
    if ($counter == 1) {
        $rank1 = $row;
    } else if ($counter == 2) {
        $rank2 = $row;
    } else if ($counter == 3) {
        $rank3 = $row;
    } else {
        $others[] = $row; 
    }
    $counter++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking</title>
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
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
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

        .podium-container {
            background-image: linear-gradient(to bottom, #e8f5e9, #c8e6c9);
            padding: 40px 20px 20px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            height: 380px;
            gap: 20px;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
            position: relative;
        }

        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 30%;
            max-width: 150px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .avatar-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #fff;
            border: 4px solid #fff;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .crown {
            position: absolute;
            top: -25px;
            font-size: 24px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        .rank-name {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-size: 14px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;
        }
        
        .rank-points {
            font-size: 12px;
            color: var(--text-light);
            background: white;
            padding: 4px 10px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .step {
            width: 100%;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            font-weight: 900;
            color: rgba(255,255,255,0.6);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .rank-2 .step { 
            height: 120px; 
            background: linear-gradient(135deg, #e0e0e0, #bdbdbd); 
        }

        .rank-2 .avatar-circle { 
            border-color: var(--silver); 
        }

        .rank-2 { order: 1; } 

        .rank-1 .step { 
            height: 160px; 
            background: linear-gradient(135deg, #ffd700, #ffca28); 
        }

        .rank-1 .avatar-circle { 
            width: 90px; 
            height: 90px; 
            font-size: 1.5em; 
            border-color: var(--gold); 
        }

        .rank-1 { 
            order: 2; 
            z-index: 3; 
        }

        .rank-3 .step { 
            height: 90px; 
            background: linear-gradient(135deg, #cd7f32, #a16223); 
        }

        .rank-3 .avatar-circle { 
            border-color: var(--bronze); 
        }

        .rank-3 { 
            order: 3; 
        }

        .all-rankings {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .all-rankings h3 {
            margin-left: 10px;
            color: var(--text-dark);
        }

        .ranking-list-header {
            display: flex;
            padding: 15px 25px;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ranking-row {
            display: flex;
            align-items: center;
            background-color: white;
            margin-bottom: 12px;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: transform 0.2s;
        }

        .ranking-row:hover {
            transform: scale(1.01);
            background-color: #f9fff9;
        }

        .col-rank { 
            flex: 0.5; 
            font-weight: 800; 
            color: var(--text-medium); 
            font-size: 18px; 
            min-width: 30px; 
        }

        .col-name { 
            flex: 2; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-weight: 600; 
            overflow: hidden; 
        }

        .col-point { 
            flex: 1; 
            text-align: center; 
            color: var(--text-dark); 
            font-weight: bold; 
            white-space: nowrap; 
        }
        
        .col-badge { 
            flex: 1; 
            text-align: right; 
        }

        .mini-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .achievement-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-gold { 
            background-color: #fff8e1; 
            color: #fbc02d; 
            border: 1px solid #fbc02d; 
        }

        .badge-silver { 
            background-color: #f5f5f5; 
            color: #757575; 
            border: 1px solid #9e9e9e; 
        }

        .badge-bronze { 
            background-color: #fff3e0; 
            color: #e64a19; 
            border: 1px solid #ffab91; 
        }

        .mobile-menu-btn { 
            display: none; 
            background: none; 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: var(--text-dark); 
            padding: 0; margin-right: 5px;
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
 
            .podium-container { height: 320px; gap: 10px; padding: 20px 10px; }
            .rank-1 .step { height: 120px; }
            .rank-2 .step { height: 90px; }
            .rank-3 .step { height: 60px; }
            .avatar-circle { width: 50px; height: 50px; }
            .rank-1 .avatar-circle { width: 65px; height: 65px; }
            .rank-name { font-size: 12px; }
            
            .ranking-list-header { display: none; } 
            .ranking-row { flex-wrap: wrap; gap: 10px; padding: 10px; }
            .col-rank { flex: 0 0 30px; }
            .col-name { flex: 1 0 50%; }
            .col-point { flex: 1 0 40%; text-align: right; }
            .col-badge { flex: 0 0 100%; text-align: right; margin-top: -5px; }
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
                    <a href="#" class="nav-btn active"><span class="icon">🏆</span><span>Ranking</span></a>
                    <a href="news_page" class="nav-btn"><span class="icon">📰</span><span>News</span></a> 
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
                        <h2>Ranking</h2>
                        <p>See who's leading the green movement</p>
                    </div>
                </div>
            </div>

            <hr>

            <div class="podium-container">
                <div class="podium-item rank-2">
                    <?php if($rank2): ?>
                    <div class="avatar-circle">
                        <img src="/ASS-WDD/<?= htmlspecialchars($rank2['photo_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="rank-name"><?php echo $rank2['name']; ?></div>
                    <div class="rank-points"><?php echo $rank2['summary_point']; ?> pts</div>
                    <?php endif; ?>
                    <div class="step">2</div>
                </div>

                <div class="podium-item rank-1">
                    <div class="crown">👑</div>
                    <?php if($rank1): ?>
                    <div class="avatar-circle">
                        <img src="/ASS-WDD/<?= htmlspecialchars($rank1['photo_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="rank-name"><?php echo $rank1['name']; ?></div>
                    <div class="rank-points"><?php echo $rank1['summary_point']; ?> pts</div>
                    <?php endif; ?>
                    <div class="step">1</div>
                </div>

                <div class="podium-item rank-3">
                    <?php if($rank3): ?>
                    <div class="avatar-circle">
                        <img src="/ASS-WDD/<?= htmlspecialchars($rank3['photo_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="rank-name"><?php echo $rank3['name']; ?></div>
                    <div class="rank-points"><?php echo $rank3['summary_point']; ?> pts</div>
                    <?php endif; ?>
                    <div class="step">3</div>
                </div>
            </div>

            <div class="all-rankings">
                <h3>Leaderboard</h3>
                
                <div class="ranking-list-header">
                    <span class="col-rank">#</span>
                    <span class="col-name">User</span>
                    <span class="col-point">Points</span>
                    <span class="col-badge">Badge</span>
                </div>

                <?php 
                $i = 0;
                while(isset($others[$i])) {
                    $user = $others[$i];
                    $current_rank = $i + 4; 
                    ?>
                    <div class="ranking-row">
                        <span class="col-rank"><?php echo $current_rank; ?></span>
                        <span class="col-name">
                            <div class="mini-avatar">
                                <?php if($user['photo_url']): ?>
                                    <img src="/ASS-WDD/<?= htmlspecialchars($user['photo_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <?php echo substr($user['name'], 0, 2); ?>
                                <?php endif; ?>
                            </div>
                            <?php echo $user['name']; ?>
                        </span>
                        <span class="col-point"><?php echo $user['summary_point']; ?></span>
                        <span class="col-badge">
                            <?php if($user['summary_point'] > 1800): ?>
                                <span class="achievement-badge badge-gold">Gold</span>
                            <?php elseif($user['summary_point'] > 1500): ?>
                                <span class="achievement-badge badge-silver">Silver</span>
                            <?php else: ?>
                                <span class="achievement-badge badge-bronze">Bronze</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php
                    $i++;
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
    </script>

</body>
</html>