<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$sql = "SELECT carpool_list.cl_id,
            carpool_list.from_place,
            carpool_list.to_place,
            user_acc.name,
            user_acc.phone_number,
            user_acc.photo_url,
            travel_log.`Points_earned` as points
        FROM carpool_list
        JOIN user_acc ON carpool_list.driver_id = user_acc.user_id
        LEFT JOIN travel_log ON carpool_list.cl_id = travel_log.CL_ID
        GROUP BY carpool_list.cl_id";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rides</title>
    <style>

        * { box-sizing: border-box; }

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
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        a { text-decoration: none; }

        .container { 
            display: flex; 
            min-height: 100vh; 
            position: relative; 
            overflow-x: hidden; 
        }

        .main { 
            margin-left: 0; 
            padding: 30px; 
            width: 100%; 
        }

        .header{
            display: flex;
            align-items: center;       
            justify-content: center;   
            gap: 20px;                 
            position: relative;
        }

        .backBtn{
            position:absolute;
            left:0%;
            border:none;
            font-size:30px;
            cursor:pointer;
            background: var(--background);
        }

        hr {
            height: 3px;
            background: var(--eco-green);
            border: none;
        }

        .rides-container {
            display: grid;
            gap: 25px;
            grid-template-columns: 1fr; 
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block; 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--eco-green);
            min-height: 160px;
        }

        .card-inner {
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .card-link:active { 
            transform: scale(0.98); 
            background-color: #f9f9f9; 
        }

        @media (hover: hover) {
            .card-link:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            }
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .driver-info-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .avatar-circle {
            width: 55px;
            height: 55px;
            background-color: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--eco-green);
            border: 2px solid var(--light-green);
            overflow: hidden;
            flex-shrink: 0;
        }

        .info-text p {
            margin: 3px 0;
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.4;
        }
        
        .info-text strong {
            color: var(--text-dark);
            font-size: 16px;
        }

        .point-badge {
            background-color: var(--eco-green);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .destination-box {
            background-color: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .destination-label {
            font-weight: bold;
            color: var(--text-dark);
            font-size: 15px;
            word-break: break-word; 
        }
        
        .destination-icon {
            color: #d32f2f;
            flex-shrink: 0;
        }

        @media (min-width: 1100px) {
            .rides-container { grid-template-columns: repeat(3, 1fr); }
        }

        @media (min-width: 768px) and (max-width: 1099px) {
            .rides-container { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .main { padding: 15px; }

            .rides-container { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <div class="container">
        <main class="main">
            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='../Aston/profile.php'">←</button>
                <h2>My Rides</h2>
            </div>
            <hr><br>

            <div class="rides-container">
                
                <?php
                while($row = mysqli_fetch_assoc($result)) {
                    
                    $points = $row['points'] ? $row['points'] : 0;
                    $ride_id = $row['cl_id'];
                ?>

                <a href="Rides Detail.php?id=<?php echo $ride_id; ?>" class="card-link">
                    <div class="card-inner">
                        <div class="card-top">
                            <div class="driver-info-group">
                                <div class="avatar-circle">
                                    <?php if($row['photo_url']) { ?>
                                        <img src="<?php echo $row['photo_url']; ?>" alt="Driver" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php } else { ?>
                                        👨‍✈️
                                    <?php } ?>
                                </div>
                                <div class="info-text">
                                    <p><strong><?php echo $row['name']; ?></strong></p>
                                    <p>📞 <?php echo $row['phone_number']; ?></p>
                                    <p>⭐ 5.0/5.0</p>
                                </div>
                            </div>
                            <div class="point-badge"><?php echo $points; ?> pts</div>
                        </div>
                        <div class="destination-box">
                            <span class="destination-icon">📍</span>
                            <span class="destination-label">
                                <?php echo $row['from_place']; ?> ➝ <?php echo $row['to_place']; ?>
                            </span>
                        </div>
                    </div>
                </a> 

                <?php 
                } 
                ?>

            </div>

        </main>
    </div>

</body>
</html>