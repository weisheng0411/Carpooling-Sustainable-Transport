
<?php
    session_start();
    
    include("conn.php");
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }

    $id = $_SESSION['user_id'];
     
    $sql = "SELECT * FROM user_acc where user_id = $id";
    $result = mysqli_query($con,$sql);
    $row = mysqli_fetch_array($result);

    $sql_D = "SELECT * FROM driver where user_id = $id";
    $result_D = mysqli_query($con,$sql_D);
    $row_D = mysqli_fetch_array($result_D);

    $sql_R = "SELECT * FROM organizer where user_id = $id";
    $result_R = mysqli_query($con,$sql_R);
    $row_R = mysqli_fetch_array($result_R);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile Menu</title>

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
    --shadow: 0 4px 12px rgba(46, 125, 50, 0.15);
    --border-radius: 15px;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: var(--background);
        display: flex;
        justify-content: center;
        padding: 40px 20px;
    }

    .container {
        background: var(--card-bg);
        width: 95%;
        max-width: 900px;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    h2 {
        font-size: 26px;
        font-weight: 700;
        text-align: center;
        color: var(--text-dark);
        margin-bottom: 20px;
    }

    hr {
        height: 3px;
        background: var(--eco-green);
        border: none;
        margin: 20px 0;
    }

    /* ---- Card Layout (Vertical for all screens) ---- */
    .card-wrapper {
        display: flex;
        flex-direction: column; /* 竖排 */
        gap: 20px; /* 卡片间距 */
    }

    /* ---- Individual Card ---- */
    .card {
        background: var(--card-bg);
        padding: 25px 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(46, 125, 50, 0.2);
    }

    .card-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--eco-green);
    }

    /* ---- Buttons ---- */
    .card button {
        background: var(--eco-green);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        padding: 12px;
        cursor: pointer;
        transition: all 0.25s ease;
        width: 70%;
        margin: 0 auto;
    }

    .card button:hover {
        background: var(--light-green);
        transform: translateY(-3px);
        box-shadow: 0 8px 12px rgba(0,0,0,0.2);
    }

    /* ---- Back Button ---- */
    .backBtn {
        display: block;
        margin: 20px auto 0 auto;
        width: 35%;
        padding: 12px;
        font-size: 16px;
        font-weight: 700;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        background: black;
        color: white;
        transition: all 0.25s ease;
    }

    .backBtn:hover {
        opacity: 0.85;
        transform: translateY(-2px);
    }

    /* ---- Mobile View ---- */
    @media (max-width: 700px) {
        .card-wrapper {
            flex-direction: column;
            gap: 20px;
        }

        .card {
            width: 100%;
            height: auto;
        }

        .card button {
            width: 60%;
            font-size: 14px;
        }

        .backBtn {
            width: 50%;
        }
    }    
</style>

</head>
<body>
    <div class="container">
        <h2>Edit Profile</h2><hr>

        <div class="card-wrapper">
            <div class="card">
                <p class="card-title">Own Profile</p>
                <button onclick="location.href='OwnProfile.php'">View</button>
            </div>

            <?php if(!empty($row_D) && $row_D['Status'] == 'pass'){ ?>
                <div class="card">
                    <p class="card-title">Driver Profile</p>
                    <button onclick="location.href='DriverProfile.php'">View</button>
                </div>
            <?php } ?>
            
            <?php if(!empty($row_R) && $row_R['Status'] == 'pass'){ ?>
                <div class="card">
                    <p class="card-title">Event Organizer Profile</p>
                    <button onclick="location.href='OrganizerProfile.php'">View</button>
                </div>
            <?php } ?>

        </div>
        <br>
        <button class='backBtn' type='button' onclick='window.location.href="../Aston/profile.php"'>Back</button>
    </div>

</body>
</html>
