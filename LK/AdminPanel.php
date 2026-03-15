<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

/* Total users */
$sql_total_users = "SELECT COUNT(*) AS total FROM user_acc";
$result_total_users = mysqli_query($con, $sql_total_users);
$row_total_users = mysqli_fetch_assoc($result_total_users);
$total_users = $row_total_users['total'];

/* Pending events */
$sql_pending_events = "SELECT COUNT(*) AS total FROM event WHERE LOWER(Status) = 'pending'";
$result_pending_events = mysqli_query($con, $sql_pending_events);
$row_pending_events = mysqli_fetch_assoc($result_pending_events);
$pending_events = $row_pending_events['total'];

/* Pending organizers */
$sql_pending_organizers = "SELECT COUNT(*) AS total FROM organizer WHERE LOWER(Status) = 'pending'";
$result_pending_organizers = mysqli_query($con, $sql_pending_organizers);
$row_pending_organizers = mysqli_fetch_assoc($result_pending_organizers);
$pending_organizers = $row_pending_organizers['total'];

/* Pending drivers */
$sql_pending_drivers = "SELECT COUNT(*) AS total FROM driver WHERE LOWER(Status) = 'pending'";
$result_pending_drivers = mysqli_query($con, $sql_pending_drivers);
$row_pending_drivers = mysqli_fetch_assoc($result_pending_drivers);
$pending_drivers = $row_pending_drivers['total'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f1f8e9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 10px;
        }

        .container {
            width: 90%;
            background:white;
            max-width: 1500px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            padding: 30px;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            position: relative;
            margin-bottom: 25px;
        }

        .backBtn {
            position: absolute;
            left: 0;
            border: none;
            font-size: 40px;
            cursor: pointer;
            background: white;
            color: black;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }


        h2 {
            text-align: center;
            font-size: 32px;
            color: #1b5e20;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        hr {
            border: none;
            height: 1px;
            background: linear-gradient(to right, transparent, #2e7d32, transparent);
            margin: 20px 0 30px 0;
        }

        h3 {
            text-align: center;
            font-size: 24px;
            color: #1b5e20;
            margin: 40px 0 25px 0;
            position: relative;
            padding-bottom: 10px;
        }

        h3:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: #2e7d32;
            border-radius: 2px;
        }

        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: space-between;
            margin: 30px auto 50px auto;
        }

        .card {
            flex: 1 1 calc(25% - 25px);
            min-width: 200px;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .card:nth-child(1) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .card:nth-child(1):before {
            background: #4facfe;
        }

        .card:nth-child(2) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .card:nth-child(2):before {
            background: #43e97b;
        }

        .card:nth-child(3) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .card:nth-child(3):before {
            background: #fa709a;
        }

        .card:nth-child(4) {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        }
        .card:nth-child(4):before {
            background: #a18cd1;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .card label {
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .card p {
            font-size: 48px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .btn {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .btn button {
            padding: 18px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: #2e7d32;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .btn button:hover {
            background: #4caf50;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        @media (max-width: 1200px) {
            .card {
                flex: 1 1 calc(50% - 25px);
            }
            
            .btn {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h2 {
                font-size: 24px;
            }
            
            h3 {
                font-size: 20px;
            }
            
            .card {
                flex: 1 1 100%;
                padding: 25px 15px;
            }
            
            .card p {
                font-size: 40px;
            }
            
            .btn {
                grid-template-columns: 1fr;
            }
            
            .backBtn {
                width: 45px;
                height: 45px;
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                margin: 5px;
            }
            
            .container {
                padding: 15px;
                width: 95%;
            }
            
            .cards {
                gap: 15px;
            }
            
            .card label {
                font-size: 14px;
            }
            
            .card p {
                font-size: 32px;
            }
            
            .btn button {
                padding: 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <button type='button' class='backBtn' onclick="window.location.href='../WS/homepage.php'">←</button>
            <h2>Admin Panel</h2>
        </div>
        <hr><br>

        <div class='cards'>
            <div class='card'>
                <label>Total User</label>
                <p><?php echo $total_users; ?></p>
            </div>

            <div class='card'>
                <label>Pending Event</label>
                <p><?php echo $pending_events; ?></p>
            </div>

            <div class='card'>
                <label>Pending Organizer</label>
                <p><?php echo $pending_organizers; ?></p>
            </div>

            <div class='card'>
                <label>Pending Driver</label>
                <p><?php echo $pending_drivers; ?></p>
            </div>
        </div> 

        <h3>Management Pending</h3>
        <div class='btn'>
            <button onclick="window.location.href='http://localhost/ASS-WDD/RY/admin_event_approval_page.php'">Event</button>

            <button onclick="window.location.href='http://localhost/ASS-WDD/RY/verify_event_organizer_pages.php'">Organizer</button>

            <button onclick="window.location.href='http://localhost/ASS-WDD/Aston/driververification.php'">Driver</button>
        </div>

        <h3>Other Management</h3>
        <div class='btn'>
            <button onclick="window.location.href='EditRewards.php'">Reward</button>

            <button onclick="window.location.href='EditNotifications.php'">Notification</button>
            
            <button onclick="window.location.href='EditNews.php'">News</button>

            <button onclick="window.location.href='ManageUser.php'">User</button>
        </div>
    </div>
</body>
</html>