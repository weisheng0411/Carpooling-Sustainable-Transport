<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

$total_points = 0;
$total_carbon = 0.00;
$bar_labels = []; $bar_data = [];
$pie_labels = []; $pie_data = [];

$sql = "SELECT SUM(Points_earned) as sum_points, SUM(`Energy_saved （CO2）`) as sum_carbon 
        FROM travel_log 
        WHERE user_id = '$current_user_id'";
$result = mysqli_query($conn, $sql);
if ($row_stats = mysqli_fetch_assoc($result)) {
    $total_points = $row_stats['sum_points'] ?? 0;
    $total_carbon = number_format((float)($row_stats['sum_carbon'] ?? 0), 2);
}

$sql_bar = "SELECT DATE_FORMAT(`Date`, '%Y-%m') as month, SUM(Points_earned) as monthly_points 
            FROM travel_log 
            WHERE User_ID = '$current_user_id' 
            GROUP BY month 
            ORDER BY month DESC 
            LIMIT 6";
$result_bar = mysqli_query($conn, $sql_bar);
$temp_labels = []; $temp_data = [];
if ($result_bar) {
    while ($row = mysqli_fetch_assoc($result_bar)) {
        $temp_labels[] = $row['month']; 
        $temp_data[] = $row['monthly_points'];
    }
}
$bar_labels = array_reverse($temp_labels);
$bar_data = array_reverse($temp_data);

$sql_pie = "SELECT CL_ID, SUM(Points_earned) as id_total 
            FROM travel_log 
            WHERE User_ID = '$current_user_id' 
            GROUP BY CL_ID 
            ORDER BY id_total DESC";
$result_pie = mysqli_query($conn, $sql_pie);
if ($result_pie) {
    $count = 0;
    $others_total = 0;
    while ($row = mysqli_fetch_assoc($result_pie)) {
        if ($count < 4) {
            $pie_labels[] = "Log ID #" . $row['CL_ID'];
            $pie_data[] = $row['id_total'];
        } else {
            $others_total += $row['id_total'];
        }
        $count++;
    }
    if ($others_total > 0) {
        $pie_labels[] = "Others";
        $pie_data[] = $others_total;
    }
}
if (empty($pie_data)) {
    $pie_labels = ["No Data"];
    $pie_data = [1];
}

