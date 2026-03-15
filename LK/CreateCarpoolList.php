<?php
require_once 'conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
$error = "";
$success = "";

// 获取司机信息
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 测试用
$driver_id = isset($_SESSION['driver_id']) ? $_SESSION['driver_id'] : 1; // 测试用


// 查询司机信息
$driver_query = "
                SELECT *
                FROM driver
                JOIN user_acc ON driver.user_id = user_acc.user_id
                WHERE driver.user_id = '$user_id'
                LIMIT 1
                ";

$driver_result = mysqli_query($con, $driver_query);

if($driver_result && mysqli_num_rows($driver_result) > 0) {
    $driver = mysqli_fetch_assoc($driver_result);
    $driver_id = $driver['Driver_ID'];
    $driver_name = $driver['name'];
    $driver_photo = $driver['photo_url'];
    $car_model = $driver['Car_Model'] ?? "Proton X70";
    $car_color = $driver['Car_Color'] ?? "White";
    $plate_number = $driver['Plate_Number'] ?? "ABC1234";
    $seats_available = $driver['Seat_Available'] ?? 4;
    $driver_status = $driver['Status'] ?? "pending"; // 司机账户状态
} else {
    // 测试数据
    $driver_id = 1;
    $car_model = "Proton X70";
    $car_color = "White";
    $plate_number = "ABC1234";
    $seats_available = 4;
    $driver_status = "pass"; // 司机账户状态
    $error = "Driver information not found. Using test data.";
}

