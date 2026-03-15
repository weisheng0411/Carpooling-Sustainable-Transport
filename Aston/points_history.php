<?php
// points_history.php  (GOBy dashboard style - NO SIDEBAR)
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

// user points
$stmt = $mysqli->prepare("SELECT current_point, summary_point, name, photo_url FROM user_acc WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$user) { $mysqli->close(); die("Invalid user"); }

// points history
$history = [];

// 1. travel_log (earned)
$q1 = $mysqli->prepare("
  SELECT TL_ID AS ref_id, Date AS dt,
         CONCAT('Travel Log: +', Points_earned, ' pts') AS title,
         Points_earned AS delta,
         'travel_log' AS src
  FROM travel_log
  WHERE User_ID = ?
");
$q1->bind_param("i", $userId);
$q1->execute();
$r1 = $q1->get_result();
while($row = $r1->fetch_assoc()) $history[] = $row;
$q1->close();

// 2. achievements bonus (optional)
$q2 = $mysqli->prepare("
  SELECT Achievements_ID AS ref_id,
         COALESCE(End_date, Start_date) AS dt,
         CONCAT('Achievement Bonus: +', COALESCE(Bonus_point,0), ' pts') AS title,
         COALESCE(Bonus_point,0) AS delta,
         'achievements' AS src
  FROM achievements
  WHERE User_ID = ? AND Bonus_point IS NOT NULL
");
$q2->bind_param("i", $userId);
$q2->execute();
$r2 = $q2->get_result();
while($row = $r2->fetch_assoc()) $history[] = $row;
$q2->close();

// 3. events joined
$q3 = $mysqli->prepare("
  SELECT e.Event_ID AS ref_id,
         DATE(e.Start_Date) AS dt,
         CONCAT('Event: ', e.Name, ' ( +', e.Points, ' pts )') AS title,
         e.Points AS delta,
         'event' AS src
  FROM registration r
  JOIN event_details ed ON ed.Reg_ID = r.Reg_ID
  JOIN event e ON e.Event_ID = ed.Event_ID
  WHERE r.User_ID = ?
");
$q3->bind_param("i", $userId);
$q3->execute();
$r3 = $q3->get_result();
while($row = $r3->fetch_assoc()) $history[] = $row;
$q3->close();

// 4. 新增：兑换奖励记录 (redeem_record - points spent)
$q4 = $mysqli->prepare("
  SELECT rr.RR_ID AS ref_id,
         rr.Date AS dt,
         CONCAT('Redeem: ', i.Name, ' (x', rr.Quantity, ')') AS title,
         -rr.Points_spent AS delta,  -- 注意：这里是负数，因为是花费点数
         'redeem' AS src,
         i.Name AS item_name,
         rr.Quantity AS quantity
  FROM redeem_record rr
  JOIN items i ON i.Items_ID = rr.Items_ID
  WHERE rr.User_ID = ?
");
$q4->bind_param("i", $userId);
$q4->execute();
$r4 = $q4->get_result();
while($row = $r4->fetch_assoc()) $history[] = $row;
$q4->close();

// sort newest first
usort($history, function($a,$b){
  return strcmp((string)$b["dt"], (string)$a["dt"]);
});

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Points History - GOBy</title>

  <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

  <style>
    /* 页面容器 */
    .page-wrap {
        max-width: 1100px;
        margin: 60px auto;
        padding: 0 24px;
    }

    /* 顶部标题区域 */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-top: 20px;
    }

    .page-title h2 {
        font-size: 32px;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        font-weight: 700;
    }

    .page-title p {
        font-size: 15px;
        color: var(--text-light);
        margin: 0;
        opacity: 0.8;
    }

    .top-right {
        display: flex;
        gap: 12px;
    }

    /* 水平分隔线 */
    hr {
        border: none;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--light-green) 20%, var(--light-green) 80%, transparent);
        margin: 20px 0 40px 0;
        opacity: 0.5;
    }

    /* 卡片样式 */
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

    /* 头像和信息区域 */
    .profile-head {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .avatar {
        width: 70px;
        height: 70px;
        border-radius: 16px;
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

    .name {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1.2;
    }

    .sub {
        margin-top: 8px;
        color: var(--text-light);
        font-weight: 600;
        font-size: 14px;
    }

    /* 点数统计 */
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
        padding: 22px;
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
        margin-bottom: 12px;
        opacity: 0.9;
    }

    .pill .v {
        font-size: 32px;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* 历史记录列表 */
    .history-list {
        margin-top: 20px;
        display: grid;
        gap: 15px;
    }

    .row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        padding: 22px;
        border-radius: 14px;
        background: linear-gradient(135deg, #f8f9f8, #ffffff);
        border: 2px solid rgba(46, 125, 50, 0.12);
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .row:hover {
        transform: translateX(5px) translateY(-3px);
        box-shadow: 0 12px 30px rgba(46, 125, 50, 0.12);
        border-color: var(--light-green);
    }

    .row::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: var(--light-green);
        border-radius: 5px 0 0 5px;
    }

    .row .l {
        min-width: 0;
        flex: 1;
    }

    .row .t {
        font-weight: 700;
        color: var(--text-dark);
        font-size: 16px;
        line-height: 1.4;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        max-width: 700px;
    }

    .row .d {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
        opacity: 0.9;
    }

    /* 点数变化显示 */
    .delta {
        font-weight: 800;
        white-space: nowrap;
        padding: 10px 16px;
        border-radius: 25px;
        border: 2px solid transparent;
        font-size: 14px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .delta:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }

    .pos {
        color: #166534;
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        border-color: #a5d6a7;
    }

    .neg {
        color: #b91c1c;
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        border-color: #ef9a9a;
    }

    /* 兑换记录特殊样式 */
    .row.redeem-row::before {
        background: #f59e0b; /* 橙色表示兑换 */
    }

    .redeem-info {
        font-size: 13px;
        color: #92400e;
        background: #fef3c7;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 4px;
        font-weight: 600;
    }

    .view-event-btn {
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-align: center;
        padding: 14px 28px;
        border-radius: 12px;
        font-size: 15px;
        display: inline-block;
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15);
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

    /* 新增兑换记录按钮样式 */
    .view-redeem-btn {
        background: linear-gradient(135deg, #ffffff, #f8f9f8);
        color: var(--text-dark);
        border: 2px solid #f59e0b;
    }

    .view-redeem-btn:hover {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(245, 158, 11, 0.2);
        border-color: transparent;
    }

    /* 响应式设计 */
    @media (max-width: 900px) {
        .page-wrap {
            padding: 0 20px;
        }
        
        .row .t {
            max-width: 500px;
        }
    }

    @media (max-width: 768px) {
        .top-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .top-right {
            width: 100%;
            justify-content: flex-start;
        }
        
        .mini {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .row {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        
        .row .t {
            max-width: 100%;
            white-space: normal;
        }
        
        .delta {
            align-self: flex-start;
        }
    }

    @media (max-width: 480px) {
        .page-wrap {
            padding: 0 16px;
        }
        
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
        
        .pill {
            padding: 20px;
        }
        
        .pill .v {
            font-size: 28px;
        }
        
        .view-event-btn {
            padding: 12px 24px;
            font-size: 14px;
        }
        
        .row {
            padding: 20px;
        }
    }
  </style>
</head>

<body>
  <div class="page-wrap">

    <div class="top-bar">
      <div class="page-title">
        <h2>Points History</h2>
        <p>Your points overview and earning history</p>
      </div>

      <div class="top-right">
        <a class="view-event-btn" href="profile.php">Back to Profile</a>
      </div>
    </div>

    <hr>

    <!-- OVERVIEW -->
    <section class="section">
      <div class="section-header">
        <h3 class="section-title">Overview</h3>
      </div>

      <div class="profile-head">
        <div class="avatar">
          <img src="/ASS-WDD/<?= htmlspecialchars($user["photo_url"] ?: 'max.jpg') ?>" alt="Profile">
        </div>
        <div>
          <p class="name"><?= htmlspecialchars($user["name"]) ?></p>
          <div class="sub">Your latest points and activity</div>
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

    <!-- HISTORY -->
    <section class="section">
      <div class="section-header">
        <h3 class="section-title">Points History</h3>
        <div class="sub">Green = Earned, Red = Spent</div>
      </div>

      <?php if(empty($history)): ?>
        <div style="color:#6b7280;font-weight:800;">No points history found.</div>
      <?php else: ?>
        <div class="history-list">
          <?php foreach($history as $h): ?>
            <?php 
            $delta = (int)$h["delta"];
            $isRedeem = ($h["src"] ?? '') === 'redeem';
            ?>
            <div class="row <?= $isRedeem ? 'redeem-row' : '' ?>">
              <div class="l">
                <div class="t"><?= htmlspecialchars($h["title"]) ?></div>
                <div class="d"><?= htmlspecialchars((string)$h["dt"]) ?></div>
                <?php if($isRedeem && isset($h["quantity"])): ?>
                  <div class="redeem-info">Quantity: <?= htmlspecialchars((string)$h["quantity"]) ?></div>
                <?php endif; ?>
              </div>

              <div class="delta <?= $delta >= 0 ? "pos" : "neg" ?>">
                <?= $delta >= 0 ? "+" : "" ?><?= $delta ?> pts
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</body>
</html>