<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

// 使用预处理语句防止SQL注入
$sql = "SELECT e.*, u.photo_url as user_photo, u.name as organizer_name
        FROM event e 
        LEFT JOIN organizer o ON e.Organizer_ID = o.Organizer_ID
        LEFT JOIN user_acc u ON o.User_ID = u.user_id
        WHERE e.Status = 'Pending'";
$result = $conn->query($sql);

if (isset($_POST['action'])) {
    $event_id = $_POST['event_id'];
    $action = $_POST['action'];
    
    // 使用预处理语句防止SQL注入
    if ($action == "approve") {
        $new_status = "Active";
    } elseif ($action == "reject") {
        $new_status = "Rejected";
    } else {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // 使用预处理语句
    $sql_update = "UPDATE event SET Status = ? WHERE Event_ID = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $event_id);
    $stmt->execute();
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=" . $action);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Event Approval</title>
    <style>
        /* 你的CSS样式保持不变 */
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
            --danger: #d32f2f;
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
        
        .page-title-group { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            min-width: 0; 
        }

        .page-title h2 { 
            font-size: 28px; 
            color: var(--text-dark); 
            margin: 0; 
            white-space: nowrap; 
        }

        .page-title p { 
            color: var(--text-light);
            margin: 5px 0 0 0; 
            font-size: 14px; 
            white-space: nowrap; 
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

        .approval-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border-top: 5px solid var(--eco-green);
            max-width: 1100px;
            margin: 0 auto 30px auto;
        }

        .content-wrapper {
            display: flex;
            gap: 30px;
        }

        .left-section {
            flex: 3;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .event-photo {
            height: 300px;
            background-color: #f9f9f9;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            color: #999;
            border: 2px dashed #ccc;
            overflow: hidden;
        }
        
        .event-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .info-item {
            background-color: #f1f8e9;
            padding: 20px;
            text-align: center;
            font-weight: 600;
            color: var(--text-dark);
            border-radius: 10px;
            border: 1px solid #c5e1a5;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .right-section {
            flex: 1;
            background: #fafafa;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 25px;
            height: fit-content;
            min-width: 250px;
        }

        .organizer-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .org-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background-color: #ddd;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .org-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .action-btn {
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .btn-approve {
            background-color: var(--eco-green);
            color: white;
        }
        .btn-approve:hover {
            background-color: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.2);
        }

        .btn-reject {
            background-color: white;
            color: var(--danger);
            border: 2px solid var(--danger);
        }
        .btn-reject:hover {
            background-color: #ffebee;
        }

        @media (max-width: 900px) {
            .content-wrapper { flex-direction: column; }
            
            .right-section { 
                flex-direction: row; 
                align-items: center; 
                justify-content: space-between; 
            }
            .organizer-info { 
                flex-direction: row; 
                border-bottom: none; 
                padding-bottom: 0; 
                text-align: left; 
                flex: 1;
            }
            .actions { 
                flex-direction: row; 
                width: 50%; 
            }
            .action-btn { flex: 1; }
        }

        @media (max-width: 600px) {
            .main { padding: 15px; }
            
            .page-title h2 { font-size: 20px; }
            .page-title p { display: none; }

            .approval-card { padding: 20px 15px; }
            .event-photo { height: 200px; }
            .right-section { flex-direction: column; gap: 20px; }
            .organizer-info { width: 100%; justify-content: flex-start; }
            .actions { width: 100%; flex-direction: column; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <div class="container">
        <main class="main">
            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='../LK/AdminPanel.php'">←</button>
                <h2>Event Approval</h2>           
            </div>
            <hr><br>

            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>

            <div class="approval-card">
                
                <div class="content-wrapper">
                    
                    <div class="left-section">
                        <div class="event-photo">
                            <?php 
                            if(!empty($row['Photo_URL'])) { 
                                $photo_path = "/ASS-WDD/WS/" . $row['Photo_URL'];
                            ?>
                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Event Photo" onerror="this.style.display='none'; this.parentNode.innerHTML='<span>📷 Photo not found</span>';">
                            <?php } else { ?>
                                <span>📸 No Photo Uploaded</span>
                            <?php } ?>
                        </div>
                        
                        <div class="event-details">
                            <h3 style="margin-top:0; color:var(--text-dark); margin-bottom: 15px;">
                                <?php echo htmlspecialchars($row['Name']); ?>
                            </h3>
                            
                            <p style="color:#555; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($row['Description'])); ?>
                            </p>

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Start Date</span>
                                    <?php echo htmlspecialchars($row['Start_Date']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End Date</span>
                                    <?php echo htmlspecialchars($row['End_Date']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location</span>
                                    <?php echo htmlspecialchars($row['Location']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Points</span>
                                    <?php echo htmlspecialchars($row['Points']); ?> Pts
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-section">
                        <div class="organizer-info">
                            <div class="org-avatar">
                                <?php 
                                if(!empty($row['user_photo'])) { 
                                    $user_photo_path = "/ASS-WDD/" . $row['user_photo'];
                                ?>
                                    <img src="<?php echo htmlspecialchars($user_photo_path); ?>" alt="Organizer Photo" onerror="this.style.display='none'; this.parentNode.style.backgroundColor='#ddd';">
                                <?php } else { ?>
                                    <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#666;">
                                        👤
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="flex: 1">
                                <div class="info-label">Organizer</div>
                                <strong style="display:block; margin-top:5px; font-size: 16px;">
                                    <?php echo htmlspecialchars($row['organizer_name'] ?? 'Unknown'); ?>
                                </strong>
                                <div style="font-size:12px; color:#999; margin-top:5px;">
                                    ID: <?php echo htmlspecialchars($row['Organizer_ID']); ?>
                                </div>
                                <div style="font-size:12px; color:#999; margin-top:2px;">
                                    Event ID: <?php echo htmlspecialchars($row['Event_ID']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="actions" onsubmit="return confirmAction(this)">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($row['Event_ID']); ?>">
                            
                            <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                <span>✅</span> Approve Event
                            </button>

                            <button type="submit" name="action" value="reject" class="action-btn btn-reject">
                                <span>❌</span> Reject Event
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <?php 
                } 
            } else {
                echo "<p style='text-align:center; padding:40px; color:#999;'>No pending events to review.</p>";
            }
            
            // 关闭连接
            if ($result) {
                $result->free();
            }
            ?>
        </main>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'approve') {
                alert("✅ Event has been successfully APPROVED!");
                window.history.replaceState(null, null, window.location.pathname);
            }
            
            if (status === 'reject') {
                alert("❌ Event has been REJECTED.");
                window.history.replaceState(null, null, window.location.pathname);
            }
        });
        
        function confirmAction(form) {
            const action = form.querySelector('button[type="submit"][clicked]').value;
            const message = action === 'approve' 
                ? 'Are you sure you want to APPROVE this event?' 
                : 'Are you sure you want to REJECT this event?';
            
            return confirm(message);
        }
        
        // 记录哪个按钮被点击了
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const buttons = form.querySelectorAll('button[type="submit"]');
                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        buttons.forEach(btn => btn.removeAttribute('clicked'));
                        this.setAttribute('clicked', 'true');
                    });
                });
            });
        });
    </script>

</body>
</html>