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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Own Profile</title>
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
            width: 90%;
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
            margin-bottom: 20px;
            color: var(--eco-green);
        }

        hr {
            height: 3px;
            background: var(--eco-green);
            border: none;
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
            display: block;
            color: var(--text-dark);
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #bbb;
            background: #f0f0f0;
            font-size: 14px;
            cursor:not-allowed;
        }

        textarea {
            resize: none;
        }

        .full {
            grid-column: span 2;
        }

        img {
            display: block;
            margin: 0 auto;
            border-radius: var(--border-radius);
            width: 200px;
            height: 200px;
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
    <h2>Own Profile</h2>
    <hr><br>

    <form class="form-grid">
        <input type='hidden' name='id' value='<?php echo $row['user_id'] ?>'>

        <div class="form-group full">
            <label>TP Number</label>
            <input type="text" name='TP Number' value='<?php echo $row['apu_id'] ?>' readonly>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name='Name' value='<?php echo $row['name'] ?>' readonly>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name='Username' value='<?php echo $row['username'] ?>' readonly>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="text" name='Password' value='<?php echo $row['password'] ?>' readonly>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name='Email' value='<?php echo $row['email'] ?>' readonly>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name='Phone Number' value='<?php echo $row['phone_number'] ?>' readonly>
        </div>

        <div class="form-group select">
            <label>Gender</label>
            <input type="text" value="<?php echo $row['gender']; ?>" readonly>
        </div>

        <div class="form-group full">
            <label>Photo</label>

            <img 
                src="/ASS-WDD/<?php echo htmlspecialchars($row['photo_url']); ?>" 
                alt="Photo" 
            >
        </div>

        <div class="btn-row full">
            <button class="edit" type="button" onclick="window.location.href='EditOwnProfile.php'">Edit</button>
            <button class="cancel" type="button" onclick="window.location.href='EditProfile.php'">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
