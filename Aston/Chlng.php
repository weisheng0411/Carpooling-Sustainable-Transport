<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli("127.0.0.1", "root", "", "wdd", 3306);
$mysqli->set_charset("utf8mb4");
if ($mysqli->connect_error) die("DB error");

$typeResult = $mysqli->query("SELECT DISTINCT Type FROM challenges ORDER BY Type");
$challengeTypes = $typeResult->fetch_all(MYSQLI_ASSOC);

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$selectedType = isset($_GET['type']) ? $_GET['type'] : '';

$query = "SELECT Challenges_ID, Name, Description, Photo_URL, Published_date, Type FROM challenges";
$conditions = [];
$params = [];
$types = "";

if (!empty($searchTerm)) {
    $conditions[] = "(Name LIKE ? OR Description LIKE ?)";
    $params[] = "%" . $searchTerm . "%";
    $params[] = "%" . $searchTerm . "%";
    $types .= "ss";
}

if (!empty($selectedType)) {
    $conditions[] = "Type = ?";
    $params[] = $selectedType;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY Published_date DESC";

if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $challenges = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $mysqli->query($query);
    $challenges = $result->fetch_all(MYSQLI_ASSOC);
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Challenge - GOBy</title>
  
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

    /* 增强导航按钮效果 */
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
        font-weight: 500;
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

    /* 增强顶部区域间距 */
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

    /* 增强图标按钮 */
    .icon-btn {
        position: relative;
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        color: var(--eco-green);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .icon-btn:hover {
        background: var(--light-green);
        color: white;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
    }

    .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ff6b6b;
        color: white;
        width: 22px;
        height: 22px;
        font-size: 12px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* 增强头像按钮 */
    .avatar-btn {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .avatar-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        border-color: var(--light-green);
    }

    /* 增强水平线 */
    hr {
        border: none;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--light-green) 20%, var(--light-green) 80%, transparent);
        margin: 0 0 40px 0;
        opacity: 0.5;
    }

    /* 增强内容区域间距 */
    .content-sections {
        display: flex;
        flex-direction: column;
        gap: 35px;
    }

    /* 增强卡片设计 */
    .section {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 30px;
        border-left: 4px solid var(--light-green);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 25px;
    }

    .section:hover {
        transform: translateY(-5px);
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
        width: 6px;
        height: 24px;
        background: var(--light-green);
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
    }

    /* 增强搜索区域 */
    .search-row {
        display: flex;
        gap: 20px;
        align-items: center;
        margin-bottom: 30px;
    }

    .search-row input[type="text"] {
        flex: 1;
        padding: 16px 20px;
        border: 2px solid rgba(46, 125, 50, 0.2);
        border-radius: 12px;
        background: white;
        font-size: 16px;
        color: var(--text-dark);
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .search-row input[type="text"]:focus {
        outline: none;
        border-color: var(--light-green);
        box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2);
        transform: translateY(-2px);
    }

    /* 增强按钮 */
    .view-btn, .continue-btn {
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .view-btn {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        padding: 14px 28px;
        border-radius: 10px;
        font-size: 15px;
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
    }

    .view-btn:hover {
        background: linear-gradient(135deg, #43a047, #2e7d32);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
    }

    .continue-btn {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        padding: 10px 24px;
        border-radius: 10px;
        font-size: 15px;
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        text-align: center;
        display: inline-block;
        width: 100%;
        margin-top: auto;
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
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
    }

    /* 增强标签页 */
    .tabs {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 25px;
    }

    .tab {
        padding: 12px 24px;
        border-radius: 30px;
        border: 2px solid rgba(46, 125, 50, 0.2);
        background: white;
        color: var(--text-dark);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 10px rgba(46, 125, 50, 0.08);
        position: relative;
        overflow: hidden;
    }

    .tab::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(76, 175, 80, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .tab:hover::before {
        left: 100%;
    }

    .tab:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
        border-color: var(--light-green);
        background: #f1f8e9;
    }

    /* 活动状态的标签样式 */
    .tab.active {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        border-color: var(--light-green);
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        transform: translateY(-2px);
    }

    /* 挑战类型标签 */
    .challenge-type-tag {
        display: inline-block;
        padding: 6px 12px;
        background: #e8f5e9;
        color: #2e7d32;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 5px;
        border: 1px solid #c8e6c9;
    }

    /* 增强挑战卡片网格 */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }

    .challenge-card {
        background: #f8f9f8;
        border: 2px solid #c8e6c9;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
        height: 350px;
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
        transition: transform 0.4s ease;
    }

    .challenge-card:hover::before {
        transform: scaleX(1);
    }

    .challenge-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        border-color: var(--light-green);
    }

    .thumb {
        height: 180px;
        border-radius: 12px;
        overflow: hidden;
        background: #eef6ee;
        border: 2px solid rgba(46, 125, 50, 0.15);
        position: relative;
        transition: transform 0.4s ease;
    }

    .challenge-card:hover .thumb {
        transform: scale(1.05);
    }

    .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.4s ease;
    }

    .challenge-card:hover .thumb img {
        transform: scale(1.1);
    }

    .challenge-title {
        margin: 0;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 18px;
        line-height: 1.4;
        min-height: 50px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .challenge-description {
        color: var(--text-light);
        font-size: 14px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin: 5px 0;
    }

    /* 添加一些动画效果 */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .challenge-card {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }

    .challenge-card:nth-child(1) { animation-delay: 0.1s; }
    .challenge-card:nth-child(2) { animation-delay: 0.2s; }
    .challenge-card:nth-child(3) { animation-delay: 0.3s; }
    .challenge-card:nth-child(4) { animation-delay: 0.4s; }
    .challenge-card:nth-child(5) { animation-delay: 0.5s; }
    .challenge-card:nth-child(6) { animation-delay: 0.6s; }
    .challenge-card:nth-child(7) { animation-delay: 0.7s; }
    .challenge-card:nth-child(8) { animation-delay: 0.8s; }

    /* 响应式设计 */
    @media (max-width: 1400px) {
        .card-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 1100px) {
        .card-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .challenge-card {
            height: 320px;
        }
    }

    @media (max-width: 900px) {
        .card-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .challenge-card {
            padding: 20px;
            height: 300px;
        }
        
        .thumb {
            height: 150px;
        }
        
        .search-row {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
    }

    @media (max-width: 600px) {
        .card-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .challenge-card {
            height: 280px;
        }
        
        .thumb {
            height: 140px;
        }
        
        .tabs {
            justify-content: center;
            gap: 10px;
        }
        
        .tab {
            padding: 10px 18px;
            font-size: 13px;
        }
        
        .section {
            padding: 25px;
        }
    }

    @media (max-width: 400px) {
        .section {
            padding: 20px;
        }
        
        .challenge-card {
            padding: 20px;
            height: 260px;
        }
    }
</style>
</head>

<body>
  <div class="layout">

    <!-- SIDEBAR (UPDATED to match your homepage sidebar structure/links) -->
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

          <a href="../WS/homepage.php" class="nav-btn" id="homepage-btn">
            <span class="icon">🏠</span><span>Homepage</span>
          </a>

          <a href="../WS/event.php" class="nav-btn">
            <span class="icon">📅</span><span>Event</span>
          </a>

          <!-- Set Challenge active on THIS page -->
          <a href="#" class="nav-btn active" id="nav-challenge">
            <span class="icon">🏆</span><span>Challenge</span>
          </a>

          <a href="../WS/reward_page.php" class="nav-btn">
            <span class="icon">📊</span><span>Reward</span>
          </a>

          <a href="../Lk/JourneyPlanner.php" class="nav-btn">
            <span class="icon">🗺️</span><span>Journey Planner</span>
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-title">Community</div>

          <a href="../LK/CarpoolList.php" class="nav-btn">
            <span class="icon">👥</span><span>Carpool List</span>
          </a>

          <a href="../RY/ranking_page.php" class="nav-btn">
            <span class="icon">🏆</span><span>Ranking</span>
          </a>

          <a href="../RY/news_page.php" class="nav-btn">
            <span class="icon">📰</span><span>News</span>
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-title">Tools</div>

          <a href="../LK/SmartParking.php" class="nav-btn">
            <span class="icon">🅿️</span><span>Smart Parking</span>
          </a>

          <a href="../LK/Ahm.php" class="nav-btn">
            <span class="icon">🎯</span><span>Achievement</span>
          </a>

          <a href="../RY/statistics.php" class="nav-btn">
            <span class="icon">📈</span><span>Statistic/Dashboard</span>
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

          <a href="profile.php" class="nav-btn">
            <span class="icon">👤</span><span>Profile</span>
          </a>

          <a href="../WS/logout.php" class="nav-btn">
            <span class="icon">🚪</span><span>Logout</span>
          </a>
        </div>

      </div>
    </nav>

    <!-- MAIN -->
    <main class="main">
      <div class="top-bar">
        <div class="page-title">
          <h2>Challenge</h2>
          <p>Discover and join new eco challenges 🌱</p>
        </div>
      </div>

      <hr>

      <!-- SEARCH / FILTER -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Search</h3>
        </div>

        <form method="GET" action="" class="search-form">
          <div class="search-row">
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search challenge..." />
            <button type="submit" class="view-btn">Search</button>
          </div>
        </form>

        <div class="tabs">
          <!-- 重置筛选 -->
          <a href="?" class="tab <?= empty($selectedType) && empty($searchTerm) ? 'active' : '' ?>">All</a>
          
          <!-- 根据数据库类型动态生成标签 -->
          <?php foreach ($challengeTypes as $type): ?>
            <?php if (!empty($type['Type'])): ?>
              <a href="?type=<?= urlencode($type['Type']) ?>" class="tab <?= $selectedType == $type['Type'] ? 'active' : '' ?>">
                <?= htmlspecialchars($type['Type']) ?>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- CHALLENGES -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Challenges</h3>
          <small>Found <?= count($challenges) ?> challenge<?= count($challenges) != 1 ? 's' : '' ?></small>
        </div>

        <div class="card-grid">
          <?php if (empty($challenges)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
              <p style="font-size: 18px; color: var(--text-light);">No challenges found. Try a different search.</p>
            </div>
          <?php else: ?>
            <?php foreach ($challenges as $c): ?>
              <div class="challenge-card">
                <div class="thumb">
                  <img src="/ASS-WDD/uploads/<?= htmlspecialchars($c['Photo_URL']) ?>"  alt="Challenge Image">
                </div>

                <p class="challenge-title"><?= htmlspecialchars($c['Name']) ?></p>
                <p class="challenge-description"><?= htmlspecialchars($c['Description']) ?></p>
                
                <!-- 显示挑战类型 -->
                <?php if (!empty($c['Type'])): ?>
                  <div class="challenge-type-tag"><?= htmlspecialchars($c['Type']) ?></div>
                <?php endif; ?>

                <a class="continue-btn"
                   href="chlngdetail.php?id=<?= (int)$c['Challenges_ID'] ?>">
                  View
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>