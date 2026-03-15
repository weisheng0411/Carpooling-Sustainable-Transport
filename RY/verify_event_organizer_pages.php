<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

$sql = "SELECT 
            o.Organizer_ID, 
            o.Description, 
            o.Status, 
            u.user_id,
            u.name, 
            u.email, 
            u.phone_number, 
            u.apu_id,
            u.photo_url,
            u.role,
            u.gender
        FROM 
            organizer o
        JOIN 
            user_acc u ON o.User_ID = u.user_id
        WHERE 
            o.Status = 'Pending'";
$result = $conn->query($sql);

if (isset($_POST['action'])) {
    $organizer_id = $_POST['organizer_id'];
    $action = $_POST['action'];
    $new_status = "";

    if ($action == "approve") {
        $new_status = "pass"; 
    } 
    
    if ($action == "reject") {
        $new_status = "Rejected";
    }

    $sql_update = "UPDATE organizer SET Status = '$new_status' WHERE Organizer_ID = '$organizer_id'";
    $conn->query($sql_update);

    header("Location: " . $_SERVER['PHP_SELF']. "?status=" . $action);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Approval</title>
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #f5f5f5;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .org-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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
                <h2>Organizer Approval</h2>
            </div>
            <hr><br>

            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>

            <div class="approval-card">
                
                <div class="content-wrapper">
                    
                    <div class="left-section">
                        
                        <div class="organizer-details">
                            <h3 style="font-size:35px; margin-top:0; color:var(--text-dark); margin-bottom: 15px;"><?php echo $row['name']; ?></h3>
                            
                            <p style="color:#555; line-height: 1.6;">
                                <?php echo $row['Description']; ?>
                            </p>

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">TP NUMBER</span>
                                    <?php echo $row['apu_id']; ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">PHONE NUMBER</span>
                                    <?php echo $row['phone_number']; ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">EMAIL</span>
                                    <?php echo $row['email']; ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">GENDER</span>
                                    <?php echo $row['gender']; ?> Pts
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-section">
                        <div class="organizer-info">
                            <div class="org-avatar">
                                <img src="/ASS-WDD/<?= htmlspecialchars($row['photo_url']) ?>"  alt="Image">
                            </div>
                            <div style="flex: 1">
                                <div class="info-label">Organizer ID</div>
                                <strong style="display:block; margin-top:5px; font-size: 16px;">
                                    ID: <?php echo $row['Organizer_ID']; ?>
                                </strong>
                                <div style="font-size:12px; color:#999; margin-top:5px;">
                                    Role: <?php echo $row['role']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="actions">
                            <input type="hidden" name="organizer_id" value="<?php echo $row['Organizer_ID']; ?>">
                            
                            <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                <span>✅</span> Approve Organizer
                            </button>

                            <button type="submit" name="action" value="reject" class="action-btn btn-reject">
                                <span>❌</span> Reject Organizer
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <?php 
                } 
            } else {
                echo "<p style='text-align:center; padding:40px; color:#999;'>No pending organizers to review.</p>";
            }
            ?>
        </main>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', (organizer) => {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'approve') {
            alert("✅ Organizer has been successfully APPROVED!");
            window.history.replaceState(null, null, window.location.pathname);
        }
        
        if (status === 'reject') {
            alert("❌ Organizer has been REJECTED.");
            window.history.replaceState(null, null, window.location.pathname);
        }
        });
    </script>

</body>
</html>