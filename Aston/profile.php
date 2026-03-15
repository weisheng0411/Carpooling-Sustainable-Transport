<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli("127.0.0.1","root","","wdd");
$mysqli->set_charset("utf8mb4");
if ($mysqli->connect_error) die("DB error");

$userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 1;

$stmt = $mysqli->prepare("
  SELECT apu_id, name, username, email, photo_url, current_point, summary_point
  FROM user_acc
  WHERE user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$user) die("Invalid user");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Profile - GOBy</title>

    <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

  <style>
    /* Center GOBy logo + text in sidebar */
    .sidebar-header {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 25px 20px;
        margin-bottom: 10px;
    }

    .sidebar-logo {
        justify-content: center;
        text-align: center;
        text-decoration: none;
        color: white;
        font-weight: 700;
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.3s ease;
    }

    .sidebar-logo:hover {
        transform: scale(1.05);
    }

    .logo-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: white;
        color: var(--eco-green);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .logo-icon:hover {
        transform: rotate(15deg) scale(1.1);
    }

    /* Make sidebar buttons better */
    .nav-btn {
        padding: 14px 20px;
        font-size: 15px;
        border-radius: 12px;
        gap: 15px;
        margin-bottom: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        width: 100%;
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
        font-size: 20px;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Top bar improvements */
    .top-bar {
        margin-bottom: 30px;
    }

    .page-title h2 {
        font-size: 32px;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        font-weight: 700;
    }

    .page-title p {
        font-size: 16px;
        color: var(--text-light);
        margin: 0;
    }

    /* Improved hr */
    hr {
        border: none;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--light-green) 20%, var(--light-green) 80%, transparent);
        margin: 0 0 40px 0;
        opacity: 0.5;
    }

    /* Section improvements */
    .section {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 30px;
        border-left: 6px solid var(--light-green);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 30px;
    }

    .section:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
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

    /* Profile header improvements */
    .profile-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }

    .left-pack {
        display: flex;
        align-items: center;
        gap: 20px;
        min-width: 0;
        flex: 1;
    }

    .avatar {
        width: 80px;
        height: 80px;
        border-radius: 18px;
        overflow: hidden;
        border: 3px solid rgba(46, 125, 50, 0.15);
        background: linear-gradient(135deg, #eef6ee, #d9ead9);
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15);
        flex: 0 0 auto;
        transition: all 0.3s ease;
    }

    .avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(46, 125, 50, 0.2);
        border-color: var(--light-green);
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.6s ease;
    }

    .avatar:hover img {
        transform: scale(1.1);
    }

    .id-block {
        min-width: 0;
        flex: 1;
    }

    .name {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 500px;
    }

    .meta {
        margin-top: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-light);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 500px;
    }

    .meta b {
        color: var(--text-dark);
        font-weight: 700;
    }

    /* Chips improvements */
    .chips {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 25px;
        background: white;
        border: 2px solid rgba(46, 125, 50, 0.15);
        color: var(--text-dark);
        font-weight: 700;
        font-size: 13px;
        box-shadow: 0 4px 10px rgba(46, 125, 50, 0.08);
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .chip:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 18px rgba(46, 125, 50, 0.12);
        border-color: var(--light-green);
        background: #f1f8e9;
    }

    /* Points display improvements */
    .mini {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .pill {
        border-radius: 16px;
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border: 2px solid rgba(46, 125, 50, 0.12);
        padding: 20px;
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.08);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .pill::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, var(--light-green), var(--accent-green));
        border-radius: 6px 0 0 6px;
    }

    .pill:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(46, 125, 50, 0.12);
        border-color: var(--light-green);
    }

    .pill .k {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-medium);
        letter-spacing: 0.8px;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .pill .v {
        font-size: 32px;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* Quick menu improvements */
    .menu-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .menu-tile {
        background: linear-gradient(135deg, #ffffff, #f8f9f8);
        border: 2px solid rgba(46, 125, 50, 0.15);
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.08);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 15px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .menu-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--light-green), var(--accent-green));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s ease;
    }

    .menu-tile:hover::before {
        transform: scaleX(1);
    }

    .menu-tile:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        border-color: var(--light-green);
    }

    .tile-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: var(--eco-green);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .menu-tile:hover .tile-icon {
        transform: rotate(10deg) scale(1.1);
    }

    .tile-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .tile-desc {
        margin: 0;
        color: var(--text-light);
        font-weight: 600;
        font-size: 14px;
        line-height: 1.5;
        flex: 1;
    }

    /* Button improvements */
    .continue-btn{
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-align: center;
        padding: 16px 28px;
        border-radius: 12px;
        font-size: 15px;
        display: inline-block;
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15);
    }

    .continue-btn {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        font-weight: 700;
    }

    .continue-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    .continue-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .continue-btn:hover {
        background: linear-gradient(135deg, #43a047, #2e7d32);
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 12px 30px rgba(46, 125, 50, 0.25);
    }

    .view-event-btn {
        background: linear-gradient(135deg, #ffffff, #f8f9f8);
        color: var(--text-dark);
        border: 2px solid var(--light-green);
    }

    .view-event-btn:hover {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(46, 125, 50, 0.2);
        border-color: transparent;
    }

    /* Footer button row */
    .button-row {
        margin-top: 40px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    /* Responsive design */
    @media (max-width: 900px) {
        .menu-grid {
            grid-template-columns: 1fr;
        }
        
        .name, .meta {
            max-width: 300px;
        }
        
        .mini {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }

    @media (max-width: 768px) {
        .profile-head {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        
        .chips {
            justify-content: flex-start;
            width: 100%;
        }
        
        .name, .meta {
            max-width: 100%;
        }
        
        .avatar {
            width: 70px;
            height: 70px;
        }
        
        .button-row {
            flex-direction: column;
        }
    }

    @media (max-width: 480px) {
        .section {
            padding: 25px;
        }
        
        .avatar {
            width: 60px;
            height: 60px;
        }
        
        .name {
            font-size: 20px;
        }
        
        .chip {
            padding: 8px 14px;
            font-size: 12px;
        }
        
        .menu-tile {
            padding: 20px;
        }
    }
</style>
</head>

<body>
  <div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
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
          <span class="icon">🏠</span><span>Homepage</span>
        </a>
        <a href="../WS/event.php" class="nav-btn">
          <span class="icon">📅</span><span>Event</span>
        </a>
        <a href="Chlng.php" class="nav-btn">
          <span class="icon">🏆</span><span>Challenge</span>
        </a>
        <a href="../WS/reward_page.php" class="nav-btn">
          <span class="icon">📊</span><span>Reward</span>
        </a>
        <a href="../LK/JourneyPlanner.php" class="nav-btn">
          <span class="icon">🗺️</span><span>Journey Planner</span>
        </a>
      </div>

      <div class="nav-section">
        <div class="nav-title">Community</div>
        <a href="../LK/CarpoolList.php" class="nav-btn">
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
        <a href="../LK/SmartParking.php" class="nav-btn">
          <span class="icon">🅿️</span>
          <span>Smart Parking</span>
        </a>
        <a href="../LK/Ahm.php" class="nav-btn">
          <span class="icon">🎯</span>
          <span>Achievement</span>
        </a>
        <a href="../RY/statistics.php" class="nav-btn">
          <span class="icon">📈</span>
          <span>Statistic/Dashboard</span>
        </a>
      </div>

      <div class="nav-section">
        <div class="nav-title">Registration</div>

        <a href="../WS/register_event_organizer.php" class="nav-btn">
          <span class="icon">📝</span><span>Register Event Organizer</span>
        </a>

        <a href="../WS/register_driver.php" class="nav-btn">
          <span class="icon">🚗</span><span>Register Driver</span>
        </a>
      </div>
      
      <div class="nav-section">
        <div class="nav-title">Account</div>
        <a href="#" class="nav-btn active">
          <span class="icon">👤</span><span>Profile</span>
        </a>
        <a href="../WS/logout.php" class="nav-btn">
          <span class="icon">🚪</span><span>Logout</span>
        </a>
      </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <div class="top-bar">
        <div class="page-title">
          <h2>Profile</h2>
          <p>Account overview and quick actions</p>
        </div>
      </div>

      <hr>

      <!-- PROFILE -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Your Account</h3>
        </div>

        <div class="profile-head">
          <div class="left-pack">
            <div class="avatar">
              <img src="/ASS-WDD/<?= htmlspecialchars($user["photo_url"] ?: 'max.jpg') ?>" alt="User Photo">
            </div>

            <div class="id-block">
              <p class="name"><?= htmlspecialchars($user["name"]) ?></p>
              <div class="meta"><?= htmlspecialchars($user["email"]) ?></div>
              <div class="meta"><b>APU ID:</b> <?= htmlspecialchars($user["apu_id"]) ?> &nbsp; • &nbsp; <b>@</b><?= htmlspecialchars($user["username"]) ?></div>
            </div>
          </div>

          <div class="chips">
            <div class="chip">👤 @<?= htmlspecialchars($user["username"]) ?></div>
            <div class="chip">🎓 <?= htmlspecialchars($user["apu_id"]) ?></div>
          </div>
        </div>

        <div class="mini">
          <div class="pill">
            <div class="k">Current Point</div>
            <div class="v"><?= (int)$user["current_point"] ?></div>
          </div>
          <div class="pill">
            <div class="k">Summary Point</div>
            <div class="v"><?= (int)$user["summary_point"] ?></div>
          </div>
        </div>
      </section>

      <!-- QUICK MENU -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Quick Actions</h3>
        </div>

        <div class="menu-grid">
          <a class="menu-tile" href="points_history.php">
            <div class="tile-icon">📊</div>
            <h3 class="tile-title">Points History</h3>
            <p class="tile-desc">View bonus points and all earning records.</p>
            <span class="continue-btn" style="width:fit-content;">Open</span>
          </a>

          <a class="menu-tile" href="../LK/CustomerService.php">
            <div class="tile-icon">💬</div>
            <h3 class="tile-title">Customer Service</h3>
            <p class="tile-desc">Get help, submit issues, or contact support.</p>
            <span class="continue-btn" style="width:fit-content;">Open</span>
          </a>

          <a class="menu-tile" href="../RY/abous_us_page.php">
            <div class="tile-icon">ℹ️</div>
            <h3 class="tile-title">About Us</h3>
            <p class="tile-desc">Learn more about GOBy and the mission.</p>
            <span class="continue-btn" style="width:fit-content;">Open</span>
          </a>

          <a class="menu-tile" href="../LK/EditProfile.php">
            <div class="tile-icon">⚙️</div>
            <h3 class="tile-title">Edit Profile</h3>
            <p class="tile-desc">Change your personal information.</p>
            <span class="continue-btn" style="width:fit-content;">Open</span>
          </a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
