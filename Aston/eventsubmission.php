<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Successful - GOBy</title>

    <link rel="stylesheet" href="/ASS-WDD/Aston/style.css?v=2">

<style>
    /* 页面容器 */
    .success-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: var(--bg);
    }

    /* 成功卡片 */
    .success-card {
        max-width: 700px;
        width: 100%;
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 32px 28px;
        text-align: center;
        border-left: 4px solid var(--light-green);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .success-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(46,125,50,0.15);
    }

    /* 成功图标 */
    .success-icon {
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: var(--eco-green);
        box-shadow: 0 4px 12px rgba(46,125,50,0.1);
        transition: transform 0.3s ease;
    }

    .success-card:hover .success-icon {
        transform: scale(1.05) rotate(5deg);
    }

    /* 文本内容 */
    .success-card h2 {
        margin: 0 0 10px;
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .success-card p {
        margin: 0 0 30px;
        font-size: 15px;
        color: var(--text-light);
        line-height: 1.6;
    }

    /* 按钮区域 */
    .success-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* 按钮样式 */
    .continue-btn, .view-event-btn {
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-weight: 600;
        transition: all 0.3s ease;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        display: inline-block;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .continue-btn {
        background: var(--light-green);
        color: white;
    }

    .continue-btn:hover {
        background: var(--eco-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(46,125,50,0.2);
    }

    .view-event-btn {
        background: white;
        color: var(--text-dark);
        border: 1px solid var(--light-green);
    }

    .view-event-btn:hover {
        background: #f1f8e9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(46,125,50,0.1);
    }

    /* 响应式设计 */
    @media (max-width: 600px) {
        .success-card {
            padding: 28px 20px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }
        
        .success-card h2 {
            font-size: 22px;
        }
        
        .success-actions {
            flex-direction: column;
            gap: 12px;
        }
        
        .continue-btn, .view-event-btn {
            width: 100%;
            padding: 14px 20px;
        }
    }
</style>
</head>

<body>

<div class="success-wrapper">
  <div class="success-card">

    <div class="success-icon">✅</div>

    <h2>Submit Successful</h2>
    <p>
      Thank you for your submission.<br>
      Your information has been successfully received.
    </p>

    <div class="success-actions">
      <a href="../WS/homepage.php" class="continue-btn">Back to Homepage</a>
      <a href="profile.php" class="view-event-btn">View Profile</a>
    </div>

  </div>
</div>

</body>
</html>
