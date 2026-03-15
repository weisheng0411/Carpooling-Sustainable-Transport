<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli("127.0.0.1", "root", "", "wdd", 3306);
$mysqli->set_charset("utf8mb4");

$userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0;
if ($userId <= 0) { header("Location: /ASS-WDD/WS/Login.php"); exit; }

$stmt = $mysqli->prepare("
  SELECT name, photo_url, current_point, summary_point
  FROM user_acc
  WHERE user_id=?
  LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { $mysqli->close(); die("Invalid user"); }

$currentPoint = (int)$user["current_point"];
$summaryPoint = (int)$user["summary_point"];

$avatar = !empty($user["photo_url"])
  ? ("/ASS-WDD/" . ltrim($user["photo_url"], "/"))
  : "/ASS-WDD/default_avatar.png";

$stmt = $mysqli->prepare("
  SELECT
    a.Achievements_ID,
    a.Progress,
    a.Start_date,
    a.End_date,
    a.Bonus_point,
    a.Status,
    c.Challenges_ID,
    c.Name,
    c.Description,
    c.Point,
    c.Type,
    c.Published_date,
    c.Photo_URL
  FROM achievements a
  INNER JOIN challenges c ON c.Challenges_ID = a.Challenges_ID
  WHERE a.User_ID = ?
  ORDER BY
    CASE
      WHEN LOWER(a.Status)='ongoing' THEN 0
      WHEN LOWER(a.Status)='completed' THEN 1
      ELSE 2
    END,
    a.Start_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mysqli->close();

$totalJoined = count($rows);
$completedCount = 0;
$ongoingCount = 0;

foreach ($rows as $r) {
  $st = strtolower((string)$r["Status"]);
  if ($st === "completed") $completedCount++;
  else $ongoingCount++;
}
$completionRate = ($totalJoined > 0) ? (int)round(($completedCount / $totalJoined) * 100) : 0;

function safePercent($p){
  $p = (int)$p;
  if ($p < 0) $p = 0;
  if ($p > 100) $p = 100;
  return $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Achievement - GOBy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
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
      --admin-blue: #2196f3;
      --admin-blue-light: #64b5f6;
      --admin-blue-dark: #1976d2;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
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
      background: var(--background);
      min-height: 100vh;
      color: var(--text-dark);
      line-height: 1.6;
    }

    .container {
      display: flex;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }

    /* ========== SIDEBAR STYLING ========== */
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
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .logo-icon:hover {
      transform: rotate(15deg) scale(1.1);
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
      position: relative;
      overflow: hidden;
    }

    .nav-btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.6s ease;
    }

    .nav-btn:hover::before {
      left: 100%;
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

    /* ========== MAIN CONTENT ========== */
    .main {
      margin-left: 280px;
      padding: 30px;
      width: calc(100% - 280px);
    }

    /* Top Bar */
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      background: white;
      padding: 20px 30px;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      border-left: 4px solid var(--light-green);
    }

    .page-title h2 {
      font-size: 28px;
      color: var(--text-dark);
      margin: 0 0 5px 0;
    }

    .page-title p {
      color: var(--text-light);
      margin: 0;
      font-size: 15px;
    }

    .top-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--light-green), transparent);
      margin: 20px 0 30px 0;
    }

    /* Stats Overview */
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      border: 1px solid transparent;
      border-left: 4px solid var(--light-green);
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
      border-color: var(--light-green);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      background: linear-gradient(135deg, white, #f5f5f5);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      color: var(--eco-green);
      font-size: 24px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .stat-card:hover .stat-icon {
      transform: rotate(10deg) scale(1.1);
    }

    .stat-content h3 {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-medium);
      margin-bottom: 8px;
      font-weight: 600;
    }

    .stat-value {
      font-size: 36px;
      font-weight: 800;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .stat-trend {
      font-size: 14px;
      color: var(--light-green);
      font-weight: 600;
    }

    /* Content Grid */
    .content-grid {
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 30px;
      align-items: start;
    }

    /* Challenges Section */
    .challenges-section {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--shadow);
      border-left: 4px solid var(--light-green);
      transition: all 0.3s ease;
    }

    .challenges-section:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .section-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title::before {
      content: "";
      display: block;
      width: 8px;
      height: 24px;
      background: var(--light-green);
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
    }

    .challenges-count {
      background: linear-gradient(135deg, var(--light-green), var(--eco-green));
      color: white;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 700;
    }

    .challenges-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 25px;
    }

    .challenge-card {
      background: #f8f9f8;
      border: 2px solid #c8e6c9;
      border-radius: 12px;
      padding: 20px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      color: inherit;
      position: relative;
      overflow: hidden;
      min-height: 200px;
      display: flex;
      flex-direction: column;
    }

    .challenge-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--light-green), var(--accent-green));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }

    .challenge-card:hover::before {
      transform: scaleX(1);
    }

    .challenge-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      border-color: var(--light-green);
    }

    .card-header {
      padding-bottom: 15px;
      border-bottom: 1px solid #e0e0e0;
      margin-bottom: 15px;
      flex-grow: 1;
    }

    .challenge-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-dark);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-badge.ongoing {
      background: linear-gradient(135deg, #fff3e0, #ffcc80);
      color: #ef6c00;
    }

    .status-badge.completed {
      background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
      color: var(--eco-green);
      border: 1px solid #a5d6a7;
    }

    .challenge-desc {
      color: var(--text-light);
      font-size: 14px;
      line-height: 1.5;
      margin-bottom: 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-body {
      padding: 15px 0;
    }

    .progress-section {
      margin-bottom: 20px;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .progress-text {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-medium);
    }

    .progress-percent {
      font-size: 14px;
      font-weight: 700;
      color: var(--light-green);
    }

    .progress-bar {
      height: 8px;
      background-color: #e0e0e0;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--light-green), var(--accent-green));
      border-radius: 4px;
      transition: width 0.5s ease;
      position: relative;
    }

    .progress-fill::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 15px;
      border-top: 1px solid #e0e0e0;
      margin-top: auto;
    }

    .points-badge {
      padding: 8px 16px;
      background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
      border: 2px solid #a5d6a7;
      border-radius: 8px;
      font-weight: 700;
      color: var(--eco-green);
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }

    .points-badge i {
      font-size: 16px;
    }

    /* Sidebar Cards */
    .sidebar-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--shadow);
      margin-bottom: 25px;
      border-left: 4px solid var(--light-green);
      transition: all 0.3s ease;
    }

    .sidebar-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
    }

    .sidebar-card-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sidebar-card-title i {
      color: var(--light-green);
      font-size: 20px;
    }

    .stats-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .stat-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px;
      background: #f8f9f8;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      background: #e8f5e9;
      border-color: var(--light-green);
      transform: translateX(4px);
    }

    .stat-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-medium);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stat-number {
      font-size: 18px;
      font-weight: 800;
      color: var(--text-dark);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 40px;
      background: linear-gradient(135deg, #f8f9f8, white);
      border-radius: 12px;
      border: 2px dashed #c8e6c9;
    }

    .empty-icon {
      font-size: 64px;
      color: #c8e6c9;
      margin-bottom: 20px;
    }

    .empty-title {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 12px;
    }

    .empty-text {
      color: var(--text-light);
      font-size: 16px;
      margin-bottom: 24px;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
    }

    .primary-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 28px;
      background: linear-gradient(135deg, var(--light-green), var(--eco-green));
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
      position: relative;
      overflow: hidden;
    }

    .primary-btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.6s ease;
    }

    .primary-btn:hover::before {
      left: 100%;
    }

    .primary-btn:hover {
      background: linear-gradient(135deg, #43a047, #2e7d32);
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
    }

    .primary-btn:active {
      transform: translateY(-1px);
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

    .challenge-card {
      animation: fadeInUp 0.5s ease forwards;
      opacity: 0;
    }

    .challenge-card:nth-child(1) { animation-delay: 0.1s; }
    .challenge-card:nth-child(2) { animation-delay: 0.2s; }
    .challenge-card:nth-child(3) { animation-delay: 0.3s; }
    .challenge-card:nth-child(4) { animation-delay: 0.4s; }
    .challenge-card:nth-child(5) { animation-delay: 0.5s; }
    .challenge-card:nth-child(6) { animation-delay: 0.6s; }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
      
      .challenges-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      }
    }

    @media (max-width: 992px) {
      .main {
        margin-left: 0;
        width: 100%;
        padding: 24px;
      }
      
      .sidebar {
        transform: translateX(-100%);
        width: 300px;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        padding: 20px;
      }
      
      .top-right {
        width: 100%;
        justify-content: space-between;
      }
      
      .stats-overview {
        grid-template-columns: 1fr;
      }
      
      .challenges-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 900px) {
      .sidebar {
        width: 80px;
        overflow: hidden;
      }
      
      .sidebar-header .sidebar-logo span,
      .nav-title,
      .nav-btn span:not(.icon) {
        display: none;
      }
      
      .sidebar-header {
        padding: 20px 10px;
      }
      
      .logo-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
      }
      
      .nav-btn {
        justify-content: center;
        padding: 15px;
      }
      
      .nav-btn .icon {
        margin-right: 0;
        font-size: 20px;
      }
      
      .main {
        margin-left: 100px;
        width: calc(100% - 100px);
        padding: 20px;
      }
    }

    @media (max-width: 768px) {
      .page-title h2 {
        font-size: 28px;
      }
      
      .stat-card {
        padding: 20px;
      }
      
      .stat-value {
        font-size: 28px;
      }
      
      .challenges-section,
      .sidebar-card {
        padding: 24px;
      }
      
      .section-title {
        font-size: 20px;
      }
    }

    @media (max-width: 600px) {
      .main {
        margin-left: 0;
        width: 100%;
        padding: 15px;
        padding-top: 70px;
      }
      
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: 280px;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .sidebar-header .sidebar-logo span,
      .nav-title,
      .nav-btn span:not(.icon) {
        display: block;
      }
      
      .sidebar-header {
        padding: 25px 20px;
      }
      
      .nav-btn {
        justify-content: flex-start;
        padding: 14px 20px;
      }
      
      .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .top-right {
        width: 100%;
        justify-content: space-between;
      }
    }

    @media (max-width: 480px) {
      .main {
        padding: 16px;
      }
      
      .page-title h2 {
        font-size: 24px;
      }
      
      .page-title p {
        font-size: 14px;
      }
      
      .challenge-card {
        padding: 16px;
      }
    }
  </style>
