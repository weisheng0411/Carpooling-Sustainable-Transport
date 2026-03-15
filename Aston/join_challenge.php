
<?php
/* join_challenge.php
   - user clicks "Join Challenge" from chlngdetail.php
   - we insert a participation row into achievements table (your screenshot)
   - then show "Participation successful" page

   NOTE: This assumes you use achievements table to track challenge participation:
   Achievements_ID, Progress, Start_date, End_date, Bonus_point, Status, User_ID, Challenges_ID
*/

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli("127.0.0.1","root","","wdd",3306);
$mysqli->set_charset("utf8mb4");

$userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0;
if ($userId <= 0) { header("Location: login.php"); exit; }

$challengeId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$challengeId) die("Invalid challenge id");

/* 1) make sure challenge exists */
$stmt = $mysqli->prepare("SELECT Challenges_ID, Name, Photo_URL FROM challenges WHERE Challenges_ID=? LIMIT 1");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$challenge = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$challenge) { $mysqli->close(); die("Challenge not found"); }

/* 2) prevent duplicate join (already ongoing/completed for this user+challenge) */
$stmt = $mysqli->prepare("
  SELECT Achievements_ID, Status
  FROM achievements
  WHERE User_ID = ? AND Challenges_ID = ?
  ORDER BY Achievements_ID DESC
  LIMIT 1
");
$stmt->bind_param("ii", $userId, $challengeId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$alreadyJoined = false;
if ($existing && in_array(strtolower($existing["Status"]), ["ongoing","completed"], true)) {
  $alreadyJoined = true;
}

/* 3) if not joined, insert new participation row */
$inserted = false;
if (!$alreadyJoined) {
  $today = date("Y-m-d");
  $progress = 0;
  $status = "ongoing";
  $bonus = null;   // keep NULL until completed (or you can set 0)

  $stmt = $mysqli->prepare("
    INSERT INTO achievements (Progress, Start_date, End_date, Bonus_point, Status, User_ID, Challenges_ID)
    VALUES (?, ?, NULL, ?, ?, ?, ?)
  ");
  // Bonus_point can be NULL => bind as "i" needs value, so we bind as null using "s" trick? easiest: set to NULL in SQL directly:
  $stmt->close();

  // Safer: write SQL with Bonus_point = NULL explicitly
  $stmt = $mysqli->prepare("
    INSERT INTO achievements (Progress, Start_date, End_date, Bonus_point, Status, User_ID, Challenges_ID)
    VALUES (?, ?, NULL, NULL, ?, ?, ?)
  ");
  $stmt->bind_param("issii", $progress, $today, $status, $userId, $challengeId);
  $stmt->execute();
  $stmt->close();

  $inserted = true;
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Join Challenge - GOBy</title>
  <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">
  <style>
    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg);
        padding: 20px;
    }

    .container {
        max-width: 900px;
        width: 100%;
    }

    .section {
        background: var(--card);
        border-radius: 12px;
        box-shadow: var(--shadow);
        padding: 30px;
        border-left: 4px solid var(--light-green);
        /* 移除 margin-top: 250px; */
    }

    /* 信息展示区域 */
    .hero {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .thumb {
        width: 100px;
        height: 100px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(46,125,50,0.15);
        background: #f1f8e9;
        flex: 0 0 auto;
    }

    .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .msg h2 {
        margin: 0 0 10px 0;
        color: var(--text-dark);
        font-size: 20px;
        font-weight: 600;
    }

    .msg p {
        margin: 0;
        color: var(--text-light);
        font-size: 14px;
        line-height: 1.5;
    }

    /* 按钮区域 */
    .btnrow {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 30px;
    }

    /* 按钮样式 */
    .continue-btn, .view-event-btn {
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.2s ease;
        text-align: center;
        padding: 14px 20px;
        border-radius: 8px;
        font-size: 14px;
        display: block;
    }

    .continue-btn {
        background: var(--light-green);
        color: white;
    }

    .continue-btn:hover {
        background: var(--eco-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46,125,50,0.2);
    }

    .view-event-btn {
        background: white;
        color: var(--text-dark);
        border: 1px solid var(--light-green);
    }

    .view-event-btn:hover {
        background: #f1f8e9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46,125,50,0.1);
    }

    /* 响应式设计 */
    @media (max-width: 768px) {
        body {
            padding: 15px;
        }
        
        .hero {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .thumb {
            width: 80px;
            height: 80px;
        }
        
        .btnrow {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .continue-btn, .view-event-btn {
            padding: 12px 16px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .section {
            padding: 20px;
        }
    }
</style>
</head>
<body>
  <div class="container success-wrap">

    <section class="section">
      <div class="hero">
        <div class="thumb">
          <img src="/ASS-WDD/uploads/<?= htmlspecialchars($challenge["Photo_URL"]) ?>" alt="Challenge">
        </div>
        <div class="msg">
          <?php if ($alreadyJoined): ?>
            <h2>You're already in this challenge ✅</h2>
            <p><?= htmlspecialchars($challenge["Name"]) ?> is already <b><?= htmlspecialchars($existing["Status"]) ?></b> for you.</p>
          <?php else: ?>
            <h2>Participation successful ✅</h2>
            <p>You have joined: <b><?= htmlspecialchars($challenge["Name"]) ?></b></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="btnrow">
        <a class="continue-btn" href="chlngdetail.php?id=<?= (int)$challengeId ?>">Back to Detail</a>
        <a class="view-event-btn" href="Chlng.php">Back to Challenges</a>
        <a class="view-event-btn" href="../LK/Ahm.php">View My Achievements</a>
      </div>
    </section>
  </div>
</body>
</html>