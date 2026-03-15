<?php
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../WS/login.php");
        exit();
    }

    if (isset($_POST['createBtn'])){
        include('conn.php');

        $photo_path='';

        if (!empty($_FILES['photo']['name'])){
            $target_dir = __DIR__ . "/uploads/";

            if(!is_dir($target_dir)){
                mkdir($target_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES['photo']['name']);
            $target_file = $target_dir . $file_name;

            $photo_path = "uploads/" . $file_name;

            move_uploaded_file($_FILES['photo']['tmp_name'], $target_file);
        }

        $sql = "INSERT INTO news (title,content,date,image)
                VALUES (' ".$_POST['title']." ',
                        ' ".$_POST['describe']." ',
                        ' ".$_POST['date']." ',
                        ' ".$photo_path." ')
                ";

        if (mysqli_query($con, $sql)){
            mysqli_close($con);
            echo "<script>
                alert('A New Created');
                window.location.href='EditNews.php';
            </script>";
        } else {
            echo "<script>
                alert('Failed to create');
                window.location.href='EditNews.php';
            </script>";
        }

    }
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add News</title>

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
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border-radius: var(--border-radius);
        border: 1px solid #bbb;
        background: #f0f0f0;
        font-size: 14px;
        box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
        margin-bottom:15px;
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

        <form method='post' action='' enctype="multipart/form-data">
            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='EditNews.php'" >←</button>
                <h2>Create A New</h2>
            </div>
            <hr><br>
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name='title' required>
            </div>

            <div class="form-group">
                <label>Content</label>
                <textarea rows='5' name='describe' placeholder='Write Something...' required></textarea>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name='date' required>
            </div>

            <div class="form-group">
                <label>Image</label>
                <input type="file" name='photo' required>
            </div>

            <button class="btn-save" type="submit" name='createBtn'>Create</button>
        </form>

    </div>

</body>
</html>
