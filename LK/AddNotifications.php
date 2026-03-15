<?php
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }
    
    if (isset($_POST['createBtn'])){
        include('conn.php');

        $sql = "INSERT INTO notification (title,message,type,date)
                VALUES (' ".$_POST['title']." ',
                        ' ".$_POST['message']." ',
                        ' ".$_POST['type']." ',
                        ' ".$_POST['date']." '
                        )";

        if (mysqli_query($con, $sql)){ 
            $not_id = mysqli_insert_id($con);

            $sqlUserNoti = "INSERT INTO user_notification (user_ID, not_ID, is_read)
                            SELECT user_id, $not_id, 0
                            FROM user_acc
                            ";
            mysqli_query($con,$sqlUserNoti);

            echo "<script>
                alert('A New Notification Created');
                window.location.href='EditNotifications.php';
            </script>";
        } else {
            echo "<script>
                alert('Failed to create');
                window.location.href='EditNotifications.php';
            </script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Notifications</title>

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

    .main {
        background: var(--card-bg);
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        max-width: 1100px;
        width: 100%;
    }

    /* Header */
    .header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        position: relative;
    }

    .header h2 {
        font-size: 28px;
        font-weight: bold;
        color: var(--eco-green);
        text-align: center;
    }

    .backBtn {
        position: absolute;
        left: 0;
        font-size: 25px;
        border: none;
        cursor: pointer;
        background: var(--card-bg);
        color: var(--text-dark);
    }

    /* Horizontal line */
    hr {
        height: 3px;
        background: var(--eco-green);
        border: none;
        margin: 15px 0;
    }

    /* Form elements */
    .form-group {
        margin-bottom: 10px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 6px;
        color: var(--text-dark);
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border-radius: var(--border-radius);
        border: 1px solid #bbb;
        background: #f0f0f0;
        font-size: 14px;
        box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    /* Button */
    button {
        padding: 12px;
        font-size: 15px;
        font-weight: bold;
        border-radius: var(--border-radius);
        border: none;
        cursor: pointer;
        transition: all 0.25s ease; 
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 5px rgba(0,0,0,0.15);
    }

    .btn-save {
        display:block;
        margin: 0 auto;
        width: 50%;
        background-color: var(--eco-green);
        color: #fff;
    }

    .btn-save:hover {
        background-color: var(--light-green);
    }

</style>
</head>

<body>
    <div class="main">

        <form method='post' action=''>
            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='EditNotifications.php'" >←</button>
                <h2>Create A Notification</h2>
            </div>
            <hr><br>
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name='title' required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea rows='5' name='message' placeholder='Write Something...' required></textarea>
            </div>

            <div class="form-group">
                <label>type</label>
                <select name='type' required>
                    <option value=''>Select Type</option>
                    <option value='system'>System</option>
                    <option value='user'>User</option>
                    <option value='other'>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name='date' required>
            </div>

            <button class="btn-save" type="submit" name='createBtn'>Create</button>
        </form>

    </div>

</body>
</html>
