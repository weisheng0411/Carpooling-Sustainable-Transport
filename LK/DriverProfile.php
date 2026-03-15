<?php
    session_start();
    
    include("conn.php");
        if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
        }

    $id = $_SESSION['user_id'];

    $sql = "SELECT * FROM user_acc
        JOIN driver ON user_acc.user_id = driver.user_id
        WHERE user_acc.user_id = $id";

    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile</title>

    <style>
        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            --border-radius: 10px;
        }

        /* -------- Base -------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: var(--background);
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        /* -------- Container -------- */
        .container {
            background: var(--card-bg);
            width: 95%;
            max-width: 900px;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        /* -------- Headings -------- */
        h2, p {
            color: var(--eco-green);
            font-weight: bold;
        }

        h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        p {
            font-size: 20px;
            margin-bottom: 10px;
        }

        hr {
            height: 3px;
            background: var(--eco-green);
            border: none;
            margin-bottom: 15px;
        }

        /* -------- Form Grid -------- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            color: var(--text-dark);
            display: block;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            background: #f0f0f0;
            font-size: 14px;
            box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
            cursor:not-allowed;
        }

        textarea {
            resize: none;
        }

        .full {
            grid-column: span 2;
        }

        /* -------- Image Preview -------- */
        img {
            display: block;
            margin: 0 auto;
            border-radius: var(--border-radius);
            width: 250px;
            height: 150px;
        }

        /* -------- Buttons -------- */
        .btn-row {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        button {
            width: 48%;
            padding: 12px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.25s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 5px rgba(0,0,0,0.15);
        }

        .edit {
            background-color: var(--eco-green);
            color: #fff;
        }

        .edit:hover {
            background-color: var(--light-green);
        }

        .cancel {
            background-color: black;
            color: white;
        }

        .cancel:hover {
            opacity: 0.85;
        }

        /* -------- Mobile View -------- */
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: span 1;
            }

            .btn-row {
                flex-direction: column;
                gap: 10px;
            }

            button {
                width: 100%;
            }
        }
    </style>

</head>
<body>

<div class="container">
    <h2>Driver Profile</h2><hr><br>
    <p>Personal Information</p><hr>

    <form class="form-grid">
        <input type="hidden" name="id" value='<?php echo $row['user_id']?>'>

        <div class="form-group">
            <label>TP Number</label>
            <input type="text" name="TPNumber" value="<?php echo $row['apu_id']?>" readonly>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="FName" value="<?php echo $row['name']?>" readonly>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phoneNumber" value="<?php echo $row['phone_number']?>" readonly>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo $row['email']?>" readonly>
        </div>
    </form>

    <br>

    <p>Vehice Information</p><hr>
    <form class="form-grid">
        <div class="form-group">
            <label>Vecicle Model</label>
            <input type="text" name="carModel" value="<?php echo $row['Car_Model']?>" readonly>
        </div>

        <div class="form-group">
            <label>Car Plate No.</label>
            <input type="text" name="plate" value="<?php echo $row['Plate_Number']?>" readonly>
        </div>

        <div class="form-group">
            <label>Seat Available</label>
            <input type="number" name="seat" value="<?php echo $row['Seat_Available']?>" readonly>
        </div>

        <div class="form-group">
            <label>License No.</label>
            <input type="text" name="license" value="<?php echo $row['License']?>" readonly>
        </div>

        <div class="form-group">
            <label>IC Photo</label>

            <img 
                src="/ASS-WDD/<?php echo htmlspecialchars($row['IC_Photo_URL']); ?>" 
                alt="IC Photo" 
            >
        </div>

        <div class="form-group">
            <label>License Photo</label>
            
            <img 
                src="/ASS-WDD/<?php echo htmlspecialchars($row['License_Photo_URL']); ?>" 
                alt="IC Photo" 
            >
        </div>

        <div class="btn-row full">
            <button class="edit" type="button" onclick="window.location.href='EditDriverProfile.php'">Edit</button>
            <button class="cancel" type="button" onclick="window.location.href='EditProfile.php'">Cancel</button>
        </div>
    </form>

    

</div>

</body>
</html>
