<?php
session_start();

$host = 'localhost';
$dbname = 'wdd';
$username = 'root';
$password = '';

if(!isset($_SESSION['user_id'])){
    header('Location: ../WS/login.php');
    exit();
}

// 检查是否通过cl_id参数访问
if(!isset($_GET['cl_id'])) {
    die("Error: No carpool trip specified.");
}

$cl_id = intval($_GET['cl_id']);
if($cl_id <= 0) {
    die("Error: Invalid carpool trip ID.");
}

$error = '';
$success_message = '';
$carpool_info = null;
$user_id = $_SESSION['user_id'];
$feedback_submitted = false;

try{
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // 1. 获取行程信息
    $stmt = $conn->prepare("SELECT cl.cl_id, cl.driver_id, cl.from_place, cl.to_place, 
                                   cl.date, cl.time, cl.bording_point, cl.status_open_close,
                                   d.Plate_Number, u.name as driver_name, u.photo_url as driver_photo_url
                            FROM Carpool_List cl
                            LEFT JOIN driver d ON cl.driver_id = d.Driver_ID
                            LEFT JOIN user_acc u ON d.user_id = u.user_id
                            WHERE cl.cl_id = :cl_id");
    $stmt->execute(['cl_id' => $cl_id]);
    $carpool_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$carpool_info){
        die("Error: Carpool trip not found.");
    }
    
    // 2. 自动将状态更新为 closed（无论原来是什么状态）
    $update_stmt = $conn->prepare("UPDATE Carpool_List SET status_open_close = 'closed' WHERE cl_id = :cl_id");
    $update_result = $update_stmt->execute(['cl_id' => $cl_id]);
    
    if($update_result){
        // 重新获取行程信息以获取更新后的状态
        $stmt->execute(['cl_id' => $cl_id]);
        $carpool_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 3. 检查是否已经提交过反馈
    $check_feedback = $conn->prepare("SELECT feedback_id FROM feedback WHERE cl_id = :cl_id");
    $check_feedback->execute(['cl_id' => $cl_id]);
    
    if($check_feedback->fetch()){
        $feedback_submitted = true;
    }
    
    // 4. 处理表单提交
    if($_SERVER['REQUEST_METHOD'] == 'POST' && !$feedback_submitted){
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        
        if($rating === 0){  
            $error = 'Please select a rating (1-5 stars)';
        } elseif($rating < 1 || $rating > 5){
            $error = 'Please select a valid rating (1-5 stars)';
        } elseif(empty($comment)) {
            $error = 'Please write a comment';
        } else {
            
            $insert_sql = "INSERT INTO feedback (rating, description, cl_id) 
                           VALUES (:rating, :description, :cl_id)";
            $insert_params = [
                'rating' => $rating,
                'description' => $comment,
                'cl_id' => $cl_id
            ];
            
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_result = $insert_stmt->execute($insert_params);
            
            if($insert_result){
                $feedback_submitted = true;
                $success_message = "Thank you! Your feedback has been submitted successfully.";
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
        }
    }
    
} catch(PDOException $e) {
    $error = "Database connection error.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate Your Driver - EcoCommute</title>
<style>
    /* =================== 全局 =================== */
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
        display: flex;
        justify-content: center;
        padding: 40px 0;
    }

    /* =================== iPhone Mockup =================== */
    .phone {
        width: 375px;
        height: 700px;
        background: #fff;
        border-radius: 40px;
        box-shadow: 0 10px 30px rgba(46, 125, 50, 0.2);
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    /* =================== 内容容器 =================== */
    .container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        padding: 10px;
    }

    hr{
        background-color: #4caf50;
        border:none;
        height:2px;
        width:100%;
        margin: 10px 0 20px 0;
    }

    /* =================== 标题 =================== */
    h2 {
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #2e7d32;
    }

    /* =================== 短评占位 =================== */
    .short-text {
        background: #f1f8e9;
        height: 50px;
        border-radius: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #388e3c;
        font-size: 16px;
        font-weight: 600;
        border: 1px solid #c8e6c9;
    }

    /* =================== 司机信息卡 =================== */
    .driver-card {
        background: #f8fff8;
        padding: 20px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 5px 15px rgba(46, 125, 50, 0.05);
        margin-bottom: 15px;
        border: 1px solid #e0f2e0;
    }

    .driver-img-container {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4caf50, #2e7d32);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .driver-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .driver-initials {
        color: white;
        font-weight: bold;
        font-size: 20px;
    }

    .driver-info p {
        margin: 0;
    }

    .driver-name {
        font-weight: bold;
        font-size: 16px;
        color: #2e7d32;
    }

    .driver-detail {
        font-size: 14px;
        color: #388e3c;
    }

    /* =================== 行程信息卡 =================== */
    .carpool-card {
        background: #f0f9ff;
        padding: 15px;
        border-radius: 15px;
        margin-bottom: 15px;
        border: 1px solid #bbdefb;
    }

    .carpool-route {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .from-to {
        font-weight: 600;
        color: #1976d2;
    }

    .carpool-date {
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .carpool-time {
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .carpool-status {
        display: inline-block;
        padding: 2px 8px;
        background: #4caf50;
        color: white;
        border-radius: 12px;
        font-size: 12px;
        margin-left: 10px;
    }

    /* =================== 星星评分 =================== */
    .stars {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .stars span {
        font-size: 40px;
        cursor: pointer;
        transition: transform 0.2s;
        color: #e0e0e0;
    }

    .stars span:hover,
    .stars span.hovered {
        transform: scale(1.3);
        color: #FFD700;
    }

    .stars span.active {
        color: #FFD700;
        text-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
    }

    #ratingText {
        text-align: center;
        font-size: 16px;
        color: #2e7d32;
        font-weight: 600;
        margin: 10px 0 20px 0;
        min-height: 24px;
    }

    /* =================== 文本输入框 =================== */
    textarea {
        width: 90%;
        min-height: 100px;
        border-radius: 15px;
        border: 2px solid #c8e6c9;
        padding: 15px;
        font-size: 16px;
        resize: none;
        margin-bottom: 25px;
        font-family: inherit;
        background: #f8fff8;
        color: #333;
        transition: all 0.3s ease;
    }

    textarea:focus {
        outline: none;
        border-color: #4caf50;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        background: white;
    }

    textarea::placeholder {
        color: #9e9e9e;
    }

    /* =================== 提交按钮 =================== */
    button {
        width: 80%;
        padding: 15px;
        color: white;
        background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
        border: none;
        border-radius: 30px;
        font-size: 18px;
        cursor: pointer;
        display:block;
        margin:0 auto;
        margin-top:20px;
        font-weight:bold;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(46, 125, 50, 0.4);
    }

    button:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(46, 125, 50, 0.3);
    }

    /* =================== 提示信息 =================== */
    .tips {
        background: #f1f8e9;
        border-radius: 12px;
        padding: 12px 15px;
        margin: 15px 0;
        border-left: 4px solid #4caf50;
    }

    .tips p {
        text-align: left;
        margin: 0 0 8px 0;
        font-size: 14px;
        font-weight: 600;
        color: #2e7d32;
    }

    .tips ul {
        margin: 0;
        padding-left: 20px;
    }

    .tips li {
        font-size: 12px;
        color: #388e3c;
        margin-bottom: 3px;
    }

    /* =================== 错误消息 =================== */
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 15px;
        border: 1px solid #f5c6cb;
        margin-bottom: 20px;
        text-align: center;
    }

    /* =================== 成功消息 =================== */
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 15px;
        border: 1px solid #c3e6cb;
        margin-bottom: 20px;
        text-align: center;
    }

    /* =================== 字符计数 =================== */
    .char-count {
        text-align: right;
        font-size: 12px;
        color: #666;
        margin-top: -20px;
        margin-bottom: 15px;
        margin-right: 5%;
    }

    /* =================== 返回按钮 =================== */
    .back-btn {
        position: absolute;
        top: 30px;
        left: 30px;
        background: #4caf50;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        border: none;
        cursor: pointer;
    }

    .back-btn:hover {
        background: #388e3c;
    }

    /* =================== 已提交反馈 =================== */
    .feedback-submitted {
        background: #e8f5e9;
        padding: 20px;
        border-radius: 15px;
        margin: 20px 0;
        text-align: center;
        border: 1px solid #c8e6c9;
    }

    .feedback-submitted h3 {
        color: #2e7d32;
        margin-top: 0;
    }

    .feedback-submitted .rating-stars {
        font-size: 30px;
        color: #FFD700;
        margin: 10px 0;
    }

    .feedback-submitted p {
        color: #388e3c;
        margin: 10px 0;
    }

    /* 调试信息 */
    .debug-info {
        background: #fff3cd;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        font-size: 12px;
        color: #856404;
    }
    </style>
    </head>
    <body>

    <div class="phone">
        <button class="back-btn" onclick="window.history.back()">←</button>
        
        <div class="container"> 
            <h2>Rate Your Driver</h2>
            <hr>

            <p class="short-text">How was your ride?</p>

            <?php if(!empty($error)): ?>
            <div class="error-message" id="errorMessage">
                <strong>Error!</strong>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if(!empty($success_message)): ?>
            <div class="success-message" id="successMessage">
                <strong>Success!</strong>
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
            <?php endif; ?>

            <?php if($carpool_info): ?>
                <!-- 调试信息 -->
                <div class="debug-info" style="display: none;">
                    Debug: feedback_submitted = <?php echo $feedback_submitted ? 'true' : 'false'; ?><br>
                    Debug: cl_id = <?php echo $cl_id; ?><br>
                    Debug: Status = <?php echo htmlspecialchars($carpool_info['status_open_close']); ?>
                </div>
                
                <!-- 显示行程状态（已自动更新为closed） -->
                <div style="text-align: center; color: #4caf50; font-weight: bold; margin-bottom: 15px;">
                    ✓ Trip is ready for rating
                </div>
                
                <div class="carpool-card">
                    <div class="carpool-route">
                        <span class="from-to"><?php echo htmlspecialchars($carpool_info['from_place']); ?> → <?php echo htmlspecialchars($carpool_info['to_place']); ?></span>
                        <span class="carpool-status"><?php echo htmlspecialchars(ucfirst($carpool_info['status_open_close'])); ?></span>
                    </div>
                    <div class="carpool-date">
                        📅 <?php echo date('M d, Y', strtotime($carpool_info['date'])); ?>
                    </div>
                    <div class="carpool-time">
                        ⏰ <?php echo date('g:i A', strtotime($carpool_info['time'])); ?>
                    </div>
                    <?php if($carpool_info['bording_point']): ?>
                    <div class="carpool-date">
                        📍 Boarding Point: <?php echo htmlspecialchars($carpool_info['bording_point']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($carpool_info['driver_name']): ?>
                <div class="driver-card">
                    <div class="driver-img-container">
                        <?php if(!empty($carpool_info['driver_photo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($carpool_info['driver_photo_url']); ?>" 
                                class="driver-img" 
                                alt="Driver Photo"
                                onerror="this.style.display='none'; this.parentElement.querySelector('.driver-initials').style.display='block';">
                            <div class="driver-initials" style="display: none;">
                                <?php echo strtoupper(substr($carpool_info['driver_name'], 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="driver-initials">
                                <?php echo strtoupper(substr($carpool_info['driver_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="driver-info">
                        <p class="driver-name"><?php echo htmlspecialchars($carpool_info['driver_name']); ?></p>
                        <?php if($carpool_info['Plate_Number']): ?>
                        <p class="driver-detail">Car Plate • <?php echo htmlspecialchars($carpool_info['Plate_Number']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($feedback_submitted): ?>
                <div class="feedback-submitted">
                    <h3>✓ Feedback Submitted</h3>
                    <p>Your feedback has been recorded successfully.</p>
                    <p>Thank you for sharing your experience!</p>
                    <div style="margin-top: 20px;">
                        <button onclick="window.location.href='CarpoolList.php'">Back to Trip List</button>
                    </div>
                </div>
                <?php else: ?>
                <div class="tips">
                    <p>Helpful tips:</p>
                    <ul>
                        <li>Be specific about what you liked or didn't like</li>
                        <li>Keep your feedback constructive and respectful</li>
                        <li>Mention safety, cleanliness, punctuality, etc.</li>
                    </ul>
                </div>

                <form id="feedbackForm" method="post" action="">
                    <input type="hidden" name="cl_id" value="<?php echo $cl_id; ?>">
                    
                    <div class="stars">
                        <span data-value="1">★</span>
                        <span data-value="2">★</span>
                        <span data-value="3">★</span>
                        <span data-value="4">★</span>
                        <span data-value="5">★</span>
                    </div>
                    <p id="ratingText">Tap a star to rate (required)</p>
                    <input type="hidden" name="rating" id="ratingInput" value="0">

                    <textarea id="comment" name="comment" placeholder="Write a comment (required)..." maxlength="500" required></textarea>
                    <div class="char-count"><span id="charCount">0</span>/500</div>

                    <button type="submit" id="submitBtn">Submit Feedback</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    var stars = document.querySelectorAll('.stars span');
    var ratingText = document.getElementById('ratingText');
    var ratingInput = document.getElementById('ratingInput');
    var form = document.getElementById('feedbackForm');
    var comment = document.getElementById('comment');
    var charCount = document.getElementById('charCount');
    var rating = 0;

    // 字符计数器
    if(comment && charCount) {
        comment.addEventListener('input', function() {
            charCount.textContent = comment.value.length;
        });
        charCount.textContent = comment.value.length;
    }

    function highlightStars(num, isHover = false) {
        if(!stars.length) return;
        
        for(var i=0; i<stars.length; i++){
            if(i < num){
                stars[i].classList.add(isHover ? 'hovered' : 'active');
                if (!isHover) {
                    stars[i].classList.remove('hovered');
                }
            } else {
                stars[i].classList.remove('active', 'hovered');
            }
        }
    }

    // 星星点击
    if(stars.length > 0){
        for(var i=0; i<stars.length; i++){
            (function(index){
                stars[index].addEventListener('mouseover', function(){
                    highlightStars(index + 1, true);
                    updateRatingText(index + 1, true);
                });
                stars[index].addEventListener('mouseout', function(){
                    highlightStars(rating);
                    updateRatingText(rating);
                });
                stars[index].addEventListener('click', function(){
                    rating = index + 1;
                    ratingInput.value = rating;
                    highlightStars(rating);
                    updateRatingText(rating);
                });
            })(i);
        }
    }

    function updateRatingText(num, isHover = false) {
        if (!ratingText) return;
        
        if (!num || num === 0) {
            ratingText.textContent = 'Tap a star to rate (required)';
            return;
        }
        
        var ratings = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        
        if (isHover) {
            ratingText.textContent = ratings[num - 1] + ' (' + num + '/5)';
        } else {
            ratingText.textContent = 'You selected: ' + ratings[num - 1] + ' (' + num + '/5)';
        }
    }

    // 提交前检查 - 关键修复！
    if(form) {
        form.addEventListener('submit', function(e){
            e.preventDefault(); // 阻止表单默认提交
            
            if(rating === 0){
                alert("Please select a rating before submitting!");
                return false;
            }
            if(comment.value.trim() === ""){
                alert("Please write a comment!");
                comment.focus();
                return false;
            }
            
            // 如果验证通过，手动提交表单
            this.submit();
        });
    }

    // 页面加载效果
    window.addEventListener('load', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease';
        setTimeout(function() {
            document.body.style.opacity = '1';
        }, 100);
    });
    </script>

</body>
</html>