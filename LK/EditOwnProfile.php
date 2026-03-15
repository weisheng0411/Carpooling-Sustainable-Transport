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
    <title>Edit Own Profile</title>
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
        box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
    }

    textarea {
        resize: none;
    }

    .full {
        grid-column: span 2;
    }

    img {
        display: block;
        margin: 10px auto;
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
    <h2>Edit Own Profile</h2><hr><br>

    <form class="form-grid" method="post" action="UpdateOP.php" enctype="multipart/form-data">
        <input type='hidden' name='id' value='<?php echo $row['user_id'] ?>'>
    
        <div class="form-group full">
            <label>TP Number</label>
            <input type="text" name='TPNumber' value='<?php echo $row['apu_id'] ?>' required>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name='Name' value='<?php echo $row['name'] ?>' required>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name='Username' value='<?php echo $row['username'] ?>' required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="text" name='Password' value='<?php echo $row['password'] ?>' required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name='Email' value='<?php echo $row['email'] ?>' required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name='PhoneNumber' value='<?php echo $row['phone_number'] ?>' required>
        </div>


        <div class="form-group select">
            <label>Gender</label>
            <select name='gender' required>
                <option value="">Select Gender</option>

                <option value="Male"
                    <?php if($row['gender'] == 'Male'){
                        echo 'selected';} ?>>
                Male</option>

                <option value="Female"
                    <?php if($row['gender'] == 'Female'){
                        echo 'selected';} ?>
                    >Female</option>
            </select>
        </div>

        <div class="form-group full">
            <label>Photo</label>
        
            <img
                id = "previewImg"
                src = "<?php echo htmlspecialchars('/ASS-WDD/uploads/' . basename($row['photo_url'])); ?>" 
                alt = "Photo"
            >
            
            <input type="file" name="photo" onchange="previewImage(event)">
        </div>

        <div class="btn-row full">
            <button class="edit" type="submit" name='confirmBtn'>Confirm</button>
            <button class="cancel" type="button" onclick="window.location.href='EditProfile.php'">Cancel</button>
        </div>

    </form>

    
</div>

<script>
    function previewImage(event){
        const img = document.getElementById('previewImg');
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
