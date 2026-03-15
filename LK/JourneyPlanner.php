<?php
    session_start();

    include("conn.php");
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Journey Planner</title>

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

    /* ================= Sidebar ================= */
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

    /* ================= Mobile Drawer ================= */
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

    /* ================= Drawer Sections ================= */
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

    .left { 
      width: 60%;
    }

    .route-box input {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 15px;
    }

    .mode-btns {
      display: flex;
      gap: 20px;
      margin:10px auto 50px auto;
    }

    .mode-btns button {
      flex: 1;
      background: var(--eco-green);
      color: white;
      padding: 10px;
      border-radius: 8px;
      font-weight: 600;
    }

    .mode-btns button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 5px rgba(0,0,0,0.15);
    }

    /* ================= Recommendation Box ================= */
    .recommend-box {
      margin-top: 20px;
      padding: 18px 20px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: var(--shadow);
      border-left: 6px solid var(--eco-green);
      font-size: 15px;
      color: var(--text-medium);
      line-height: 1.6;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 80px;
      transition: all 0.3s ease;
    }

    .recommend-box.active {
      background: #f9fff6;
      color: var(--text-dark);
      font-weight: 600;
    }

    .box-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 20px;
    }

    /* ================= Rewards Table Card ================= */
    .right {
      width: 40%;
      border-radius: 14px;
    }

    .right table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      overflow: hidden;
      border-radius: 12px;
      background:white;
    }

    .right th {
      background: var(--eco-green);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      text-align: left;
    }

    .right th,
    .right td {
      padding: 18px;
      border-bottom: 2px solid #e0e0e0;
    }

    .right tr:last-child td {
      border-bottom: none;
    }

    .right tr:hover td {
      background: #f4fbf4;
    }

    /* Mode emphasis */
    .right td:first-child {
      font-weight: 600;
      color: var(--text-dark);
    }

    /* ================= MOBILE ================= */
    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .mobile-menu-btn {
        display: flex;
      }

      .main {
        margin-left: 0;
        padding: 20px 10px;
        width: 100%;
      }

      .container {
        flex-direction: column;
        gap: 16px;
        width: 100%;
      }

      .left,
      .right {
        width: 100%;
      }

      .mode-btns {
        flex-direction: column;
      }

      .top-bar {
        align-items: flex-start;
      }

      .right {
        overflow-x: auto;
      }

      .right table {
        min-width: 320px;
      }
    }
  </style>
</head>

<body>
  <div class="mobile-menu-btn" onclick="toggleDrawer()">☰</div>

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

          <button class="active">
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
        <button>
          <span class="icon">🅿️</span>
          <span>Smart Parking</span>
        </button>

        <button>
          <span class="icon">🎯</span>
          <span>Achievement</span>
        </button>

        <button>
          <span class="icon">📊</span>
          <span>Statistic / Dashboard</span>
        </button>
      </div>

      <div class="drawer-section">
        <div class="drawer-title">Account</div>
        <button>
          <span class="icon">👤</span>
          <span>Profile</span>
        </button>

        <button>
          <span class="icon">🎁</span>
          <span>Reward</span>
        </button>
      </div>
      
    </div>
  </div>

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

        <a href="#" class="nav-btn active">
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
        <a href="SmartParking.php" class="nav-btn">
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

  <main class="main">
      <h2>Journey Planner</h2>
    <hr><br>

    <div class="container">

      <div class="left">
        <div class="box-title">Plan Your Route</div>

        <div class="route-box">
          <input id="fromInput" placeholder="From: LRT bukit jalil" autocomplete="off">
          <input id="toInput" placeholder="To: KLCC" autocomplete="off">
          <div class="mode-btns">
            <button onclick="openRoute('car')">Car</button>
            <button onclick="openRoute('bus')">Bus</button>
            <button onclick="openRoute('foot')">Walk</button>
            
          </div>
        </div>

        <div class="recommend-box" id="distanceInfo">
          Distance and Score will appear here
        </div>
      </div>

      <div class="right">
        <div class="box-title">Travel Rewards / Scores</div>
        <table>
          <tr><th>Mode</th><th>Score per km</th></tr>
          <tr><td>Car</td><td>1 point/km</td></tr>
          <tr><td>Bus</td><td>2 points/km</td></tr>
          <tr><td>Walk</td><td>5 points/km</td></tr>
        </table>
      </div>
    </div>
  </main>

  <script>
    function toggleDrawer() {
      document.getElementById("drawer").classList.toggle("open");
    }

    const scorePerKm = { car: 1, bus: 2, foot: 5};
    let isCalculating = false;

    async function geocode(query) {
      try {
        const res = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}`);
        const data = await res.json();

        if (!data.features || !data.features.length){
          return null;
        }
        return data.features[0].geometry.coordinates;
      } 
      catch { 
        return null; 
      }
    }

    async function openRoute(mode) {
      if (isCalculating) return;
      isCalculating = true;
      const from = document.getElementById("fromInput").value.trim();
      const to = document.getElementById("toInput").value.trim();
      const infoBox = document.getElementById("distanceInfo");

      if (!from || !to) { 
        alert("Enter both locations");
        isCalculating = false;
        return;
      }

      infoBox.textContent = "Calculating route...";
      infoBox.classList.remove("active");

      try {
        const [fromCoord, toCoord] = await Promise.all([geocode(from), geocode(to)]);

        if (!fromCoord || !toCoord) {
          infoBox.textContent = "Location not found";
          isCalculating = false; return;
        }

        const url = `https://router.project-osrm.org/route/v1/${mode}/${fromCoord[0]},${fromCoord[1]};${toCoord[0]},${toCoord[1]}?overview=false`;
        const res = await fetch(url);
        const data = await res.json();

        if (!data.routes || !data.routes.length) {
          infoBox.textContent = "No route found";
          isCalculating = false; return;
        }

        const distanceKm = data.routes[0].distance / 1000;
        const score = Math.round(distanceKm * (scorePerKm[mode] || 1));

        infoBox.textContent = `🚏 Distance: ${distanceKm.toFixed(2)} km  |  🌱 Score: ${score} points`;
        infoBox.classList.add("active");

        window.open(`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(from)}&destination=${encodeURIComponent(to)}&travelmode=${mode}`, "_blank");
      }
      catch (e) { 
        console.error(e);
        infoBox.textContent = "Error calculating route";
        infoBox.classList.remove("active");
       }
       
      isCalculating = false;
    } 
  </script>
</body>
</html>
