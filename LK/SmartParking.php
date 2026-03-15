<?php
    session_start();

    include("conn.php");
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.12);
            --border-radius: 12px;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        button {
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: 0.25s ease;
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
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
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
            text-decoration: none;
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
        }

        .nav-btn {
            width: 100%;
            padding: 14px 20px;
            background: transparent;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 8px;
            border-radius: 10px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .nav-btn .icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            width: 46px;
            height: 46px;
            background: white;
            border-radius: 50%;
            font-size: 22px;
            z-index: 1000;
            box-shadow: var(--shadow);
            align-items: center;
            justify-content: center;
        }

        /* ================= Drawer ================= */
        .drawer {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--eco-green), #1b5e20);
            transition: transform 0.35s cubic-bezier(.4,0,.2,1);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
        }

        .drawer.open {
            transform: translateX(280px);
        }

        .drawer-logo {
            margin: 20px;
            border-radius: 14px;
            background: rgba(255,255,255,0.95);
            color: var(--eco-green);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
            box-shadow: var(--shadow);
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .drawer-content {
            padding: 10px 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .drawer-section {
            margin-bottom: 18px;
        }

        .drawer-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.6);
            margin: 10px 6px;
        }

        .drawer-content button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            background: transparent;
            color: rgba(255,255,255,0.9);
            font-size: 15px;
            font-weight: 500;
            text-align: left;
            transition: all 0.25s ease;
        }

        .drawer-content button:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(6px);
        }

        .drawer-content button.active {
            background: #ffffff;
            color: var(--eco-green);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .drawer-content .icon {
            width: 22px;
            text-align: center;
            font-size: 18px;
        }

        /* ================= Main ================= */
        .main {
            margin-left: 300px;
            padding: 30px;
            width: calc(100% - 300px);
        }

        /* ================= Content ================= */
        hr {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--light-green), transparent);
            margin: 20px 0 30px 0;
        }

        .container {
            display: flex;
            gap: 40px;
        }

        .parking-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .parking-status {
            flex: 2;
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .parking-status h3 {
            margin-top: 0;
            color: var(--text-dark);
            font-size: 22px;
            margin-bottom: 20px;
        }

        .parking-status .status-counts {
            display: flex;
            gap: 15px;
            margin-bottom: 24px;
        }

        .parking-status .status-counts div {
            background: #e8f5e9;
            padding: 16px;
            border-radius: 10px;
            flex: 1;
            text-align: center;
            font-weight: 600;
            color: var(--text-dark);
            border: 2px solid var(--accent-green);
        }

        .parking-status .status-counts div span {
            font-size: 28px;
            font-weight: 700;
            color: var(--eco-green);
            display: block;
            margin-top: 8px;
        }

        .parking-location {
            border: 2px solid #e0e0e0;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }

        .parking-location:hover {
            border-color: var(--light-green);
            background: #f1f8e9;
        }

        .parking-location strong {
            font-size: 18px;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }

        .parking-location p {
            margin: 8px 0;
            color: var(--text-light);
        }

        .parking-location button {
            width: 100%;
            padding: 14px;
            margin-top: 12px;
            border-radius: 8px;
            background: var(--eco-green);
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .parking-location button:hover {
            background: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.3);
        }

        .parking-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .prediction, .recommended {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .prediction h4, .recommended h4 {
            margin-top: 0;
            color: var(--text-dark);
            font-size: 20px;
            margin-bottom: 20px;
        }

        .prediction .slot {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: #f5f5f5;
            border-radius: 8px;
            font-weight: 500;
        }

        .prediction .slot span:last-child {
            color: var(--eco-green);
            font-weight: 600;
        }

        .recommended .details {
            margin: 12px 0;
            color: var(--text-light);
        }

        .recommended button {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            background: var(--eco-green);
            color: white;
            font-weight: 600;
            font-size: 16px;
            margin-top: 16px;
        }

        .recommended button:hover {
            background: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.3);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .main {
                margin-left: 0;
                padding: 20px 16px;
                width: 100%;
            }

            .parking-container {
                flex-direction: column;
                gap: 20px;
            }

            .parking-status, .parking-right {
                width: 100%;
            }

            .status-counts {
                flex-direction: column;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .top-right {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body>
    <div class="mobile-menu-btn" onclick="toggleDrawer()">☰</div>

    <!-- Drawer -->
    <div class="drawer" id="drawer">
        <div class="drawer-logo">
            <div class="icon">🌱</div>
            <span>GOBy</span>
        </div>
        
        <div class="drawer-content">
            <div class="drawer-section">
                <div class="drawer-title">Navigation</div>
                <button>
                    <span class="icon">🏠</span>
                    <span>Homepage</span>
                </button>
                
                <button>
                    <span class="icon">📅</span>
                    <span>Event</span>
                </button>
                
                <button>
                    <span class="icon">🏆</span>
                    <span>Challenge</span>
                </button>
                
                <button>
                    <span class="icon">📊</span>
                    <span>Reward</span>
                </button>
                
                <button>
                    <span class="icon">🗺️</span>
                    <span>Journey Planner</span>
                </button>
            </div>
            
            <div class="drawer-section">
                <div class="drawer-title">Community</div>
                <button>
                    <span class="icon">👥</span>
                    <span>Carpool List</span>
                </button>
                
                <button>
                    <span class="icon">🏆</span>
                    <span>Ranking</span>
                </button>
                
                <button>
                    <span class="icon">📰</span>
                    <span>News</span>
                </button>
            </div>
            
            <div class="drawer-section">
                <div class="drawer-title">Tools</div>
                <button class="active">
                    <span class="icon">🅿️</span>
                    <span>Smart Parking</span>
                </button>
                
                <button>
                    <span class="icon">🎯</span>
                    <span>Achievement</span>
                </button>
                
                <button>
                    <span class="icon">🎁</span>
                    <span>Statistic/Dashboard</span>
                </button>
            </div>
            
            <div class="drawer-section">
                <div class="drawer-title">Registration</div>
                <button>
                    <span class="icon">📝</span>
                    <span>Register Event Organizer</span>
                </button>
                
                <button>
                    <span class="icon">🚗</span>
                    <span>Register Driver</span>
                </button>
            </div>
            
            <div class="drawer-section">
                <div class="drawer-title">Account</div>
                <button>
                    <span class="icon">👤</span>
                    <span>Profile</span>
                </button>
                
                <button>
                    <span class="icon">🚪</span>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="../WS/homepage.php" class="sidebar-logo">
                <div class="logo-icon">🌱</div>
                <span>GOBy</span>
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-title">Navigation</div>
                <a href="../WS/homepage.php" class="nav-btn">
                    <span class="icon">🏠</span>
                    <span>Homepage</span>
                </a>

                <a href="../WS/event.php" class="nav-btn">
                    <span class="icon">📅</span>
                    <span>Event</span>
                </a>

                <a href="../Aston/Chlng.php" class="nav-btn">
                    <span class="icon">🏆</span>
                    <span>Challenge</span>
                </a>

                <a href="../WS/reward_page.php" class="nav-btn">
                    <span class="icon">📊</span>
                    <span>Reward</span>
                </a>

                <a href="journeyPlanner.php" class="nav-btn">
                    <span class="icon">🗺️</span>
                    <span>Journey Planner</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Community</div>
                <a href="CarpoolList.php" class="nav-btn">
                    <span class="icon">👥</span>
                    <span>Carpool List</span>
                </a>

                <a href="../RY/ranking_page.php" class="nav-btn">
                    <span class="icon">🏆</span>
                    <span>Ranking</span>
                </a>

                <a href="../RY/news_page.php" class="nav-btn">
                    <span class="icon">📰</span>
                    <span>News</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Tools</div>
                <a href="#" class="nav-btn active">
                    <span class="icon">🅿️</span>
                    <span>Smart Parking</span>
                </a>

                <a href="Ahm.php" class="nav-btn">
                    <span class="icon">🎯</span>
                    <span>Achievement</span>
                </a>

                <a href="../RY/statistics.php" class="nav-btn">
                    <span class="icon">🎁</span>
                    <span>Statistic/Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Registration</div>
                <a href="../WS/register_event_organizer.php" class="nav-btn">
                    <span class="icon">📝</span>
                    <span>Register Event Organizer</span>
                </a>

                <a href="../WS/register_driver.php" class="nav-btn">
                    <span class="icon">🚗</span>
                    <span>Register Driver</span>
                </a>
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

    <!-- Main Content -->
    <main class="main">
        <h2>Smart Parking</h2>
        <hr><br>

        <div class="parking-container">

            <div class="parking-status">
                <h3>Real-Time Parking Status</h3>

                <div class="status-counts">
                    <div>Available<br><span id="availableCount">0</span> Locations</div>
                    <div>Limited<br><span id="limitedCount">0</span> Locations</div>
                    <div>Full<br><span id="fullCount">0</span> Locations</div>
                </div>

                <div class="parking-location">
                    <strong>APU Outdoor Parking</strong>
                    <p>APU Car Park - Zone B</p>
                    <p>Status: <span id="outdoorStatus"></span></p>
                    <p>Available: <span id="outdoorAvail"></span></p>
                    <button onclick="navigateOutdoor()">Navigation to parking</button>
                </div>

                <div class="parking-location">
                    <strong>APU Indoor Parking</strong>
                    <p>Asia Pacific University</p>
                    <p>Status: <span id="indoorStatus"></span></p>
                    <p>Available: <span id="indoorAvail"></span></p>
                    <button onclick="navigateIndoor()">Navigation to parking</button>
                </div>
            </div>

            <div class="parking-right">
                <div class="prediction">
                    <h4>Peak Time Prediction</h4>
                    <div class="slot">
                        <span>8:00 AM-10:00 AM</span>
                        <span>90%</span>
                    </div>

                    <div class="slot">
                        <span>10:00 AM-12:00 PM</span>
                        <span>80%</span>
                    </div>

                    <div class="slot">
                        <span>12:00 PM-2:00 PM</span>
                        <span>65%</span>
                    </div>
                </div>

                <div class="recommended">
                    <h4>Recommended</h4>
                    <strong id="recommendName">APU Outdoor Parking</strong>
                    <p class="details" id="recommendAddress">APU Car Park - Zone B</p>
                    <p class="details" id="recommendAvail"></p>
                    <button id="recommendNav" onclick="navigateRecommended()">Navigation to parking</button>
                </div>
            </div>
        </div>
    </main>

    <script>
    function toggleDrawer() { 
        document.getElementById("drawer").classList.toggle("open"); 
    }

    function navigateOutdoor() {
        window.open("https://www.google.com/maps/search/?api=1&query=APU+Car+Park+Zone+B");
    }

    function navigateIndoor() {
        window.open("https://www.google.com/maps/search/?api=1&query=Asia+Pacific+University+of+Technology+%26+Innovation");
    }

    function navigateRecommended() {
        if(parking.outdoor.available >= parking.indoor.available) {
            navigateOutdoor();
        } else {
            navigateIndoor();
        }
    }

    const parking = {
        outdoor: { total: 400, available: 200 },
        indoor: { total: 200, available: 50 }
    };

    function smoothChange(current, total) {
        const change = Math.floor(Math.random() * 3) - 1;
        let next = current + change;
        if(next < 0) next = 0;
        if(next > total) next = total;
        return next;
    }

    function updateParking() {
        parking.outdoor.available = smoothChange(parking.outdoor.available, parking.outdoor.total);
        parking.indoor.available = smoothChange(parking.indoor.available, parking.indoor.total);

        document.getElementById("outdoorAvail").innerText = `${parking.outdoor.available} / ${parking.outdoor.total}`;
        document.getElementById("indoorAvail").innerText = `${parking.indoor.available} / ${parking.indoor.total}`;

        let available = 0, limited = 0, full = 0;

        function getStatus(avail, total) {
            if(avail === 0) { 
                full++;
                return "Full";
            }

            if(avail/total < 0.3) {
                limited++;
                return "Limited";
            }

            available++;
            return "Available";
        }

        document.getElementById("outdoorStatus").innerText = getStatus(parking.outdoor.available, parking.outdoor.total);
        document.getElementById("indoorStatus").innerText = getStatus(parking.indoor.available, parking.indoor.total);

        document.getElementById("availableCount").innerText = available;
        document.getElementById("limitedCount").innerText = limited;
        document.getElementById("fullCount").innerText = full;

        // Recommended Part
        if(parking.outdoor.available >= parking.indoor.available) {
            document.getElementById("recommendName").innerText = "APU Outdoor Parking";
            document.getElementById("recommendAddress").innerText = "APU Car Park - Zone B";
            document.getElementById("recommendAvail").innerText = `Available: ${parking.outdoor.available} / ${parking.outdoor.total} spots`;
        } else {
            document.getElementById("recommendName").innerText = "APU Indoor Parking";
            document.getElementById("recommendAddress").innerText = "Asia Pacific University";
            document.getElementById("recommendAvail").innerText = `Available: ${parking.indoor.available} / ${parking.indoor.total} spots`;
        }
    }

    updateParking();
    setInterval(updateParking, 5000);
    </script>

</body>
</html>