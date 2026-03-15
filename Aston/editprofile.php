<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli("127.0.0.1","root","","wdd");
$mysqli->set_charset("utf8mb4");
if ($mysqli->connect_error) die("DB error");

$stmt = $mysqli->prepare("SELECT username, email, password, phone_number, photo_url FROM user_acc WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Edit Profile - GOBy</title>

    <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

  <style>
    /* Page-specific */
    .page-wrap{ max-width:1100px; margin:0 auto; padding:24px; }

    .profile-card{
      display:flex;
      gap:16px;
      align-items:center;
      flex-wrap:wrap;
    }

    .profile-photo{
      width:84px;
      height:84px;
      border-radius: 16px;
      overflow:hidden;
      border: 1px solid rgba(46,125,50,0.18);
      background:#eef6ee;
      box-shadow: 0 1px 8px rgba(46,125,50,0.06);
      flex: 0 0 auto;
    }
    .profile-photo img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }

    .profile-meta h4{
      margin:0 0 6px;
      font-size:18px;
      font-weight:900;
      color:#1b5e20;
      line-height:1.2;
    }
    .profile-meta p{
      margin:0;
      color:#6b7280;
      font-weight:800;
      font-size:13px;
    }

    .form-grid{
      display:grid;
      grid-template-columns: 1fr 180px;
      gap:12px;
      align-items:center;
      margin-top: 10px;
    }

    .form-grid .btn-col{
      justify-self: stretch;
      width:100%;
      text-align:center;
    }

    .hint{
      margin-top:10px;
      color:#6b7280;
      font-weight:800;
      font-size:13px;
    }

    .toast{
      display:none;
      margin-top:14px;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(46,125,50,0.18);
      background:#fff;
      box-shadow: 0 1px 8px rgba(46,125,50,0.06);
      color:#1b5e20;
      font-weight:900;
    }

    @media (max-width: 900px){
      .form-grid{ grid-template-columns: 1fr; }
      .form-grid .btn-col{ width: fit-content; }
    }
    @media (max-width: 600px){
      .page-wrap{ padding:16px; }
      .profile-photo{ width:72px; height:72px; }
      .form-grid .btn-col{ width:100%; }
    }
  </style>
</head>

<body>
  <div class="page-wrap">

    <div class="top-bar">
      <div class="page-title">
        <h2>Edit Profile</h2>
        <p>Update your account details</p>
      </div>

      <div class="top-right">
        <a class="view-event-btn" href="profile.php">Back</a>
        <a class="continue-btn" href="../WS/homepage.php">Home</a>
      </div>
    </div>

    <hr>

    <!-- PROFILE HEADER -->
    <section class="section">
      <div class="section-header">
        <h3 class="section-title">Your Profile</h3>
      </div>

      <div class="profile-card">
        <div class="profile-photo">
          <img src="<?= htmlspecialchars($user['photo_url'] ?: 'max.jpg') ?>" alt="Profile">
        </div>

        <div class="profile-meta">
          <h4>@<?= htmlspecialchars($user['username']) ?></h4>
          <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
      </div>

      <div id="toast" class="toast"></div>
    </section>

    <!-- PERSONAL INFO -->
    <section class="section">
      <div class="section-header">
        <h3 class="section-title">Personal Information</h3>
      </div>

      <div class="form-grid">
        <input type="text" id="usernameInput" placeholder="<?= htmlspecialchars($user['username']) ?>" onkeydown="handleEnter(event,'username','usernameInput')">
        <a class="continue-btn btn-col" href="#" onclick="updateUsername();return false;">Update</a>

        <input type="email" id="emailInput" placeholder="<?= htmlspecialchars($user['email']) ?>" onkeydown="handleEnter(event,'email','emailInput')">
        <a class="continue-btn btn-col" href="#" onclick="updateEmail();return false;">Update</a>

        <input type="password" id="passwordInput" placeholder="Enter New Password" onkeydown="handleEnter(event,'password','passwordInput')">
        <a class="continue-btn btn-col" href="#" onclick="updatePassword();return false;">Update</a>

        <input type="text" id="phoneInput" placeholder="<?= htmlspecialchars((string)$user['phone_number']) ?>" onkeydown="handleEnter(event,'phone_number','phoneInput')">
        <a class="continue-btn btn-col" href="#" onclick="updatePhone();return false;">Update</a>
      </div>

      <div class="hint">Tip: press <b>Enter</b> to update the field.</div>
    </section>

    <!-- OPTIONAL BIRTHDAY -->
    <section class="section">
      <div class="section-header">
        <h3 class="section-title">Birthday</h3>
      </div>
      <div style="font-weight:900;color:#1b5e20;">30 September 1997</div>
    </section>

    <div style="margin-top: 18px; display:flex; gap:12px; flex-wrap:wrap;">
      <a class="view-event-btn" href="profile.php">Back</a>
      <a class="continue-btn" href="../WS/homepage.php">Home</a>
    </div>
  </div>

  <script>
    function showToast(msg, ok=true){
      const t = document.getElementById("toast");
      t.style.display = "block";
      t.textContent = msg || "Done.";
      t.style.borderColor = ok ? "rgba(46,125,50,0.25)" : "rgba(220,38,38,0.25)";
      t.style.color = ok ? "#1b5e20" : "#b91c1c";
    }

    function updateField(field, inputId) {
      const value = document.getElementById(inputId).value.trim();
      if (!value) { showToast("Value cannot be empty", false); return; }

      fetch("update_profile.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
      })
      .then(res => res.text())
      .then(msg => showToast(msg || "Updated"))
      .catch(() => showToast("Update failed", false));
    }

    function updateUsername(){ updateField("username","usernameInput"); }
    function updateEmail(){ updateField("email","emailInput"); }
    function updatePassword(){ updateField("password","passwordInput"); }
    function updatePhone(){ updateField("phone_number","phoneInput"); }

    function handleEnter(e, field, inputId){
      if (e.key === "Enter"){ e.preventDefault(); updateField(field, inputId); }
    }
  </script>
</body>
</html>
