<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Service</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-color: #2e7d32;
        --primary-light: #4caf50;
        --primary-dark: #1b5e20;
        --secondary-color: #2196f3;
        --secondary-light: #64b5f6;
        --success-color: #28a745;
        --success-light: #66bb6a;
        --warning-color: #ff9800;
        --text-dark: #333;
        --text-medium: #555;
        --text-light: #777;
        --bg-light: #f8f9fa;
        --bg-white: #ffffff;
        --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.12);
        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 16px;
        --transition: all 0.3s ease;
    }

    * { 
        box-sizing: border-box; 
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        min-height: 100vh;
        color: var(--text-dark);
        line-height: 1.6;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .container {
        width: 90%;
        max-width: 600px;
        background: var(--bg-white);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-medium);
        padding: 50px 40px;
        text-align: center;
        border-top: 5px solid var(--primary-color);
        position: relative;
        overflow: hidden;
    }

    .container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light), var(--primary-color));
    }

    .success-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, var(--success-light), var(--success-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        color: white;
        font-size: 48px;
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        position: relative;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        50% {
            box-shadow: 0 8px 30px rgba(40, 167, 69, 0.5);
        }
        100% {
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
    }

    .success-icon::after {
        content: '';
        position: absolute;
        top: -8px;
        left: -8px;
        right: -8px;
        bottom: -8px;
        border: 3px solid rgba(40, 167, 69, 0.2);
        border-radius: 50%;
        animation: ripple 1.5s infinite;
    }

    @keyframes ripple {
        0% {
            transform: scale(0.8);
            opacity: 1;
        }
        100% {
            transform: scale(1.2);
            opacity: 0;
        }
    }

    h1 {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 15px;
        position: relative;
        padding-bottom: 15px;
    }

    h1::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 2px;
    }

    .subtitle {
        font-size: 18px;
        color: var(--text-medium);
        margin-bottom: 30px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .confirmation-text {
        background: #f8f9fa;
        padding: 20px;
        border-radius: var(--border-radius-md);
        border-left: 4px solid var(--primary-light);
        margin: 30px 0;
        text-align: left;
    }

    .confirmation-text p {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .confirmation-text i {
        color: var(--primary-color);
        width: 20px;
    }

    .button-container {
        margin-top: 40px;
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .action-button {
        padding: 16px 32px;
        border-radius: var(--border-radius-md);
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: var(--shadow-light);
        min-width: 180px;
        text-decoration: none;
    }

    .home-button {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
    }

    .home-button:hover {
        background: linear-gradient(135deg, var(--primary-dark), #1b5e20);
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
    }

    .history-button {
        background: linear-gradient(135deg, var(--secondary-color), #1976d2);
        color: white;
    }

    .history-button:hover {
        background: linear-gradient(135deg, #1976d2, #0d47a1);
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
    }

    .action-button:active {
        transform: translateY(-1px);
    }

    .additional-info {
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #eee;
        font-size: 14px;
        color: var(--text-light);
    }

    .contact-info {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 10px;
    }

    .contact-info a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
    }

    .contact-info a:hover {
        text-decoration: underline;
    }

    /* 响应式设计 */
    @media (max-width: 768px) {
        .container {
            padding: 40px 25px;
        }
        
        h1 {
            font-size: 30px;
        }
        
        .subtitle {
            font-size: 16px;
        }
        
        .button-container {
            flex-direction: column;
            align-items: center;
        }
        
        .action-button {
            width: 100%;
            max-width: 280px;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 30px 20px;
            width: 95%;
        }
        
        h1 {
            font-size: 26px;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }
        
        .action-button {
            padding: 14px 24px;
            font-size: 15px;
        }
    }

    /* 动画效果 */
    .container {
        animation: fadeIn 0.5s ease forwards;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-message {
        animation: slideUp 0.5s ease 0.3s forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    @keyframes slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .buttons {
        animation: slideUp 0.5s ease 0.5s forwards;
        opacity: 0;
        transform: translateY(20px);
    }
</style>
</head>
<body>

<div class="container">
    <!-- 成功图标 -->
    <div class="success-icon">
        <i class="fas fa-check"></i>
    </div>

    <!-- 成功标题 -->
    <h1 class="success-message">Submission Successful!</h1>
    
    <!-- 副标题 -->
    <p class="subtitle success-message">
        Thank you for submitting your issue. Our customer service team will review it and get back to you as soon as possible.
    </p>

    <div class="confirmation-text success-message">
        <p><i class="fas fa-check-circle"></i> Your issue has been recorded successfully</p>
        <p><i class="fas fa-clock"></i> Reference ID: CS-<?php echo date('Ymd') . '-' . rand(1000, 9999); ?></p>
    </div>

    <div class="button-container buttons">
        <button class="action-button home-button" onclick="window.location.href='../Aston/profile.php'">
            <i class="fas fa-home"></i> Back to Profile
        </button>
    </div>


</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 自动重定向定时器（30秒后）
        setTimeout(function() {
            const confirmRedirect = confirm('You will be redirected to the homepage in 10 seconds. Click OK to go now, or Cancel to stay on this page.');
            if (confirmRedirect) {
                window.location.href = '../WS/homepage.php';
            }
        }, 20000); // 20秒后提示
        
    });
</script>

</body>
</html>