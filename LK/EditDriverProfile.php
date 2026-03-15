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
    <title>Edit Driver Profile</title>

    <style>
        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        h2 {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: var(--eco-green);
            margin-bottom: 15px;
        }

        .section-title p {
            font-weight: bold;
            font-size: 20px;
            color: var(--eco-green);
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
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-dark);
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            background: #f0f0f0;
            font-size: 14px;
            box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
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
            margin: 10px auto;
            border-radius: var(--border-radius);
            max-width: 250px;
            max-height: 150px;
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
    <h2>Edit Driver Profile</h2><hr><br>
    
    <form class="form-grid" method='post' action='UpdateDP.php' enctype='multipart/form-data'>
        <div class="section-title full">
            <p>Personal Information</p>
            <hr>
        </div>

        <input type="hidden" name="id" value='<?php echo $row['user_id']?>'>

        <div class="form-group">
            <label>TP Number</label>
            <input type="text" name="TPNumber" value="<?php echo $row['apu_id']?>" required>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="FName" value="<?php echo $row['name']?>" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phoneNumber" value="<?php echo $row['phone_number']?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo $row['email']?>" required>
        </div>

        <div class="section-title full">
            <p>Vehice Information</p>
            <hr>
        </div>
        
        <div class="form-group">
            <label>Vecicle Model</label>
            <input type="text" name="carModel" value="<?php echo $row['Car_Model']?>" required>
        </div>

        <div class="form-group">
            <label>Car Plate No.</label>
            <input type="text" name="plate" value="<?php echo $row['Plate_Number']?>" required>
        </div>

        <div class="form-group">
            <label>Seat Available</label>
            <input type="number" name="seat" value="<?php echo $row['Seat_Available']?>" required>
        </div>

        <div class="form-group">
            <label>License No.</label>
            <input type="text" name="license" value="<?php echo $row['License']?>" required>
        </div>

        <div class="form-group">
            <label>IC Photo</label>

            <img
                id = "previewICImg"
                src = "<?php echo htmlspecialchars('/ASS-WDD/uploads/' . basename($row['IC_Photo_URL'])); ?>"
                alt = "IC Photo"
            >

            <input type="file" name="ICPhoto" onchange="previewImage(event,'previewICImg')">
        </div>

        <div class="form-group">
            <label>License Photo</label>
            
            <img
                id = "previewLicenseImg"
                src = "<?php echo htmlspecialchars('/ASS-WDD/uploads/' . basename($row['License_Photo_URL'])); ?>"
                alt = "License Photo"
            >

            <input type="file" name="LicensePhoto" onchange="previewImage(event,'previewLicenseImg')">
        </div>

        <div class="btn-row full">
            <button class="edit" type="submit" name='confirmBtn'>Confirm</button>
            <button class="cancel" type="button" onclick="window.location.href='EditProfile.php'">Cancel</button>
        </div>
    </form>

</div>

<script>
    function previewImage(event,imgID){
        const img = document.getElementById(imgID);
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }  
</script>

</body>
</html>
