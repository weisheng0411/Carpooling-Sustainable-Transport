<?php
session_start();
include("../LK/conn.php");



if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$cl_id = isset($_GET['cl_id']) ? (int)$_GET['cl_id'] : 0;

if ($cl_id <= 0) {
    die("Invalid ride ID.");
}

/**
 * Fetch ride details (destination) from Carpool_List
 * NOTE: Column names based on your screenshot: cl_id, from_place, to_place, date, time, seats, bording_point, status_open_close
 */
$stmt = mysqli_prepare($con, "SELECT cl_id, from_place, to_place, date, time, bording_point, status_open_close, driver_id 
FROM Carpool_List 
WHERE cl_id = ? 
LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $cl_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    die("Ride not found.");
}

$ride = mysqli_fetch_assoc($res);

// Optional: prevent navigating to closed rides
// if (isset($ride['status_open_close']) && $ride['status_open_close'] !== 'open') {
//     die("This ride is closed.");
// }

$from_place = $ride['from_place'] ?? '';
$to_place   = $ride['to_place'] ?? '';
$date       = $ride['date'] ?? '';
$time       = $ride['time'] ?? '';
$boarding   = $ride['bording_point'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Ride Navigation</title>
    <script>
    function informDriver() {
      alert("Driver Informed");
    }
  </script>


  <style>
    :root{
      --eco-green:#2e7d32;
      --eco-dark:#1b5e20;
      --bg:#f1f8e9;
      --card:#ffffff;
      --muted:#6b7280;
      --shadow: 0 10px 25px rgba(0,0,0,.10);
      --radius:18px;
    }
    *{box-sizing:border-box;}
    body{
      margin:0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: var(--bg);
      color:#0f172a;
    }

    /* Phone-like layout */
    .wrap{
      min-height: 100vh;
      display:flex;
      flex-direction:column;
    }

    .topbar{
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 14px 14px 10px;
      background: linear-gradient(180deg, rgba(241,248,233,1) 0%, rgba(241,248,233,.92) 70%, rgba(241,248,233,0) 100%);
      backdrop-filter: blur(10px);
    }

    .topbar-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
    }

    .back-btn{
      border:none;
      background: white;
      color: var(--eco-green);
      width: 44px;
      height: 44px;
      border-radius: 14px;
      box-shadow: var(--shadow);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
      cursor:pointer;
    }

    .title{
      flex:1;
      min-width:0;
    }
    .title h2{
      margin:0;
      font-size:16px;
      font-weight:800;
      color: var(--eco-dark);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .title p{
      margin:3px 0 0 0;
      font-size:12px;
      color: var(--muted);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    .map{
      flex:1;
      padding: 0 14px;
    }
    iframe{
      width:100%;
      height: calc(100vh - 220px);
      border: 0;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      background: #eaf3ea;
    }

    .sheet{
      position: sticky;
      bottom: 0;
      padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
      background: linear-gradient(180deg, rgba(241,248,233,0) 0%, rgba(241,248,233,.92) 20%, rgba(241,248,233,1) 100%);
      backdrop-filter: blur(10px);
    }

    .card{
      background: var(--card);
      border-radius: 22px;
      box-shadow: var(--shadow);
      padding: 14px;
      border: 1px solid rgba(46,125,50,.12);
    }

    .ride-meta{
      display:flex;
      justify-content:space-between;
      gap:10px;
      align-items:flex-start;
      margin-bottom: 12px;
    }

    .route{
      min-width:0;
    }
    .route .fromto{
      font-weight:800;
      color: var(--eco-dark);
      font-size:14px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .route .sub{
      margin-top:4px;
      font-size:12px;
      color: var(--muted);
      line-height:1.3;
    }

    .badge{
      flex-shrink:0;
      background: #e8f5e9;
      color: var(--eco-green);
      border: 1px solid rgba(46,125,50,.18);
      font-weight:800;
      font-size:12px;
      padding: 8px 10px;
      border-radius: 999px;
      display:flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }

    .btn-row{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:10px;
      margin-top:10px;
    }

    .btn{
      border:none;
      border-radius: 14px;
      padding: 12px 14px;
      font-weight:800;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn:active{ transform: scale(.98); }

    .btn-primary{
      background: linear-gradient(135deg, #4caf50, #2e7d32);
      color:white;
      box-shadow: 0 10px 20px rgba(46,125,50,.22);
    }
    .btn-secondary{
      background:#f6fff6;
      border:1px solid rgba(46,125,50,.18);
      color: var(--eco-green);
    }

    .hint{
      margin-top:8px;
      font-size:11px;
      color: var(--muted);
      text-align:center;
    }
  </style>
</head>
<body>
<div class="wrap">

  <!-- TOP BAR -->
  <div class="topbar">
    <div class="topbar-row">
      <button class="back-btn" onclick="history.back()" aria-label="Back">←</button>
      <div class="title">
        <h2>Ride Navigation</h2>
        <p><?php echo htmlspecialchars($from_place); ?> → <?php echo htmlspecialchars($to_place); ?></p>
      </div>
      <div style="width:44px;"></div>
    </div>
  </div>

  <!-- MAP -->
  <div class="map">
    <!-- Default map (destination only). JS will upgrade to directions once GPS is allowed. -->
    <iframe
      id="mapFrame"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      src="https://www.google.com/maps?q=<?php echo urlencode($to_place); ?>&output=embed">
    </iframe>
  </div>

  <!-- BOTTOM SHEET -->
  <div class="sheet">
    <div class="card">
      <div class="ride-meta">
        <div class="route">
          <div class="fromto"><?php echo htmlspecialchars($from_place); ?> → <?php echo htmlspecialchars($to_place); ?></div>
          <div class="sub">
            📅 <?php echo htmlspecialchars($date); ?> &nbsp; ⏰ <?php echo htmlspecialchars($time); ?><br>
            📍 Boarding: <?php echo htmlspecialchars($boarding ?: '—'); ?>
          </div>
        </div>
        <div class="badge" id="etaBadge">🕒 ETA: —</div>
      </div>

      <div class="btn-row">
        <button class="btn btn-primary" onclick="startNavigation()">
          🧭 Navigate
        </button>
        <button class="btn btn-secondary" onclick="informDriver()">
        📢 Inform Driver
      </button>

        <a class="btn btn-secondary" href="../LK/ChatWithGroup.php?cl_id=<?php echo $cl_id; ?>">
          💬 Chat with group
        </a>
        <a class="btn btn-primary" href="../LK/Feedback(only phone).php?cl_id=<?php echo $cl_id; ?>">
          ✅ Finish & Leave Feedback
        </a>
      </div>
      <div class="hint" id="hint">
        Tap <b>Navigate</b> to show the route to <b><?php echo htmlspecialchars($to_place); ?></b>. If asked, allow location for best results.
      </div>
    </div>
  </div>

</div>

<script>
  const DESTINATION = <?php echo json_encode($to_place); ?>;
  const CL_ID = <?php echo (int)$cl_id; ?>;

  function setMapToPlace(place) {
    const url = "https://www.google.com/maps?q=" + encodeURIComponent(place) + "&output=embed";
    document.getElementById("mapFrame").src = url;
  }

  // Embed directions using current GPS as origin (works without API key).
  function setMapToDirections(lat, lng, destination) {
    const saddr = lat + "," + lng;
    const url = "https://www.google.com/maps?saddr=" + encodeURIComponent(saddr)
              + "&daddr=" + encodeURIComponent(destination)
              + "&output=embed";
    document.getElementById("mapFrame").src = url;
  }

  // Open native Google Maps directions (better UX like Grab).
  function openNativeDirections(lat, lng, destination) {
    const origin = lat + "," + lng;
    const url = "https://www.google.com/maps/dir/?api=1"
              + "&origin=" + encodeURIComponent(origin)
              + "&destination=" + encodeURIComponent(destination)
              + "&travelmode=driving";
    window.open(url, "_blank");
  }

  function startNavigation() {
    if (!DESTINATION) {
      alert("Destination is missing for this ride.");
      return;
    }

    // Try GPS first
    if (!navigator.geolocation) {
      setMapToPlace(DESTINATION);
      return;
    }

    document.getElementById("hint").textContent = "Getting your location…";

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        // Update embedded map to directions
        setMapToDirections(lat, lng, DESTINATION);

        // Optional: also open native directions (comment out if you only want iframe)
        // openNativeDirections(lat, lng, DESTINATION);

        document.getElementById("hint").textContent = "Route loaded. Drive safe!";
        // We cannot get real ETA without Google Directions API key, so keep it simple:
        document.getElementById("etaBadge").textContent = "🧭 Route ready";
      },
      (err) => {
        // If user denies location, fallback to destination-only map
        setMapToPlace(DESTINATION);
        document.getElementById("hint").textContent = "Location not available. Showing destination on map.";
        document.getElementById("etaBadge").textContent = "🕒 ETA: —";
      },
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );
  }

  function finishRide() {
    // Change these filenames to your real pages if different:
    window.location.href = "Feedback.php?cl_id=" + encodeURIComponent(CL_ID);
  } 

  function goChat() {
    // Change these filenames to your real pages if different:
    window.location.href = "ChatWithGroup.php?cl_id=" + encodeURIComponent(CL_ID);
  }

  // Default behavior: show destination map right away
  if (DESTINATION) setMapToPlace(DESTINATION);
</script>
</body>
</html>