</head>

<body>
<div class="container">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="/ASS-WDD/WS/homepage.php" class="sidebar-logo">
        <div class="logo-icon">🌱</div>
        <span>GOBy</span>
      </a>
    </div>
    
    <div class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-title">Navigation</div>
        <a href="/ASS-WDD/WS/homepage.php" class="nav-btn">
          <span class="icon">🏠</span>
          <span>Homepage</span>
        </a>
        <a href="/ASS-WDD/Aston/event.php" class="nav-btn">
          <span class="icon">📅</span>
          <span>Event</span>
        </a>
        <a href="/ASS-WDD/Aston/Chlng.php" class="nav-btn">
          <span class="icon">🏆</span>
          <span>Challenge</span>
        </a>
        <a href="/ASS-WDD/WS/reward_page.php" class="nav-btn">
          <span class="icon">📊</span>
          <span>Reward</span>
        </a>
        <a href="/ASS-WDD/Aston/navigation.php" class="nav-btn">
          <span class="icon">🗺️</span>
          <span>Journey Planner</span>
        </a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Community</div>
        <a href="/ASS-WDD/LK/CarpoolList.php" class="nav-btn">
          <span class="icon">👥</span>
          <span>Carpool List</span>
        </a>
        <a href="/ASS-WDD/RY/ranking_page.php" class="nav-btn">
          <span class="icon">🏆</span>
          <span>Ranking</span>
        </a>
        <a href="/ASS-WDD/RY/news_page.php" class="nav-btn">
          <span class="icon">📰</span>
          <span>News</span>
        </a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Tools</div>
        <a href="/ASS-WDD/LK/SmartParking.php" class="nav-btn">
          <span class="icon">🅿️</span>
          <span>Smart Parking</span>
        </a>
        <a href="/ASS-WDD/LK/Ahm.php" class="nav-btn active">
          <span class="icon">🎯</span>
          <span>Achievement</span>
        </a>
        <a href="/ASS-WDD/RY/statistics.php" class="nav-btn">
          <span class="icon">📈</span>
          <span>Statistic/Dashboard</span>
        </a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Registration</div>
        <a href="/ASS-WDD/Aston/register_event_organizer.php" class="nav-btn">
          <span class="icon">📝</span>
          <span>Register Event Organizer</span>
        </a>
        <a href="/ASS-WDD/Aston/register_driver.php" class="nav-btn">
          <span class="icon">🚗</span>
          <span>Register Driver</span>
        </a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Account</div>
        <a href="/ASS-WDD/Aston/profile.php" class="nav-btn">
          <span class="icon">👤</span>
          <span>Profile</span>
        </a>
        <a href="/ASS-WDD/Aston/logout.php" class="nav-btn">
          <span class="icon">🚪</span>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main">
    <!-- Top Bar -->
    <div class="top-bar">
      <div class="page-title">
        <h2>Achievement Dashboard</h2>
        <p>Track your challenge progress & completions 🎯</p>
      </div>

      <!-- Top Right Section - Empty as per requirement -->
      <div class="top-right">
        <!-- Avatar and notification icons removed as requested -->
      </div>
    </div>
   
    <hr>
    
    <!-- Stats Overview -->
    <div class="stats-overview">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-coins"></i>
        </div>
        <div class="stat-content">
          <h3>Current Points</h3>
          <div class="stat-value"><?= number_format($currentPoint) ?></div>
          <div class="stat-trend">+12% from last month</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>Completed Challenges</h3>
          <div class="stat-value"><?= $completedCount ?></div>
          <div class="stat-trend">Keep up the great work!</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
          <h3>Completion Rate</h3>
          <div class="stat-value"><?= $completionRate ?>%</div>
          <div class="stat-trend">Excellent progress</div>
        </div>
      </div>
    </div>

    <div class="content-grid">
      <!-- Challenges Section -->
      <div>
        <section class="challenges-section">
          <div class="section-header">
            <h3 class="section-title">
              Your Joined Challenges
              <?php if (!empty($rows)): ?>
              <span class="challenges-count"><?= count($rows) ?> challenges</span>
              <?php endif; ?>
            </h3>
          </div>

          <?php if (empty($rows)): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-trophy"></i>
            </div>
            <h3 class="empty-title">No Challenges Joined Yet</h3>
            <p class="empty-text">You haven't joined any challenges yet. Explore the Challenge page and start your journey!</p>
            <a href="/ASS-WDD/Aston/Chlng.php" class="primary-btn">
              <i class="fas fa-rocket"></i> Browse Challenges
            </a>
          </div>
          <?php else: ?>
          <div class="challenges-grid">
            <?php foreach($rows as $r):
              $progress = safePercent($r["Progress"]);
              $status = strtolower((string)$r["Status"]);
              $statusLabel = ($status === "completed") ? "Completed" : "In Progress";
              $statusClass = ($status === "completed") ? "completed" : "ongoing";

              $desc = (string)$r["Description"];
              if (mb_strlen($desc) > 120) $desc = mb_substr($desc, 0, 120) . "...";

              $bonus = isset($r["Bonus_point"]) ? (int)$r["Bonus_point"] : 0;
              $points = (int)$r["Point"];
              $totalPoints = ($status === "completed") ? ($points + $bonus) : $points;
            ?>
            <div class="challenge-card">
              <div class="card-header">
                <div class="challenge-title">
                  <span><?= htmlspecialchars($r["Name"]) ?></span>
                  <span class="status-badge <?= $statusClass ?>">
                    <i class="fas fa-<?= $status === 'completed' ? 'check' : 'clock' ?>"></i>
                    <?= htmlspecialchars($statusLabel) ?>
                  </span>
                </div>
                <p class="challenge-desc"><?= htmlspecialchars($desc) ?></p>
              </div>

              <div class="card-body">
                <div class="progress-section">
                  <div class="progress-label">
                    <span class="progress-text">Progress</span>
                    <span class="progress-percent"><?= $progress ?>%</span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                  </div>
                </div>
              </div>

              <div class="card-footer">
                <div class="points-badge">
                  <i class="fas fa-coins"></i>
                  <?php if ($status === "completed"): ?>
                  Earned <?= number_format($totalPoints) ?> points
                  <?php else: ?>
                  <?= number_format($totalPoints) ?> points available
                  <?php endif; ?>
                </div>
                <div style="color: var(--text-light); font-size: 13px; font-weight: 600;">
                  <i class="fas fa-calendar"></i>
                  <?= date('M d, Y', strtotime($r["Start_date"])) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </section>
      </div>

      <!-- Sidebar Stats -->
      <div>
        <div class="sidebar-card">
          <h4 class="sidebar-card-title">
            <i class="fas fa-chart-pie"></i>
            Quick Stats
          </h4>
          <div class="stats-list">
            <div class="stat-item">
              <span class="stat-label">
                <i class="fas fa-users"></i>
                Joined Challenges
              </span>
              <span class="stat-number"><?= $totalJoined ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">
                <i class="fas fa-spinner"></i>
                Ongoing Challenges
              </span>
              <span class="stat-number"><?= $ongoingCount ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">
                <i class="fas fa-check-double"></i>
                Completed Challenges
              </span>
              <span class="stat-number"><?= $completedCount ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">
                <i class="fas fa-star"></i>
                Summary Points
              </span>
              <span class="stat-number"><?= number_format($summaryPoint) ?></span>
            </div>
          </div>
        </div>

        <div class="sidebar-card">
          <h4 class="sidebar-card-title">
            <i class="fas fa-lightbulb"></i>
            Tips & Suggestions
          </h4>
          <div style="color: var(--text-light); font-size: 14px; line-height: 1.6;">
            <p style="margin-bottom: 12px;">
              <strong>🏆 Complete more challenges</strong><br>
              Each completed challenge gives you bonus points!
            </p>
            <p style="margin-bottom: 12px;">
              <strong>🚀 Join trending challenges</strong><br>
              Check the Challenge page for popular activities.
            </p>
            <p>
              <strong>📈 Track your progress</strong><br>
              Regularly monitor your achievements here.
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  // Add page load animation
  document.addEventListener('DOMContentLoaded', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    
    setTimeout(() => {
      document.body.style.opacity = '1';
    }, 100);
    
    // Add click animation to cards
    const cards = document.querySelectorAll('.challenge-card, .stat-card');
    cards.forEach(card => {
      card.addEventListener('click', function() {
        this.style.transform = 'translateY(-2px) scale(1.02)';
        setTimeout(() => {
          this.style.transform = 'translateY(-4px)';
        }, 100);
      });
    });
    
    // Mobile sidebar toggle (from homepage)
    let isSidebarOpen = false;
    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      sidebar.classList.toggle('active');
      isSidebarOpen = !isSidebarOpen;
    }

    // Add mobile sidebar toggle button
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    toggleBtn.className = 'mobile-toggle-btn';
    toggleBtn.style.cssText = `
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 9999;
      background: var(--light-green);
      color: white;
      border: none;
      border-radius: 8px;
      width: 44px;
      height: 44px;
      display: none;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    `;
    
    document.body.appendChild(toggleBtn);
    
    toggleBtn.addEventListener('click', toggleSidebar);
    
    // Show/hide toggle button based on screen size
    function updateToggleVisibility() {
      if (window.innerWidth <= 600) {
        toggleBtn.style.display = 'flex';
        sidebar.classList.remove('active');
      } else {
        toggleBtn.style.display = 'none';
        sidebar.classList.add('active');
      }
    }
    
    updateToggleVisibility();
    window.addEventListener('resize', updateToggleVisibility);
    
    // Add hover effect to stat items
    const statItems = document.querySelectorAll('.stat-item');
    statItems.forEach(item => {
      item.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(8px)';
      });
      
      item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
      });
    });

    // Esc key to close sidebar on mobile
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && window.innerWidth <= 600 && isSidebarOpen) {
        toggleSidebar();
      }
    });
  });
</script>
</body>
</html>