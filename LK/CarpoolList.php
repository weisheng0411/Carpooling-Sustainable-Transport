<?php
session_start();
include("conn.php"); // This creates $con variable

/* ---------- FIX: Map $con to $conn ---------- */
// Your conn.php creates $con, but your code uses $conn
// So we need to map them
if (isset($con)) {
    $conn = $con; // Now $conn is the mysqli connection object
} else {
    // If $con doesn't exist, something is wrong with conn.php
    die("Database connection failed. Please check conn.php file.");
}

/* ---------- 1. 登录检查 ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* ---------- 2. 检查是否为通过审核的司机 ---------- */
// Now $conn should be a mysqli object, not an integer
$sql_driver = "SELECT Status FROM Driver WHERE user_id = ?";
$stmt = $conn->prepare($sql_driver); // This should work now
// ... rest of your existing code
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_driver = $stmt->get_result();

$is_pass_driver = false;

if ($result_driver->num_rows > 0) {
    $driver = $result_driver->fetch_assoc();
    if ($driver['Status'] === 'pass') {
        $is_pass_driver = true;
    }
}

/* ---------- 3. 处理 Create Ride 表单 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_pass_driver) {

    $from_place     = trim($_POST['from_place'] ?? '');
    $to_place       = trim($_POST['to_place'] ?? '');
    $date           = $_POST['date'] ?? '';
    $time           = $_POST['time'] ?? '';
    $seats          = (int)($_POST['seats'] ?? 0);
    $boarding_point = trim($_POST['boarding_point'] ?? '');

    if (
        $from_place === '' ||
        $to_place === '' ||
        $date === '' ||
        $time === '' ||
        $seats <= 0 ||
        $boarding_point === ''
    ) {
        die("Error: All fields are required.");
    }

    $sql_insert = "
        INSERT INTO carpool_list
        (driver_id, from_place, to_place, date, time, seats, boarding_point, status_open_close)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
    ";

    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param(
        "issssis",
        $user_id,
        $from_place,
        $to_place,
        $date,
        $time,
        $seats,
        $boarding_point
    );

    if ($stmt->execute()) {
        header("Location: carpool_list.php?success=1");
        exit();
    } else {
        die("Insert failed: " . $stmt->error);
    }
}

/* ---------- 4. 读取 Carpool List ---------- */
$search = trim($_GET['search'] ?? '');

$sql_list = "SELECT 
                cl.*,
                ua.name,
                ua.photo_url,
                d.Car_Model,
                AVG(f.rating) AS avg_rating,
                COUNT(f.feedback_id) AS rating_count
            FROM carpool_list cl
            JOIN Driver d ON cl.driver_id = d.driver_id
            JOIN user_acc ua ON d.user_id = ua.user_id
            LEFT JOIN feedback f ON f.cl_id = cl.cl_id
            WHERE cl.status_open_close = 'open'
            ";


$search = trim($_GET['search'] ?? '');


if ($search !== '') {
    $sql_list .= " AND (cl.from_place LIKE ? OR cl.to_place LIKE ?)";
}
$sql_list .= " GROUP BY cl.cl_id";
$stmt = $conn->prepare($sql_list);

if ($search !== '') {
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
}