// 处理表单提交
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create'])) {
    $from_place = "APU";
    $to_place = mysqli_real_escape_string($con, $_POST['to_place'] ?? '');
    $date = mysqli_real_escape_string($con, $_POST['date'] ?? '');
    $time = mysqli_real_escape_string($con, $_POST['time'] ?? '');
    
    if(!empty($to_place) && !empty($date) && !empty($time)) {
        // 检查司机账户状态 - 必须是'pass'才能创建拼车
        if($driver_status !== 'pass') {
            $error = "Cannot create carpool. Your driver account status is: $driver_status (must be 'pass')";
        } else {
            // 检查carpool_list表
            $table_check = mysqli_query($con, "SHOW TABLES LIKE 'carpool_list'");
            
            if(mysqli_num_rows($table_check) > 0) {
                // 获取下一个cl_id
                $id_query = "SELECT MAX(cl_id) as max_id FROM carpool_list";
                $id_result = mysqli_query($con, $id_query);
                $next_id = 1;
                
                if($id_result && mysqli_num_rows($id_result) > 0) {
                    $row = mysqli_fetch_assoc($id_result);
                    $next_id = $row['max_id'] + 1;
                }
                
                // 设置拼车状态为'open'
                $carpool_status = "open"; // 拼车状态，只能是open或close
                
                // 插入数据到carpool_list表
                $sql = "INSERT INTO carpool_list (driver_id, from_place, to_place, date, time, seats, bording_point, status_open_close) 
                        VALUES ('$driver_id', '$from_place', '$to_place', '$date', '$time', '$seats_available', 'Main Entrance', '$carpool_status')";
                
                if(mysqli_query($con, $sql)) {
                    $success = "Carpool created successfully!";
                    echo "<script>window.location.href='CarpoolList.php';</script>";

                } else {
                    $error = "Error: " . mysqli_error($con);
                }
            } else {
                $error = "Table 'carpool_list' does not exist.";
            }
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Car Pool Page</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

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
        --admin-blue: #2196f3;
        --admin-blue-light: #64b5f6;
        --admin-blue-dark: #1976d2;
    }

    body {
        background: var(--background);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }

    .container {
        width: 90%;
        max-width: 700px;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 35px;
        border-left: 4px solid var(--light-green);
    }

    h2 {
        text-align: center;
        margin-bottom: 10px;
        color: var(--text-dark);
        font-size: 28px;
        font-weight: 700;
        padding-bottom: 15px;
        border-bottom: 3px solid #e8f5e9;
    }

    hr {
        border: none;
        height: 1px;
        background: #e0e0e0;
        margin: 20px 0 25px 0;
    }

    .profile {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9f8;
        border-radius: 10px;
        border: 1px solid #c8e6c9;
    }

    .profile img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid var(--light-green);
    }

    .profile div {
        flex-grow: 1;
    }

    .profile h3 {
        color: var(--text-dark);
        margin-bottom: 8px;
        font-size: 22px;
    }

    .driver-info, .details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .driver-info div, .details div {
        display: flex;
        flex-direction: column;
        font-size: 15px;
    }

    .driver-info label, .details label {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 8px;
        color: var(--text-medium);
    }

    .driver-info p {
        background: #f8f9f8;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        color: var(--text-dark);
        min-height: 44px;
        display: flex;
        align-items: center;
    }

    .details input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        background: #f8f9f8;
        font-size: 15px;
        color: var(--text-dark);
    }

    .details input:focus {
        outline: none;
        border-color: var(--light-green);
        background: white;
    }

    .details input::placeholder {
        color: var(--text-light);
        opacity: 0.7;
    }

    button {
        color: white;
        padding: 16px 32px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    button::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    button:hover::before {
        width: 300px;
        height: 300px;
    }

    button:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    button:active:not(:disabled) {
        transform: translateY(-1px);
    }

    .cancelBtn {
        background: linear-gradient(135deg, #616161, #424242);
        width: 70%;
        margin: 10px auto 0;
    }

    .cancelBtn:hover:not(:disabled) {
        background: linear-gradient(135deg, #424242, #212121);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    .createBtn {
        margin-top: 40px;
        background: linear-gradient(135deg, var(--light-green), var(--eco-green));
        width: 70%;
        margin: 40px auto 20px;
    }

    .createBtn:hover:not(:disabled) {
        background: linear-gradient(135deg, #43a047, #2e7d32);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
    }

    .createBtn:disabled {
        background: linear-gradient(135deg, #cccccc, #b3b3b3);
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .error {
        color: #d32f2f;
        text-align: center;
        margin-bottom: 20px;
        padding: 12px;
        background: #ffebee;
        border-radius: 8px;
        border-left: 4px solid #f44336;
        font-weight: 500;
    }

    .success {
        color: #2e7d32;
        text-align: center;
        margin-bottom: 20px;
        padding: 12px;
        background: #e8f5e9;
        border-radius: 8px;
        border-left: 4px solid var(--light-green);
        font-weight: 500;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: bold;
        margin-left: 10px;
    }
    
    .pass { 
        background: #4CAF50; 
        color: white; 
    }
    
    .pending { 
        background: #FF9800; 
        color: white; 
    }
    
    .rejected { 
        background: #F44336; 
        color: white; 
    }

    @media (max-width: 768px) {
        .container {
            padding: 25px;
            width: 95%;
        }
        
        .driver-info, .details {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        button {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }
        
        .profile {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .profile img {
            width: 100px;
            height: 100px;
        }
        
        .cancelBtn, .createBtn {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 20px;
        }
        
        h2 {
            font-size: 24px;
        }
        
        button {
            padding: 12px 20px;
        }
    }
</style>
</style>
</head>
<body>

<div class="container">
    <h2>Create Car Pool Page</h2>
    <br>
    
    <?php if(!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="profile">
        <img src="/ASS-WDD/<?= htmlspecialchars($driver_photo) ?>" alt="driver photo">
        <div>
            <h3>Driver Name: <?php echo htmlspecialchars($driver_name); ?></h3>
            <span class="status-badge <?php echo htmlspecialchars($driver_status); ?>">
                Driver ID: <?php echo htmlspecialchars($driver_id); ?>
            </span>
        </div>
    </div>

    <div class="driver-info">
        <div>
            <label>Car Model</label>
            <p><?php echo htmlspecialchars($car_model); ?></p>
        </div>

        <div>
            <label>Car Color</label>
            <p><?php echo htmlspecialchars($car_color); ?></p>
        </div>

        <div>
            <label>Plate Number</label>
            <p><?php echo htmlspecialchars($plate_number); ?></p>
        </div>

        <div>
            <label>Rating</label>
            <p>4.9 ⭐</p>
        </div>

        <div>
            <label>Phone</label>
            <p>012-3456789</p>
        </div>

        <div>
            <label>Seat Available</label>
            <p><?php echo htmlspecialchars($seats_available); ?> seats</p>
        </div>
    </div>

    <hr><br>

    <form method='POST' action=''>
        <div class='details'>
            <div>
                <label>From</label>
                <input type="text" name="from_place" value="APU" readonly style="background-color: #f5f5f5; color: #666;">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Departure point is fixed as APU</small>
            </div>

            <div>
                <label>To</label>
                <input type="text" name="to_place" placeholder="eg: Bukit Jalil" required value="<?php echo isset($_POST['to_place']) ? htmlspecialchars($_POST['to_place']) : ''; ?>">
            </div>

            <div>
                <label>Time</label>
                <input type="time" name="time" required value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
            </div>

            <div>
                <label>Date</label>
                <input type="date" name="date" required value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
            </div>
        </div>
        
        <button type="submit" name="create" class='createBtn' <?php echo ($driver_status !== 'pass') ? 'disabled' : ''; ?>>
            Create
        </button>
        <button type="button" onclick="window.location.href='CarpoolList.php'" class='cancelBtn'>Cancel</button>
    </form>
    
</div>

</body>
</html>