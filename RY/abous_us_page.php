<?php
session_start();

    include("conn.php");
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
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

        .container { display: flex;
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
        
        .about-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .about-card {
            display: flex;
            flex-direction: column; 
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border-left: 4px solid var(--eco-green);
            transition: transform 0.2s;
        }

        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .about-photo-wrapper {
            width: 100%;
            height: 250px;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #999;
            font-size: 1.2rem;
            position: relative;
        }

        .about-photo-wrapper img {
            width: 100%; height: 100%; 
            object-fit: cover;
        }

        .about-text-wrapper {
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }

        .about-text-wrapper h2 {
            margin-top: 0;
            color: var(--text-dark);
            font-size: 40px;
            margin-bottom: 15px;
        }

        .about-text-wrapper p {
            color: var(--text-light);
            line-height: 1.6;
            margin: 0;
            font-size: 25px;
        }

        @media only screen and (min-width: 900px) {
            .about-card { 
                flex-direction: row; 
                min-height: 250px; 
            }

            .about-photo-wrapper { 
                width: 40%; 
                height: auto; 
                min-height: 250px; 
            }
            
            .about-card.reverse { 
                flex-direction: row-reverse; 
                border-left: none; 
                border-right: 4px solid var(--eco-green); 
            }
        }

        @media (max-width: 600px) {
            .main { padding: 15px; }

            .about-text-wrapper { padding: 20px; }
            .about-photo-wrapper { height: 200px; }
        }
    </style>
</head>

<body>

    <div class="container">
        <main class="main">

            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='../Aston/profile.php'">←</button>
                <h2>About Us</h2>
            </div>
            <hr><br>

            <div class="about-content">
                
                <div class="about-card">
                    <div class="about-photo-wrapper">
                        <img src="../uploads/2b0ffb8d-4133-4205-a63d-d1b6dc3940c5.jpg" alt="User Photo">
                        </div>
                    <div class="about-text-wrapper">
                        <h2>Team Member</h2>
                        <p>Ler Lian Kai</p>
                        <p>Sam Junyi</p>
                        <p>So Wei Sheng</p>
                        <p>Tan Zhi Cheng</p>
                        <p>Ho Rong Yu</p>
                    </div>
                </div>

                <div class="about-card reverse">
                    <div class="about-photo-wrapper">
                        <img src="../uploads/20b4050b-ab87-486b-8791-0140e882664f.jpg" alt="User Photo">
                        </div>
                    <div class="about-text-wrapper">
                        <h2>Our Story</h2>
                        <p>It all started with a simple idea: how can we reduce our carbon footprint while making daily commuting easier? From a garage project to a community movement...</p>
                    </div>
                </div>

            </div>

        </main>
    </div>

</body>
</html>