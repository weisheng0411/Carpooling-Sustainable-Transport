<?php
// 数据库连接
$conn = mysqli_connect("localhost", "root", "", "wdd");
if (!$conn) {
    die("Connection failed");
}

// 处理删除
if (isset($_GET['delete'])) {
    $not_id = $_GET['delete'];
    $sql = "DELETE FROM notification WHERE not_id = $not_id";
    mysqli_query($conn, $sql);
    echo "<script>alert('Notification deleted!');</script>";
}

// 处理更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_noti'])) {
    $not_id = $_POST['not_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $date = $_POST['date'];
    
    $sql = "UPDATE notification SET 
            title = '$title', 
            message = '$message', 
            type = '$type', 
            date = '$date' 
            WHERE not_id = $not_id";
    mysqli_query($conn, $sql);
    echo "<script>alert('Notification updated!');</script>";
}

// 搜索
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// 获取数据
$sql = "SELECT * FROM notification";
if (!empty($search)) {
    $sql .= " WHERE title LIKE '%$search%' OR message LIKE '%$search%'";
}
$sql .= " ORDER BY date DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Notifications</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
        color: #1b5e20;
        line-height: 1.6;
    }

    .main {
        max-width: 1200px;
        margin: 30px auto;
        padding: 30px 40px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    /* HEADER SECTION */
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .header-left {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex: 1;
    }

    .header-right {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .backBtn, .refreshBtn {
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
    }

    .backBtn {
        background: #607d8b;
        color: white;
    }

    .backBtn:hover {
        background: #455a64;
        transform: translateY(-2px);
    }

    .refreshBtn {
        background: #2e7d32;
        color: white;
    }

    .refreshBtn:hover {
        background: #1b5e20;
        transform: translateY(-2px);
    }

    h2 {
        text-align: center;
        font-size: 28px;
        color: #1b5e20;
        margin: 0;
        flex: 1;
    }

    hr {
        background-color: #c8e6c9;
        border: none;
        height: 2px;
        margin: 10px 0 25px 0;
    }

    /* SEARCH BAR SECTION */
    .top-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
        background: #f8f9f8;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .search-input {
        flex: 1;
        padding: 12px 18px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #4caf50;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
    }

    button {
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    button:hover {
        transform: translateY(-2px);
    }

    .btn-search {
        background: #4caf50;
        color: white;
    }

    .btn-search:hover {
        background: #388e3c;
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    }

    .btn-upload {
        background: #2196f3;
        color: white;
    }

    .btn-upload:hover {
        background: #1976d2;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
    }

    /* NOTIFICATION LIST SECTION */
    .news-flex {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .noti {
        background: white;
        padding: 25px;
        border-radius: 12px;
        border: 1px solid #e8f5e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .noti:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-color: #c8e6c9;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #2e7d32;
        font-size: 14px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        font-size: 15px;
        transition: all 0.3s;
        background: #f8f9f8;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #4caf50;
        background: white;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
    }

    textarea {
        resize: vertical;
        min-height: 100px;
        font-family: inherit;
    }

    select {
        cursor: pointer;
    }

    /* ACTION BUTTONS */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        gap: 12px;
    }

    .form-actions button {
        flex: 1;
        padding: 10px 16px;
        font-size: 14px;
    }

    .btn-save {
        background: #4caf50;
        color: white;
    }

    .btn-save:hover {
        background: #388e3c;
        box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
    }

    .btn-reload {
        background: #6c757d;
        color: white;
    }

    .btn-reload:hover {
        background: #495057;
        box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
    }

    .btn-delete {
        background: #f44336;
        color: white;
    }

    .btn-delete:hover {
        background: #d32f2f;
        box-shadow: 0 4px 10px rgba(244, 67, 54, 0.2);
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
        .main {
            padding: 20px;
            margin: 15px;
        }
        
        .header {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
        
        .header-left {
            order: 1;
            width: 100%;
        }
        
        .header-right {
            order: 2;
            justify-content: center;
            width: 100%;
        }
        
        h2 {
            margin-bottom: 10px;
        }
        
        .top-bar {
            flex-direction: column;
        }
        
        .top-bar input,
        .top-bar button {
            width: 100%;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .form-actions button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .main {
            padding: 15px;
            margin: 10px;
        }
        
        .backBtn, .refreshBtn {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .header-right {
            gap: 8px;
        }
    }

    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background: #f8f9f8;
        border-radius: 12px;
        border: 2px dashed #c8e6c9;
        color: #666;
    }

    .empty-state h3 {
        color: #2e7d32;
        margin-bottom: 10px;
    }
</style>
</head>

<body>

<div class="main">

    <div class='header'>
        <div class="header-left">
            <h2>Edit Notifications</h2>
        </div>
        <div class="header-right">
            <button type='button' class='backBtn' onclick="window.location.href='AdminPanel.php'">← Back</button>
            <button type='button' class='refreshBtn' onclick="window.location.href='EditNotifications.php'">↻ Refresh</button>
        </div>
    </div>

    <form class="top-bar" method="GET">
        <input type="text" class="search-input" name="search" placeholder="Search notifications..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn-search" type="submit">Search</button>
        <button class="btn-upload" type="button" onclick="window.location.href='AddNotifications.php'">Create Notifications</button>
    </form>

    <div class="news-flex">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $not_id = $row['not_id'];
                $title = htmlspecialchars($row['title']);
                $message = htmlspecialchars($row['message']);
                $type = htmlspecialchars($row['type']);
                $date = $row['date'];
        ?>
        <!-- Notification Item -->
        <form class="noti" method="POST">
            <input type="hidden" name="not_id" value="<?php echo $not_id; ?>">
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo $title; ?>" required>
            </div>

            <div class="form-group">
                <label>Content</label>
                <textarea name="message" required><?php echo $message; ?></textarea>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" required>
            </div>

            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="System" <?php echo $type == 'System' ? 'selected' : ''; ?>>System</option>
                    <option value="User" <?php echo $type == 'User' ? 'selected' : ''; ?>>User</option>
                    <option value="Alert" <?php echo $type == 'Alert' ? 'selected' : ''; ?>>Alert</option>
                    <option value="Info" <?php echo $type == 'Info' ? 'selected' : ''; ?>>Info</option>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn-save" type="submit" name="save_noti">Save</button>
                <button class="btn-reload" type="reset" onclick="window.location.href='http://localhost/ASS-WDD/LK/EditNotifications.php'">Reload</button>
                <button class="btn-delete" type="button" 
                        onclick="if(confirm('Delete this notification?')) window.location.href='?delete=<?php echo $not_id; ?>'">
                    Delete
                </button>
            </div>
        </form>
        <?php
            }
        } else {
            echo '<div style="text-align: center; padding: 40px; color: #666;">No notifications found.</div>';
        }
        ?>
    </div>

</div>

</body>
</html>

<?php
mysqli_close($conn);
?>