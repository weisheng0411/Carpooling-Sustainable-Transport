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

/* Get clicked challenge id */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("Invalid challenge id");

/* Current challenge */
$stmt = $mysqli->prepare("
  SELECT Challenges_ID, Name, Description, Point, Type, Published_date, Photo_URL
  FROM challenges
  WHERE Challenges_ID = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$challenge = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$challenge) { $mysqli->close(); die("Challenge not found"); }

/* Other challenges (exclude current) */
$otherStmt = $mysqli->prepare("
  SELECT Challenges_ID, Name, Photo_URL, Published_date
  FROM challenges
  WHERE Challenges_ID <> ?
  ORDER BY Published_date DESC
  LIMIT 6
");
$otherStmt->bind_param("i", $id);
$otherStmt->execute();
$other = $otherStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$otherStmt->close();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($challenge['Name']) ?> - GOBy</title>

    <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

  <style>
    /* 增强容器布局 */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* 增强顶部栏 */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-top: 20px;
    }

    .page-title h2 {
        font-size: 36px;
        color: var(--text-dark);
        margin: 0 0 10px 0;
        font-weight: 700;
        line-height: 1.2;
    }

    .page-title p {
        font-size: 16px;
        color: var(--text-light);
        margin: 0;
        opacity: 0.8;
    }

    /* 增强水平分隔线 */
    hr {
        border: none;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--light-green) 20%, var(--light-green) 80%, transparent);
        margin: 30px 0 50px 0;
        opacity: 0.5;
    }

    /* 主要详情网格布局 */
    .detail-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 40px;
        margin-bottom: 50px;
    }

    /* 增强卡片样式 */
    .section {
        background: var(--card);
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(46, 125, 50, 0.08);
        padding: 35px;
        border-left: 6px solid var(--light-green);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: fit-content;
    }

    .section:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(46, 125, 50, 0.12);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
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
        height: 28px;
        background: var(--light-green);
        border-radius: 4px;
        box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3);
    }

    /* 增强横幅图片 */
    .banner {
        height: 280px;
        border-radius: 16px;
        overflow: hidden;
        border: 2px solid rgba(46, 125, 50, 0.15);
        background: #eef6ee;
        box-shadow: 0 8px 25px rgba(46, 125, 50, 0.1);
        position: relative;
        margin-bottom: 30px;
        transition: all 0.4s ease;
    }

    .banner:hover {
        transform: scale(1.005);
        box-shadow: 0 12px 35px rgba(46, 125, 50, 0.15);
        border-color: var(--light-green);
    }

    .banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.6s ease;
    }

    .banner:hover img {
        transform: scale(1.05);
    }

    /* 增强数据统计 */
    .stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin: 30px 0;
    }

    .stat {
        border-radius: 16px;
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border: 2px solid rgba(46, 125, 50, 0.12);
        padding: 25px;
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, var(--light-green), var(--accent-green));
        border-radius: 6px 0 0 6px;
    }

    .stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(46, 125, 50, 0.1);
        border-color: var(--light-green);
    }

    .stat .label {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-medium);
        letter-spacing: 0.8px;
        margin-bottom: 12px;
        opacity: 0.8;
    }

    .stat .value {
        font-size: 32px;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* 增强信息块 */
    .block {
        margin-top: 30px;
        padding: 25px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border: 2px solid rgba(46, 125, 50, 0.12);
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.06);
        transition: all 0.3s ease;
    }

    .block:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(46, 125, 50, 0.1);
        border-color: var(--light-green);
    }

    .block h4 {
        margin: 0 0 15px 0;
        font-size: 15px;
        font-weight: 700;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .block h4::before {
        content: "•";
        color: var(--light-green);
        font-size: 20px;
    }

    .block p {
        margin: 0;
        color: var(--text-dark);
        line-height: 1.7;
        font-size: 16px;
        opacity: 0.9;
    }

    /* 增强操作按钮区域 */
    .actions {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 10px;
    }

    /* 增强按钮样式 */
    .continue-btn, .view-event-btn {
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-align: center;
        display: inline-block;
    }

    .continue-btn {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        padding: 18px 32px;
        border-radius: 12px;
        font-size: 16px;
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
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
        width: 400px;
        height: 400px;
    }

    .continue-btn:hover {
        background: linear-gradient(135deg, #43a047, #2e7d32);
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 30px rgba(46, 125, 50, 0.3);
    }

    .view-event-btn {
        background: linear-gradient(135deg, #ffffff, #f8f9f8);
        color: var(--text-dark);
        padding: 16px 28px;
        border-radius: 12px;
        font-size: 15px;
        box-shadow: 0 6px 15px rgba(46, 125, 50, 0.1);
        border: 2px solid var(--light-green);
        font-weight: 600;
    }

    .view-event-btn:hover {
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        color: white;
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(46, 125, 50, 0.2);
        border-color: transparent;
    }

    /* 其他挑战网格 */
    .other-challenges-section {
        margin-top: 50px;
    }

    .other-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 25px;
    }

    .other-card {
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border: 2px solid #c8e6c9;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .other-card::before {
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

    .other-card:hover::before {
        transform: scaleX(1);
    }

    .other-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        border-color: var(--light-green);
    }

    .other-thumb {
        height: 160px;
        border-radius: 12px;
        overflow: hidden;
        background: #eef6ee;
        border: 2px solid rgba(46, 125, 50, 0.15);
        position: relative;
        transition: transform 0.4s ease;
    }

    .other-card:hover .other-thumb {
        transform: scale(1.05);
    }

    .other-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.6s ease;
    }

    .other-card:hover .other-thumb img {
        transform: scale(1.1);
    }

    .other-title {
        margin: 0;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 16px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 44px;
    }

    .other-date {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
        opacity: 0.8;
    }

    .other-card .continue-btn {
        padding: 12px 24px;
        font-size: 14px;
        margin-top: auto;
        width: fit-content;
    }

    /* 无内容提示 */
    .no-content {
        text-align: center;
        padding: 40px;
        color: var(--text-light);
        font-size: 16px;
        opacity: 0.7;
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border-radius: 16px;
        border: 2px dashed rgba(46, 125, 50, 0.2);
    }

    /* 响应式设计 */
    @media (max-width: 1200px) {
        .detail-grid {
            gap: 30px;
        }
        
        .other-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .banner {
            height: 240px;
        }
    }

    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .banner {
            height: 220px;
        }
        
        .stats {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .other-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .section {
            padding: 30px;
        }
    }

    @media (max-width: 600px) {
        .container {
            padding: 0 15px;
        }
        
        .top-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .page-title h2 {
            font-size: 28px;
        }
        
        .section {
            padding: 25px;
        }
        
        .banner {
            height: 180px;
        }
        
        .other-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .stat {
            padding: 20px;
        }
        
        .stat .value {
            font-size: 28px;
        }
        
        .continue-btn, .view-event-btn {
            padding: 16px 24px;
            font-size: 15px;
        }
    }

    @media (max-width: 400px) {
        .section {
            padding: 20px;
        }
        
        .block {
            padding: 20px;
        }
        
        .stat {
            padding: 18px;
        }
        
        .continue-btn, .view-event-btn {
            padding: 14px 20px;
            font-size: 14px;
        }
    }
</style>
</head>

<body>
  <div class="container">

    <!-- TOP BAR -->
    <div class="top-bar">
      <div class="page-title">
        <h2><?= htmlspecialchars($challenge['Name']) ?></h2>
        <p>Challenge details and actions</p>
      </div>

      <div class="top-right">
        <a class="view-event-btn" href="Chlng.php">Back</a>
      </div>
    </div>

    <hr>

    <!-- MAIN DETAIL -->
    <div class="detail-grid">

      <!-- LEFT -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Overview</h3>
        </div>

        <div class="banner">
          <img src="/ASS-WDD/uploads/<?= htmlspecialchars($challenge['Photo_URL']) ?>" alt="Banner">
        </div>

        <div class="stats">
          <div class="stat">
            <div class="label">Total Points</div>
            <div class="value"><?= (int)$challenge['Point'] ?></div>
          </div>
          <div class="stat">
            <div class="label">Type</div>
            <div class="value"><?= htmlspecialchars($challenge['Type']) ?></div>
          </div>
        </div>

        <div class="block">
          <h4>Published Date</h4>
          <p><?= htmlspecialchars($challenge['Published_date']) ?></p>
        </div>

        <div class="block">
          <h4>Challenge Detail</h4>
          <p><?= nl2br(htmlspecialchars($challenge['Description'])) ?></p>
        </div>
      </section>

      <!-- RIGHT -->
      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Action</h3>
        </div>

        <div class="actions">
        <a class="continue-btn"
          href="/ASS-WDD/Aston/join_challenge.php?id=<?= (int)$challenge['Challenges_ID'] ?>">
          Join Challenge
        </a>

          <a class="view-event-btn" href="Chlng.php">
            Back to Challenges
          </a>
        </div>
      </section>

    </div>

    <!-- OTHER CHALLENGES -->
    <section class="section" style="margin-top:18px;">
      <div class="section-header">
        <h3 class="section-title">Other Challenges</h3>
      </div>

      <?php if (empty($other)): ?>
        <p style="color:#6b7280; margin:0;">No other challenges available.</p>
      <?php else: ?>
        <div class="other-grid">
          <?php foreach ($other as $o): ?>
            <a class="other-card"
               href="chlngdetail.php?id=<?= (int)$o['Challenges_ID'] ?>"
               style="text-decoration:none;color:inherit;">
              <div class="other-thumb">
                 <img src="/ASS-WDD/uploads/<?= htmlspecialchars($o['Photo_URL']) ?>"  alt="Challenge Image">
              </div>
              <p class="other-title"><?= htmlspecialchars($o['Name']) ?></p>
              <p class="other-date">Published: <?= htmlspecialchars(date('M d, Y', strtotime($o['Published_date']))) ?></p>
              <span class="continue-btn" style="width:fit-content;">View</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</body>
</html>
