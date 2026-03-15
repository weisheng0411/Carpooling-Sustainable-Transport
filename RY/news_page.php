<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}


$search = "";
if (isset($_GET['search'])){
    $search = $_GET['search'];
    $sql = "SELECT * FROM news WHERE title LIKE '%$search%'";
}
else{
    $sql = "SELECT * FROM news ORDER BY news_id DESC";
}

$result = mysqli_query($conn,$sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Page</title>
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
            left: 0; top: 0;
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
            margin-bottom: 
            25px; 
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

        hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--light-green), transparent);
            margin: 20px 0 30px 0;
        }

        .filterbar { 
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            border-left: 4px solid var(--light-green);
            flex-wrap: wrap; 
        }
        
        .filterbar input {
            flex-grow: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            min-width: 200px; 
        }

        .filterbar input:focus { 
            border-color: var(--light-green); 
        }

        .filterbar button{
            background-color: var(--eco-green);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .filterbar button:hover{
            background-color: var(--light-green); 
        }

        .refreshBtn{
            color:white;
            background-color:#2e7d32;
            padding:10px 20px;
            border:none;
            border-radius:8px;
            font-weight:600;
            cursor:pointer;
        }

        .content { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }

        .news-box {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--eco-green);
            transition: transform 0.2s;
        }

        .news-box:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }

        .news-box h3 { 
            margin-top: 0; 
            color: var(--text-dark); 
            font-size: 14px; color: #999; 
            font-weight: normal; 
            margin-bottom: 5px; 
        }

        .news-title-text { 
            font-size: 20px; 
            font-weight: bold; 
            color: var(--text-dark); 
            margin: 0 0 15px 0; 
            word-break: break-word; 
        }

        .news-box p { 
            color: var(--text-light); 
            line-height: 1.6; 
            margin: 10px 0; 
            word-break: break-word; 
        }

        .news-date { 
            color: var(--text-medium); 
            font-size: 13px; 
            margin-top: 15px; 
            display: block; 
            font-weight: 600; 
        }

        .news-image-container {
            margin: 15px 0;
            border-radius: 8px;
            overflow: hidden;
            max-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            width: 100%;
        }

        .news-image {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            display: block;
        }

        .image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px;
            text-align: center;
            width: 100%;
            height: 200px;
        }

        .image-placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #ccc;
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
            
            .news-image-container {
                max-height: 300px;
            }
        }

        @media (max-width: 600px) {
            .main { margin-left: 0; width: 100%; padding: 15px; }
            
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }

            .sidebar-header .sidebar-logo span, .nav-title, .nav-btn span:not(.icon) { display: block; }
            .nav-btn { justify-content: flex-start; padding: 14px 20px; }
            .nav-btn .icon { margin-right: 0; }

            .mobile-menu-btn { display: block; }

            .filterbar { flex-direction: column; gap: 10px; padding: 15px; }
            .filterbar button { width: 100%; }
            
            .news-image-container {
                max-height: 250px;
            }
            
            .news-title-text {
                font-size: 18px;
            }
        }
        
        @media (min-width: 601px) { .mobile-menu-btn { display: none; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="#" class="nav-btn active"><span class="icon">📰</span><span>News</span></a> 
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
                        <h2>News</h2>
                    </div>
                </div>
            </div>

            <hr>

            <form method="GET">
                <div class="filterbar">
                    <input type="text" name="search" placeholder="Search news..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                    <button class='refreshBtn' onclick="location.href='news_page.php'">Refresh</button>
                </div>
            </form>

            <div class="content">
                <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_array($result)){
                            echo '<div class="news-box">';
                            echo '<div class="news-title-text">'. htmlspecialchars($row['title']). '</div>';
                            
                            // 显示新闻内容
                            echo '<p>'. htmlspecialchars($row['content']). '</p>';
                            
                            // 检查是否有图片并显示
                            if (!empty($row['image'])) {
                                echo '<div class="news-image-container">';
                                echo '<img src="/ASS-WDD/uploads/' . htmlspecialchars($row['image']) . '" class="news-image" alt="news image" onerror="this.style.display=\'none\'; this.parentElement.innerHTML=\'<div class=\\\'image-placeholder\\\'><i class=\\\'fas fa-image\\\'></i><p>Image not available</p></div>\';">';
                                echo '</div>';
                            } else {
                                // 如果没有图片，显示占位符
                                echo '<div class="news-image-container">';
                                echo '<div class="image-placeholder">';
                                echo '<i class="fas fa-image"></i>';
                                echo '<p>No image available</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '<span class="news-date">📅 '. htmlspecialchars($row['date']). '</span>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="news-box">';
                        echo '<div class="news-title-text">No News Found</div>';
                        echo '<p>There are currently no news articles available.</p>';
                        echo '</div>';
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
        
        // 图片加载失败处理
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.news-image');
            images.forEach(img => {
                img.onerror = function() {
                    this.parentElement.innerHTML = `
                        <div class="image-placeholder">
                            <i class="fas fa-image"></i>
                            <p>Image not found</p>
                        </div>
                    `;
                };
            });
        });
    </script>

</body>
</html>