$stmt->execute();
$result_list = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpool List</title>
    <style>
        * { box-sizing: border-box; }

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            --border-radius: 12px;
            --danger: #d32f2f;
            --blue: #1976d2; 
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
            font-size: 20px; flex-shrink: 0; 
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
            color: white; font-weight: 600; 
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

        .filter-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap; 
        }
        
        .search-wrapper { 
            flex: 1; 
            position: relative; 
            min-width: 200px; 
        }

        .search-wrapper input {
            width: 100%; 
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd; 
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border 0.2s;
        }
        .search-wrapper input:focus { 
            border-color: var(--eco-green); 
        }

        .search-icon { 
            position: absolute; 
            left: 12px; top: 50%; 
            transform: translateY(-50%); 
            color: #999; 
        }

        .btn {
            padding: 12px 25px; 
            border-radius: 8px; 
            border: none; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.2s; 
            white-space: nowrap;
        }

        .btn-filter { 
            background: #f5f5f5; 
            color: #555; 
            border: 1px solid #ddd;
        }

        .btn-filter:hover { 
            background: #e0e0e0; 
        }
        
        .btn-create { 
            background: var(--eco-green); 
            color: white; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }

        .btn-create:hover { 
            background: var(--light-green); 
            transform: translateY(-2px); 
            box-shadow: 0 4px 8px rgba(46,125,50,0.2);
        }

        .content-grid {
            display: flex;
            gap: 25px;
            align-items: flex-start;
        }
        
        .list-section { 
            flex: 3; 
            width: 100%; 
        }

        .stats-section { 
            flex: 1; 
            min-width: 250px; 
        }

        .carpool-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--eco-green);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .carpool-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 6px 15px rgba(0,0,0,0.08); 
        }

        .driver-info { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            width: 25%; 
            border-right: 1px solid #eee; 
            padding-right: 15px; 
        }

        .driver-photo { 
            width: 50px; 
            height: 50px; 
            background: #eee; 
            border-radius: 50%; 
            background-size: cover; 
            background-position: center; 
            flex-shrink: 0; 
        }

        .driver-text { 
            overflow: hidden; 
        }

        .driver-text h4 { 
            margin: 0 0 5px 0; 
            font-size: 16px; 
            color: var(--text-dark); 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }

        .driver-text p { 
            margin: 0; 
            font-size: 13px; 
            color: #888; 
            display: flex; 
            align-items: center; 
            gap: 5px; 
        }

        .rating-star { 
            color: #fbc02d; 
        }

        .route-info { 
            flex: 1; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 20px; 
        }

        .location-group { 
            display: flex; 
            flex-direction: column; 
            gap: 5px; 
            max-width: 40%; 
        }

        .loc-label { 
            font-size: 11px; 
            text-transform: uppercase; 
            color: #999; 
            letter-spacing: 0.5px; 
        }

        .loc-name { 
            font-weight: 700; 
            font-size: 16px; 
            color: var(--text-dark); 
            word-wrap: break-word; 
        }
        
        .route-arrow { 
            color: var(--light-green); 
            font-size: 20px; 
            padding: 0 15px; 
        }

        .time-badge { 
            background: #e8f5e9; 
            color: var(--eco-green); 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 13px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px; 
            margin-top: 5px; 
            white-space: nowrap;
        }

        .action-group { 
            width: 20%; 
            display: flex; 
            flex-direction: column; 
            align-items: flex-end; 
            gap: 10px; 
            min-width: 100px; 
        }

        .seat-count { 
            font-size: 13px; 
            color: #666; 
            font-weight: 500; 
        }

        .seat-highlight { 
            color: var(--eco-green); 
            font-weight: 700; 
        }
        
        .btn-join {
            width: 100%; 
            padding: 10px;
            background: white; 
            border: 1px solid var(--eco-green); 
            color: var(--eco-green);
            border-radius: 6px; font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-join:hover { 
            background: var(--eco-green); 
            color: white; 
        }

        .stat-card-side {
            background: white; 
            padding: 20px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .stat-card-side h3 { 
            margin: 0 0 15px 0; 
            font-size: 16px; 
            color: var(--text-dark); 
        }

        .mini-stat { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 12px; 
            font-size: 14px; 
        }

        .mini-stat span { 
            color: #666; 
        }

        .mini-stat strong { 
            color: var(--text-dark); 
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

        @media (max-width: 991px) {
            .content-grid { flex-direction: column; }
            .stats-section { width: 100%; display: flex; gap: 20px; }
            .stat-card-side { flex: 1; }
            
            .carpool-card { flex-direction: column; align-items: flex-start; gap: 15px; border-left: none; border-top: 5px solid var(--eco-green); }
            .driver-info { width: 100%; border-right: none; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .route-info { width: 100%; padding: 0; margin-bottom: 10px; }
            .action-group { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; }
            .btn-join { width: auto; padding: 10px 30px; }
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
            
            .icon-btn, .avatar-btn { width: 36px; height: 36px; font-size: 14px !important; }
            .icon-btn span[style*="font-size:20px"] { font-size: 16px !important; }
            .avatar-btn div { font-size: 16px !important; }
            
            .filter-card { gap: 10px; padding: 15px; }
            .search-wrapper { width: 100%; }
            .btn { width: 100%; justify-content: center; }
            
            .stats-section { flex-direction: column; gap: 10px; }
            
            .route-info { flex-direction: column; align-items: flex-start; gap: 15px; position: relative; }
            .route-arrow { display: none; }
            .route-info::after { content: "↓"; display: block; color: var(--light-green); font-weight:bold; margin-left: 10px; font-size: 20px; position: absolute; top: 45%; left: 0; }
            .location-group { max-width: 100%; width: 100%; }
            .location-group[style*="text-align: right"] { text-align: left !important; align-items: flex-start !important; padding-left: 25px; }
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
                    <a href="JourneyPlanner.php" class="nav-btn"><span class="icon">🗺️</span><span>Journey Planner</span></a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Community</div>
                    <a href="#" class="nav-btn  active"><span class="icon">👥</span><span>Carpool List</span></a>
                    <a href="../RY/ranking_page.php" class="nav-btn"><span class="icon">🏆</span><span>Ranking</span></a>
                    <a href="../RY/news_page" class="nav-btn"><span class="icon">📰</span><span>News</span></a> 
                </div>

                <div class="nav-section">
                    <div class="nav-title">Tools</div>
                    <a href="SmartParking.php" class="nav-btn"><span class="icon">🅿️</span><span>Smart Parking</span></a>
                    <a href="Ahm.php" class="nav-btn"><span class="icon">🎯</span><span>Achievement</span></a>
                    <a href="../RY/statistics.php" class="nav-btn"><span class="icon">🎁</span><span>Statistic/Dashboard</span></a> 
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Account</div>
                    <a href="../WS/register_event_organizer.php" class="nav-btn"><span class="icon">📝</span><span>Register Event Organizer</span></a>
                    <a href="../WS/register_driver.php" class="nav-btn"><span class="icon">🚗</span><span>Register Driver</span></a>
                </div>

                <div class="nav-section">
                    <div class="nav-title">Account</div>
                    <a href="../Aston/profile.php" class="nav-btn">
                        <span class="icon">👤</span>
                        <span>Profile</span>
                    </a>
                    <a href="../WS/logout.php" class="nav-btn">
                        <span class="icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <main class="main">
            <div class="top-bar">
                <div class="page-title-group">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                    <div class="page-title">
                        <h2>Carpool List</h2>
                        <p>Find a ride or share your journey</p>
                    </div>
                </div>
            </div>

            <hr>

            <div class="filter-card">
                <form method="GET" style="display:flex; width:100%; flex-wrap:wrap; gap:10px;"> 
                    <div class="search-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" 
                            name="search" 
                            value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>" 
                            placeholder="Search place...">
                    </div>
                    <button class="btn btn-filter" type="submit">Filter</button>
                </form>
                
               <?php if ($is_pass_driver): ?>
                    <button class="btn btn-create" onclick="window.location.href='CreateCarpoolList.php'">
                        ➕ Create Ride
                    </button>
                <?php endif; ?>

            </div>

            <div class="content-grid">
                
                <div class="list-section">
                    <?php
                        if ($result_list && mysqli_num_rows($result_list) > 0) {
                            while ($row = mysqli_fetch_assoc($result_list)) {

                                $photo = !empty($row['photo_url']) 
                                    ? '/ASS-WDD/' . $row['photo_url'] 
                                    : '';

                                $driverName = $row['name'] ?? 'Driver';
                                $rating = ($row['avg_rating'] !== null)
                                    ? number_format($row['avg_rating'], 1)
                                    : 'N/A';

                                $rating_count = $row['rating_count'] ?? 0;
                        ?>
                    <div class="carpool-card">
                        <div class="driver-info">
                            <div class="driver-photo" style="background-image: url('<?php echo $photo; ?>');"></div>
                            <div class="driver-text">
                                <h4><?php echo $driverName; ?></h4>
                                <p>
                                    <span class="rating-star">★</span> <?php echo $rating; ?>/5.0
                                    <span class=""></span> <?php echo $rating_count; ?> people
                                </p>
                                <p style="font-size:12px; margin-top:2px;"><?php echo $row['Car_Model']; ?></p>
                            </div>
                        </div>

                        <div class="route-info">
                            <div class="location-group">
                                <span class="loc-label">Origin</span>
                                <span class="loc-name"><?php echo $row['from_place']; ?></span>
                                <div class="time-badge">
                                    <span>📅 <?php echo $row['date']; ?></span>
                                </div>
                            </div>

                            <div class="route-arrow">➜</div>

                            <div class="location-group" style="text-align: right; align-items: flex-end;">
                                <span class="loc-label">Destination</span>
                                <span class="loc-name"><?php echo $row['to_place']; ?></span>
                                <div class="time-badge">
                                    <span>⏰ <?php echo $row['time']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="action-group">
                            <div class="seat-count">
                                <span class="seat-highlight"><?php echo $row['seats']; ?></span> seats left
                            </div>
                            <button class="btn-join" onclick="window.location.href='RidesDetail.php?id=<?php echo $row['cl_id']; ?>'">
                                Join Ride
                            </button>
                        </div>
                    </div>
                    <?php 
                        } 
                    } else {
                        echo "<div style='text-align:center; padding:40px; color:#999;'>No carpools available at the moment.</div>";
                    }
                    ?>
                </div>

                <div class="stats-section">
                    <div class="stat-card-side">
                        <h3>Quick Statistics</h3>
                        <div class="mini-stat">
                            <span>Total Rides</span>
                            <strong>120</strong>
                        </div>
                        <div class="mini-stat">
                            <span>Active Drivers</span>
                            <strong>15</strong>
                        </div>
                        <div class="mini-stat">
                            <span>CO2 Saved Today</span>
                            <strong style="color:var(--eco-green)">24 kg</strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-side" style="background: #e8f5e9; border:1px solid #c8e6c9;">
                        <h3 style="color:var(--eco-green);">Eco Tip 💡</h3>
                        <p style="font-size:14px; color:#555; line-height:1.5;">Carpooling just once a week saves 20% of your commute emissions!</p>
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