$json_bar_labels = json_encode($bar_labels);
$json_bar_data = json_encode($bar_data);
$json_pie_labels = json_encode($pie_labels);
$json_pie_data = json_encode($pie_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --danger: #d32f2f;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        a { text-decoration: none; }

        .container { display: flex; min-height: 100vh; position: relative; overflow-x: hidden; }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--eco-green) 0%, #1b5e20 100%);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex; flex-direction: column;
            overflow-y: auto; z-index: 1000;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease; 
        }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; }
        .sidebar-logo { display: flex; align-items: center; justify-content: center; gap: 12px; color: white; font-size: 22px; font-weight: 700; }
        .logo-icon { width: 36px; height: 36px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--eco-green); font-size: 20px; flex-shrink: 0; }
        .sidebar-nav { padding: 0 20px; flex-grow: 1; }
        .nav-section { margin-bottom: 25px; }
        .nav-title { color: rgba(255, 255, 255, 0.7); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 15px; white-space: nowrap; }
        
        .nav-btn {
            width: 100%; padding: 14px 20px;
            background: transparent; color: rgba(255, 255, 255, 0.85);
            margin-bottom: 8px; border-radius: 10px;
            display: flex; align-items: center; gap: 15px;
            font-size: 15px; font-weight: 500; 
            white-space: nowrap; overflow: hidden;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .nav-btn.active { background: rgba(255, 255, 255, 0.15); color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .nav-btn .icon { width: 24px; font-size: 18px; text-align: center; flex-shrink: 0; }

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
        .overlay.active { display: block; opacity: 1; }

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
        
        .page-title-group { display: flex; align-items: center; gap: 15px; min-width: 0; }
        .page-title h2 { font-size: 28px; color: var(--text-dark); margin: 0; white-space: nowrap; }
        .page-title p { color: var(--text-light); margin: 5px 0 0 0; font-size: 14px; white-space: nowrap; }
        
        hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--light-green), transparent);
            margin: 20px 0 30px 0;
        }

        .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
        .col-12 { width: 100%; padding: 15px; }
        
        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            height: 100%;
            display: flex; flex-direction: column;
            border-top: 4px solid transparent; 
            transition: transform 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.15);
        }

        .card-points { border-top-color: #ffa726; }
        .card-carbon { border-top-color: var(--light-green); }

        .stat-content { display: flex; align-items: center; gap: 20px; }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .icon-points { background-color: #fff3e0; color: #ffa726; }
        .icon-carbon { background-color: #e8f5e9; color: var(--eco-green); }

        .stat-text h3 { margin: 0; font-size: 28px; color: var(--text-dark); }
        .stat-text p { margin: 5px 0 0; color: var(--text-light); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }

        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-title { font-size: 18px; font-weight: 700; color: var(--text-dark); }
        .chart-wrapper { position: relative; flex-grow: 1; min-height: 300px; width: 100%; }

        .mobile-menu-btn { 
            display: none; 
            background: none; 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: var(--text-dark); 
            padding: 0; margin-right: 5px;
        }

        @media (min-width: 768px) {
            .col-md-6 { width: 50%; padding: 15px; }
        }
        @media (min-width: 1100px) {
            .col-lg-8 { width: 66.6666%; padding: 15px; }
            .col-lg-4 { width: 33.3333%; padding: 15px; }
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

            .col-md-6, .col-lg-8, .col-lg-4 { width: 100%; }
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
                    <a href="news_page" class="nav-btn"><span class="icon">📰</span><span>News</span></a> 
                </div>

                <div class="nav-section">
                    <div class="nav-title">Tools</div>
                    <a href="../LK/SmartParking.php" class="nav-btn"><span class="icon">🅿️</span><span>Smart Parking</span></a>
                    <a href="../LK/Ahm.php" class="nav-btn"><span class="icon">🎯</span><span>Achievement</span></a>
                    <a href="#" class="nav-btn active"><span class="icon">🎁</span><span>Statistic/Dashboard</span></a> 
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
                    <h2>Dashboard</h2>
                    <p>Track your environmental impact</p>
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="dashboard-card card-points">
                    <div class="stat-content">
                        <div class="stat-icon icon-points">🏆</div>
                        <div class="stat-text">
                            <h3><?php echo number_format($total_points); ?></h3>
                            <p>Total Points</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6">
                <div class="dashboard-card card-carbon">
                    <div class="stat-content">
                        <div class="stat-icon icon-carbon">🌱</div>
                        <div class="stat-text">
                            <h3><?php echo $total_carbon; ?> <span style="font-size:16px; color:#999;">kg</span></h3>
                            <p>CO2 Saved</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="dashboard-card">
                    <div class="chart-header">
                        <span class="chart-title">Monthly Progress</span>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="dashboard-card">
                    <div class="chart-header">
                        <span class="chart-title">Contribution by Log</span>
                    </div>
                    <div class="chart-wrapper" style="display:flex; justify-content:center;">
                        <canvas id="transportChart"></canvas>
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

    const barLabels = <?php echo $json_bar_labels ?: '[]'; ?>;
    const barData = <?php echo $json_bar_data ?: '[]'; ?>;
    const pieLabels = <?php echo $json_pie_labels ?: '[]'; ?>;
    const pieData = <?php echo $json_pie_data ?: '[]'; ?>;

    const colors = { 
        green: '#2e7d32', 
        light: '#4caf50', 
        yellow: '#ffca28', 
        red: '#ef5350', 
        gray: '#ddd' 
    };

    const ctxBar = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: barLabels.length ? barLabels : ['No Data'],
            datasets: [{
                label: 'Points',
                data: barData.length ? barData : [0],
                backgroundColor: colors.green,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
        }
    });

    const ctxPie = document.getElementById('transportChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: [colors.green, colors.light, colors.yellow, colors.red, colors.gray],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });
</script>

</body>
</html>