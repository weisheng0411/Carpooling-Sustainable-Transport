<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Other Services - GOBy</title>

  <!-- GOBy dashboard stylesheet -->
    <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

  <style>
    /* Page-specific */
    .service-grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:16px;
    }

    .service-card{
      background:#ffffff;
      border: 1px solid rgba(46,125,50,0.18);
      border-radius: 12px;
      padding: 18px;
      box-shadow: 0 1px 10px rgba(46,125,50,0.08);
      text-decoration:none;
      color: inherit;
      display:flex;
      flex-direction:column;
      gap:10px;
      transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .service-card:hover{
      transform: translateY(-3px);
      box-shadow: 0 14px 26px rgba(46,125,50,0.14);
      border-color: rgba(76,175,80,0.55);
    }

    .service-icon{
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background:#e8f5e9;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size: 22px;
      color:#1b5e20;
    }

    .service-title{
      margin:0;
      font-size: 16px;
      font-weight: 900;
      color:#1b5e20;
    }

    .service-desc{
      margin:0;
      color:#6b7280;
      font-weight: 800;
      font-size: 13px;
      line-height: 1.45;
      flex:1;
    }

    .service-action{
      margin-top: 6px;
      display:flex;
      justify-content:flex-start;
    }

    @media (max-width: 900px){
      .service-grid{ grid-template-columns: 1fr; }
    }
  </style>
</head>

<body>
  <div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="homepage.php" class="sidebar-logo">
          <div class="logo-icon">🌱</div>
          <span>GOBy</span>
        </a>
      </div>

      <div class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-title">Admin</div>
          <a href="otherservices.php" class="nav-btn active"><span class="icon">🧰</span><span>Other Services</span></a>
          <a href="driververification.php" class="nav-btn"><span class="icon">✅</span><span>Driver Verification</span></a>
          <a href="eventsubmission.php" class="nav-btn"><span class="icon">📨</span><span>Event Submission</span></a>
        </div>

        <div class="nav-section">
          <div class="nav-title">Account</div>
          <a href="profile.php" class="nav-btn"><span class="icon">👤</span><span>Profile</span></a>
          <a href="logout.php" class="nav-btn"><span class="icon">🚪</span><span>Logout</span></a>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <div class="top-bar">
        <div class="page-title">
          <h2>Other Services</h2>
          <p>Admin tools for managing requests and submissions</p>
        </div>

        <div class="top-right">
          <a class="view-event-btn" href="homepage.php">Homepage</a>
        </div>
      </div>

      <hr>

      <section class="section">
        <div class="section-header">
          <h3 class="section-title">Available Services</h3>
        </div>

        <div class="service-grid">
          <a class="service-card" href="driververification.php">
            <div class="service-icon">✅</div>
            <h3 class="service-title">Driver Verification</h3>
            <p class="service-desc">Review pending driver applications and approve or reject.</p>
            <div class="service-action">
              <span class="continue-btn">Open</span>
            </div>
          </a>

          <a class="service-card" href="eventsubmission.php">
            <div class="service-icon">📨</div>
            <h3 class="service-title">Event Submission</h3>
            <p class="service-desc">Check event submission requests and process approvals.</p>
            <div class="service-action">
              <span class="continue-btn">Open</span>
            </div>
          </a>
        </div>
      </section>

      <div style="margin-top: 18px; display:flex; gap:12px; flex-wrap:wrap;">
        <a class="view-event-btn" href="homepage.php">Home</a>
        <a class="continue-btn" href="profile.php">Profile</a>
      </div>
    </main>

  </div>
</body>
</html>
